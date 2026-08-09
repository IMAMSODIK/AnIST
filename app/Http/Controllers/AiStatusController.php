<?php

namespace App\Http\Controllers;

use App\AI\GeminiService;
use App\Models\AppSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiStatusController extends Controller
{
    public function __construct(
        protected GeminiService $gemini,
    ) {}

    public function __invoke()
    {
        // Respect the same key resolution as GeminiService so the topbar
        // status badge reflects the ACTIVELY used key (DB override when
        // present, otherwise .env / config cache). Without this, the badge
        // would still show the key AFTER it was invalid / expired, even
        // though the upload pipeline already failed on every new request.
        $apiKey = AppSetting::get('gemini_api_key')
            ?: config('services.gemini.api_key');
        $baseUrl = config('services.gemini.base_url');
        $model = config('services.gemini.model');

        if (!$apiKey) {
            return response()->json([
                'status' => 'offline',
                'message' => 'API Key not configured',
                'model' => $model,
            ]);
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'x-goog-api-key' => $apiKey,
                ])
                ->get("{$baseUrl}/models/{$model}");

            if ($response->successful()) {
                return response()->json([
                    'status' => 'online',
                    'message' => 'AI is operational',
                    'model' => $model,
                ]);
            }

            // Surface a friendlier message so the admin knows to update the
            // key (e.g. 401/403 → invalid/expired key).
            $friendly = $this->gemini->testApiKey($apiKey);

            return response()->json([
                'status' => 'error',
                'message' => $friendly['message'] ?? ('API returned error: ' . $response->status()),
                'model' => $model,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'offline',
                'message' => 'Connection failed: ' . substr($e->getMessage(), 0, 100),
                'model' => $model,
            ]);
        }
    }
}
