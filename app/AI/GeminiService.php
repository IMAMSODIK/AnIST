<?php

namespace App\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class GeminiService
{
    protected string $apiKey;
    protected string $model;
    protected string $baseUrl;
    protected int $timeout;
    protected int $maxRetries;

    public function __construct()
    {
        $this->apiKey = Config::get('services.gemini.api_key');
        $this->model = Config::get('services.gemini.model', 'gemini-2.0-flash');
        $this->baseUrl = Config::get('services.gemini.base_url');
        $this->timeout = Config::get('services.gemini.timeout', 120);
        $this->maxRetries = Config::get('services.gemini.max_retries', 3);
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

                return [
                    'success' => false,
                    'error' => 'Gemini API returned status ' . $response->status(),
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
     * Send request with retry logic
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
                        // (and therefore out of access logs).
                        'x-goog-api-key' => $this->apiKey,
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
                        $delay = pow(2, $attempt) * 1000; // exponential backoff in ms
                        usleep($delay * 1000);
                    }
                    continue;
                }

                return $response;

            } catch (\Exception $e) {
                $lastException = $e;
                $attempt++;
                if ($attempt < $this->maxRetries) {
                    $delay = pow(2, $attempt) * 1000;
                    usleep($delay * 1000);
                }
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return $response;
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
     * Check if the service is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey);
    }
}
