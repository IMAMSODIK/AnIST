<?php

namespace App\AI;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GeminiService
{
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    protected int $maxRetries;
    protected int $rateLimitMaxWaitSec;

    public function __construct()
    {
        // NOTE: api_key is intentionally NOT resolved here. It is resolved
        // lazily on every request/worker via resolveApiKey() so that an
        // admin updating the key from /settings (persisted to AppSetting /
        // database) takes effect IMMEDIATELY for both web requests and the
        // long-running queue worker — without restarting the worker and
        // without rewriting the .env file (which is read-only on shared
        // hosting such as Hostinger and ignored when config:cache is on).
        $this->model = Config::get('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = Config::get('services.gemini.base_url');
        $this->timeout = Config::get('services.gemini.timeout', 120);
        $this->maxRetries = Config::get('services.gemini.max_retries', 3);
        // Maximum time we are willing to sleep waiting for a 429 quota reset
        // before giving up. The default keeps us below PHP's max_execution_time
        // for the queue worker while still allowing Gemini's "retry in ~47s"
        // advice to be honoured.
        $this->rateLimitMaxWaitSec = (int) Config::get('services.gemini.rate_limit_max_wait_sec', 90);
    }

    /**
     * Resolve the active Gemini API key.
     *
     * Resolution order (first match wins):
     *   1. AppSetting::get('gemini_api_key') — value written from /settings,
     *      stored encrypted in the database, cached for ~60s so the queue
     *      worker does not hit the DB on every Gemini request.
     *   2. config('services.gemini.api_key') — value from .env (or from the
     *      compiled config cache when config:cache is enabled).
     *
     * Because this is called on EVERY sendWithRetry() iteration, an admin
     * who updates the key from /settings does not need to restart the queue
     * worker or rewrite .env — the AppSetting cache TTL (= 60s) is the only
     * propagation delay. AppSetting::set() also calls Cache::forget() so the
     * change is picked up immediately by the web process that performed the
     * update; other workers / processes follow within the TTL.
     */
    protected function resolveApiKey(): string
    {
        $dbKey = AppSetting::get('gemini_api_key');
        if (is_string($dbKey) && $dbKey !== '') {
            return $dbKey;
        }

        return (string) (Config::get('services.gemini.api_key') ?? '');
    }

    /**
     * Check if the service is configured (any key source present).
     */
    public function isConfigured(): bool
    {
        return !empty($this->resolveApiKey());
    }

    /**
     * Analyze evidence file with a specific prompt
     */
    public function analyzeEvidence(string $prompt, ?string $fileBase64 = null, ?string $mimeType = null): array
    {
        $startTime = microtime(true);

        try {
            $parts = [];

            // Add file if provided
            if ($fileBase64 && $mimeType) {
                $parts[] = [
                    'inline_data' => [
                        'mime_type' => $mimeType,
                        'data' => $fileBase64,
                    ],
                ];
            }

            // Add text prompt
            $parts[] = ['text' => $prompt];

            $payload = [
                'contents' => [
                    [
                        'parts' => $parts,
                    ],
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'topP' => 0.95,
                    'responseMimeType' => 'application/json',
                ],
            ];

            $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

            $response = $this->sendWithRetry($url, $payload);

            $processingTime = round(microtime(true) - $startTime, 2);

            if (!$response->successful()) {
                Log::error('Gemini API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                $friendlyError = $this->friendlyErrorMessage($response);

                return [
                    'success' => false,
                    'error' => $friendlyError,
                    'processing_time' => $processingTime,
                    'raw_response' => $response->body(),
                ];
            }

            $body = $response->json();
            $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                return [
                    'success' => false,
                    'error' => 'No text content in Gemini response',
                    'processing_time' => $processingTime,
                    'raw_response' => $body,
                ];
            }

            // Parse JSON response
            $parsed = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                // Try to extract JSON from text
                $parsed = $this->extractJson($text);
            }

            if (!$parsed) {
                return [
                    'success' => false,
                    'error' => 'Failed to parse JSON from Gemini response',
                    'processing_time' => $processingTime,
                    'raw_response' => $text,
                ];
            }

            return [
                'success' => true,
                'data' => $parsed,
                'processing_time' => $processingTime,
                'raw_response' => $body,
            ];

        } catch (\Exception $e) {
            $processingTime = round(microtime(true) - $startTime, 2);

            Log::error('Gemini service exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'processing_time' => $processingTime,
                'raw_response' => null,
            ];
        }
    }

    /**
     * Analyze a text-only prompt with Google Search grounding enabled (with
     * automatic fallback). Used by the Strategic Advisor feature where the AI
     * is asked to analyze an uploaded strategic document (RJPP / MPTI /
     * external research) AND surface current internet trends.
     *
     * STRATEGY (rubust terhadap free-tier limitation):
     *   1. Try call WITH `google_search_retrieval` tool (live grounding).
     *   2. If that fails with HTTP 429/403 (free tier often disallows the
     *      grounding tool entirely — error: "limit: 0 generate_content_*"),
     *      automatically retry WITHOUT the tool. The model still produces
     *      strategic analysis + recommendations + trends, but the trends are
     *      drawn from training knowledge instead of the live web.
     *   3. Caller is informed via the `grounded` flag in the return shape so
     *      the UI can display a "Live Grounding: ON/OFF" badge.
     *
     * Why a fallback instead of failing? Grounding availability depends on
     * the project quota tier — trial keys and many free-tier projects do not
     * get any grounding quota at all (limit: 0), so they would otherwise see
     * "Kuota habis" forever even though the regular text API works fine.
     *
     * When grounded mode is enabled, Gemini does NOT accept
     * `responseMimeType: application/json` — the tool returns free text with
     * `groundingMetadata` describing the cited sources. We therefore request
     * no specific response mime in either mode and parse the JSON out of the
     * resulting text via `extractJson()` (the prompt still instructs the
     * model to return pure JSON).
     *
     * Return shape:
     *   [ 'success'         => bool,
     *     'data'            => array|null,
     *     'error'           => string|null,
     *     'processing_time' => float,
     *     'raw_response'    => mixed,
     *     'grounding'       => array|null,  // metadata when grounded
     *     'grounded'         => bool,        // true bila grounding aktif
     *     'raw_text'        => string|null ] // helo debug bila JSON parse gagal
     */
    public function analyzeWithSearch(string $prompt): array
    {
        $startTime = microtime(true);

        if (! $this->isConfigured()) {
            return $this->searchFailure(
                'Gemini API key belum dikonfigurasi. Atur di menu Settings.',
                $startTime, null, false,
            );
        }

        // ---------- Attempt 1: WITH grounding ----------
        $groundedResult = $this->callGenerateContent($prompt, useGrounding: true);

        if ($groundedResult['ok']) {
            $groundedResult['processing_time'] = round(microtime(true) - $startTime, 2);
            $groundedResult['grounded'] = true;

            return $groundedResult;
        }

        // ---------- Decision: fallback or surface error? ----------
        // Fallback ONLY on errors that indicate the grounding tool itself is
        // unavailable on this key/tier — NOT on transient 5xx or genuine
        // quota-exhaustion of the regular text API (which would also fail
        // without grounding, so no point retrying).
        $groundErr  = $groundedResult['error'] ?? '';
        $groundCode = $groundedResult['http_code'] ?? 0;

        $willFallbackQuota = $this->isGroundingUnavailableError($groundErr, $groundCode);

        // MALFORMED_FUNCTION_CALL dengan grounded mode bisa juga karena tool
        // grounding + JSON kompleks terlalu berat. Treat sama: retry tanpa
        // grounding.
        $willFallbackMalformed = stripos($groundErr, 'MALFORMED_FUNCTION_CALL') !== false;

        Log::warning('Gemini grounded call failed; deciding fallback', [
            'http_code'      => $groundCode,
            'error'          => $groundErr,
            'will_fallback'  => $willFallbackQuota || $willFallbackMalformed,
        ]);

        if (! $willFallbackQuota && ! $willFallbackMalformed) {
            $groundedResult['processing_time'] = round(microtime(true) - $startTime, 2);
            $groundedResult['grounded'] = false;

            return $groundedResult;
        }

        // ---------- Attempt 2: WITHOUT grounding ----------
        $plainResult = $this->callGenerateContent($prompt, useGrounding: false);

        // ---------- Attempt 3: bila non-grounded juga MALFORMED, retry lagi
        // dengan mode "low complexity" — hapus tool grounding sama sekali
        // dan disable tools yang bisa trigger function-calling path. Sudah
        // dilakukan di Attempt 2, jadi di sini cuma coba sekali lagi dengan
        // suhu rendah dan instruksi JSON ekstra tegas.
        if (! $plainResult['ok'] && stripos($plainResult['error'] ?? '', 'MALFORMED_FUNCTION_CALL') !== false) {
            Log::warning('Gemini plain mode also MALFORMED — retrying once more', []);
            $plainResult = $this->callGenerateContent($prompt, useGrounding: false);
        }

        $plainResult['processing_time'] = round(microtime(true) - $startTime, 2);
        $plainResult['grounded']        = false;
        $plainResult['grounding']       = null;

        // Annotate so caller/UI knows grounding was disabled due to quota.
        if ($plainResult['success']) {
            $plainResult['fallback_reason'] = 'Grounding dinonaktifkan (tidak tersedia pada API key / quota tier). Tren berasal dari knowledge model, bukan live web.';
        }

        return $plainResult;
    }

    /**
     * Inner helper: single call to Gemini :generateContent. Returns a
     * normalized array used by analyzeWithSearch. Set $useGrounding=true to
     * inject the google_search_retrieval tool.
     */
    protected function callGenerateContent(string $prompt, bool $useGrounding): array
    {
        try {
            $payload = [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.2,
                    'topP'        => 0.95,
                ],
            ];

            if ($useGrounding) {
                // stdClass → empty object `{}` in JSON, required by spec.
                $payload['tools'] = [
                    ['google_search_retrieval' => new \stdClass()],
                ];
            }

            $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

            $response = $this->sendWithRetry($url, $payload);

            if (! $response->successful()) {
                Log::error('Gemini API error', [
                    'grounded' => $useGrounding,
                    'status'   => $response->status(),
                    'body'     => $response->body(),
                ]);

                return [
                    'success'      => false,
                    'error'        => $this->friendlyErrorMessage($response),
                    'http_code'     => $response->status(),
                    'raw_response' => $response->body(),
                    'ok'           => false,
                ];
            }

            $body      = $response->json() ?? [];
            $text      = $body['candidates'][0]['content']['parts'][0]['text'] ?? null;
            $grounding = $body['candidates'][0]['groundingMetadata'] ?? null;
            $finish    = $body['candidates'][0]['finishReason'] ?? null;

            // Gemini dapat mengembalikan $text = null dibarengi finishReason
            // non-STOP — paling sering "MALFORMED_FUNCTION_CALL" (model
            // terlalu kompleks menulis JSON dalam 1-2 token) atau "SAFETY"
            // (prompt ditolak filter konten). Beri pesan spesifik supaya
            // caller tahu bahwa bukan server error.
            if (! $text) {
                $reason = $finish ?? 'UNKNOWN';
                $hint = match ($reason) {
                    'MALFORMED_FUNCTION_CALL' => 'Model gagal menyusun JSON dalam batas token. Sederhanakan prompt / coba lagi.',
                    'SAFETY',                  => 'Prompt ditolak oleh safety filter. Tinjau konten dokumen.',
                    'RECITATION',              => 'Respons dihentikan karena recitation (kutipan terlalu mirip sumber).',
                    'MAX_TOKENS',               => 'Batas token tercapai sebelum JSON lengkap. Kurangi panjang prompt.',
                    default                    => 'Respons kosong tanpa selesai (kemungkinan prompt ditolak).',
                };

                return [
                    'success'      => false,
                    'error'        => $hint . " (finishReason={$reason})",
                    'http_code'     => $response->status(),
                    'raw_response' => $body,
                    'grounding'    => $grounding,
                    'ok'           => false,
                ];
            }

            $parsed = json_decode($text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $parsed = $this->extractJson($text);
            }

            if (! $parsed) {
                // Partial success: keep raw text for UI fallback.
                return [
                    'success'      => false,
                    'error'        => 'Respons Gemini tidak dapat di-parse sebagai JSON (raw tetap ditampilkan).',
                    'http_code'     => $response->status(),
                    'raw_response' => $text,
                    'grounding'    => $grounding,
                    'raw_text'     => $text,
                    'ok'           => false,
                ];
            }

            return [
                'success'      => true,
                'data'         => $parsed,
                'raw_response' => $body,
                'grounding'    => $grounding,
                'ok'           => true,
            ];
        } catch (\Exception $e) {
            Log::error('Gemini service exception', [
                'grounded' => $useGrounding,
                'message'  => $e->getMessage(),
            ]);

            return [
                'success'      => false,
                'error'        => $e->getMessage(),
                'http_code'     => 0,
                'raw_response' => null,
                'ok'           => false,
            ];
        }
    }

    /**
     * Determine if the failure indicates the grounding search tool itself is
     * unavailable (worth retrying without grounding) vs a true outage.
     *
     * Strategy: always retry without grounding on 429 / 403. Grounding has a
     * SEPARATE quota on the Gemini API free tier — observed behaviour is that
     * many free-tier project keys have `limit: 0` for the grounding tool but
     * unlimited for regular text generation. So a 429 on grounded mode is
     * commonly a permanent "tool not available" condition rather than a
     * transient per-minute exhaustion, and the fallback will usually succeed.
     * If fallback ALSO fails (true per-minute quota), the surfaced error
     * reflects the non-grounded attempt — which is what the user actually has
     * any chance of fixing by waiting.
     */
    protected function isGroundingUnavailableError(string $msg, int $httpCode): bool
    {
        return in_array($httpCode, [429, 403], true);
    }

    /** Convenience wrapper to build a failure return-shape consistently. */
    protected function searchFailure(string $msg, float $startTime, mixed $raw, bool $grounded): array
    {
        return [
            'success'         => false,
            'data'            => null,
            'error'           => $msg,
            'processing_time' => round(microtime(true) - $startTime, 2),
            'raw_response'    => $raw,
            'grounding'       => null,
            'grounded'         => $grounded,
        ];
    }

    /**
     * Send request with retry logic.
     *
     * Honours the `Retry-After` response header (seconds) and, when Gemini's
     * 429 body contains a "Please retry in Xs" message, parses that value so
     * we sleep long enough for the per-minute free-tier quota (20 req/min) to
     * reset instead of hammering the API with short exponential backoff that
     * always re-hits 429. The wait is capped by `rate_limit_max_wait_sec` so
     * the queue worker does not stall indefinitely on a misbehaving endpoint.
     */
    protected function sendWithRetry(string $url, array $payload): \Illuminate\Http\Client\Response
    {
        $attempt = 0;
        $lastException = null;
        $response = null;

        // Guard against a misconfigured `max_retries` of 0, which previously
        // left `$response` undefined and made the trailing return fatal.
        if ($this->maxRetries < 1) {
            $this->maxRetries = 1;
        }

        while ($attempt < $this->maxRetries) {
            try {
                $response = Http::timeout($this->timeout)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                        // Auth via header keeps the key out of the request URL
                        // (and therefore out of access logs). Resolved on every
                        // send attempt so an admin pushing a new key via
                        // /settings takes effect immediately.
                        'x-goog-api-key' => $this->resolveApiKey(),
                    ])
                    ->post($url, $payload);

                // Don't retry on 4xx client errors (except 429 rate limit)
                if ($response->successful() || ($response->clientError() && $response->status() !== 429)) {
                    return $response;
                }

                // Retry on 429 and 5xx
                if ($response->status() === 429 || $response->serverError()) {
                    $attempt++;
                    if ($attempt < $this->maxRetries) {
                        $delaySec = $this->computeRetryDelaySeconds($response, $attempt);
                        usleep((int) ($delaySec * 1_000_000));
                    }
                    continue;
                }

                return $response;

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    $delaySec = min(60, pow(2, $attempt)); // 2,4,8,16,32,60s
                    usleep((int) ($delaySec * 1_000_000));
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return $response;
    }

    /**
     * Compute how many seconds to sleep before the next retry.
     *
     * Priority:
     *   1. `Retry-After` response header (seconds or HTTP date).
     *   2. "Please retry in Xs" / "X seconds" string in the 429 body —
     *      Gemini's free tier usually tells us exactly when the quota
     *      resets, e.g. "Please retry in 46.912038772s".
     *   3. Exponential backoff fallback (2,4,8,16,32,60s) for 5xx or when
     *      no hint is available.
     *
     * The wait is always clamped to [1, rateLimitMaxWaitSec] so a malicious
     * or buggy endpoint cannot stall the worker forever.
     */
    protected function computeRetryDelaySeconds(\Illuminate\Http\Client\Response $response, int $attempt): float
    {
        $fallback = min(60, pow(2, $attempt)); // 2,4,8,16,32,60s
        $delay = $fallback;

        // 1. Retry-After header (seconds or HTTP-date).
        if ($response->headers()) {
            $retryAfter = $response->header('Retry-After');
            if ($retryAfter) {
                if (is_numeric($retryAfter)) {
                    $delay = (float) $retryAfter;
                } else {
                    $ts = strtotime($retryAfter);
                    if ($ts !== false) {
                        $delay = max(0, (float) ($ts - time()));
                    }
                }
            }
        }

        // 2. Parse Gemini 429 body: "Please retry in 46.912038772s" or
        //    "retry in 47 seconds".
        $body = $response->body();
        if (is_string($body) && $body !== '') {
            if (preg_match('/retry in ([0-9]+(?:\.[0-9]+)?)\s*s/i', $body, $m)) {
                $delay = (float) $m[1];
            } elseif (preg_match('/retry in ([0-9]+(?:\.[0-9]+)?)\s*seconds?/i', $body, $m)) {
                $delay = (float) $m[1];
            }
        }

        // Clamp to sane bounds.
        $delay = max(1.0, min((float) $this->rateLimitMaxWaitSec, $delay));

        return $delay;
    }

    /**
     * Try to extract JSON from text that may contain non-JSON content
     */
    protected function extractJson(string $text): ?array
    {
        // Try to find JSON block in markdown code blocks
        if (preg_match('/```json\s*(.*?)\s*```/s', $text, $matches)) {
            $decoded = json_decode($matches[1], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        // Try to find JSON object
        if (preg_match('/\{.*\}/s', $text, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * Test that the configured API key actually works. Intended to be invoked
     * from /settings after the admin pastes a new key so they get immediate
     * feedback (the key either works now, or it doesn't — no need to upload
     * evidence first). Uses the cheapest possible Gemini call: a 1-token
     * generateContent request with "ping".
     *
     * Returns an array suitable for JSON response:
     *   [ 'success' => bool, 'message' => string, 'detail' => string|null ]
     */
    public function testApiKey(?string $overrideKey = null): array
    {
        $apiKey = $overrideKey ?? $this->resolveApiKey();

        if ($apiKey === '') {
            return [
                'success' => false,
                'message' => 'API key belum dikonfigurasi.',
                'detail'  => null,
            ];
        }

        $url = "{$this->baseUrl}/models/{$this->model}:generateContent";

        try {
            $response = Http::timeout(20)
                ->withHeaders([
                    'Content-Type'    => 'application/json',
                    'x-goog-api-key'  => $apiKey,
                ])
                ->post($url, [
                    'contents' => [
                        ['parts' => [['text' => 'ping']]],
                    ],
                    'generationConfig' => [
                        'temperature'   => 0,
                        'maxOutputTokens'=> 1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Tidak dapat terhubung ke Gemini: ' . $e->getMessage(),
                'detail'  => null,
            ];
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'message' => "API key valid. Model '{$this->model}' dapat diakses.",
                'detail'  => null,
            ];
        }

        // Re-use the friendly error formatter we already have for the upload
        // pipeline so the user gets the same actionable message here.
        return [
            'success' => false,
            'message' => $this->friendlyErrorMessage($response),
            'detail'  => 'HTTP ' . $response->status(),
        ];
    }

    /**
     * Translate a Gemini API error response into a user-friendly Indonesian
     * message that explains WHAT went wrong and the concrete next step the
     * admin should take. The technical payload is still logged at ERROR
     * level; this method only shapes the string that surfaces to the UI so
     * an admin sees "API key tidak valid" instead of opaque "status 403".
     */
    protected function friendlyErrorMessage(\Illuminate\Http\Client\Response $response): string
    {
        $status = $response->status();
        $body = (string) $response->body();

        // Try to extract the Google error `status` and `message`.
        $gStatus = null;
        $gMessage = null;
        $decoded = json_decode($body, true);
        if (is_array($decoded) && isset($decoded['error'])) {
            $gStatus = $decoded['error']['status'] ?? null;
            $gMessage = $decoded['error']['message'] ?? null;
        }

        return match ($status) {
            401 => 'API key tidak valid atau sudah kedaluwarsa. Generate ulang API key di Google AI Studio (format AIza...). Jika key sudah benar, periksa GEMINI_MODEL di .env — model \''. $this->model . '\' mungkin tidak tersedia di tier/key Anda.',
            403 => match ($gStatus) {
                'PERMISSION_DENIED' => 'Akses project ditolak (PERMISSION_DENIED). Kemungkinan project di-suspend atau API key tidak berasal dari Google AI Studio (format AIza...). Generate ulang key resmi di https://aistudio.google.com/app/apikey.',
                default => 'Gemini API menolak akses (HTTP 403). Periksa kembali API key, project Google Cloud, dan apakah API "Generative Language API" sudah di-enabled.',
            },
            404 => "Model '{$this->model}' tidak ditemukan (HTTP 404). Periksa GEMINI_MODEL di .env — gunakan model yang valid seperti 'gemini-2.5-flash' atau 'gemini-2.0-flash'.",
            429 => 'Kuota Gemini free tier habis (limit 20 request/menit). Tunggu ±1 menit lalu upload ulang, atau upgrade ke paid tier. (Model terpakai: \'' . $this->model . '\'.)',
            500, 502, 503 => 'Server Gemini sedang bermasalah (HTTP ' . $status . '). Coba lagi beberapa saat.',
            default => 'Gemini API error (HTTP ' . $status . '): ' . ($gMessage ?? substr($body, 0, 200)),
        };
    }
}
