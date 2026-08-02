<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiStatusController extends Controller
{
    public function __invoke()
    {
        $apiKey = config('services.gemini.api_key');
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

            return response()->json([
                'status' => 'error',
                'message' => 'API returned error: ' . $response->status(),
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
