<?php

namespace App\Services;

use App\AI\GeminiService;
use App\AI\PromptManager;
use App\AI\ResponseValidator;
use App\DTO\AiResultDTO;
use App\Models\AiResult;
use App\Models\AuditTrail;
use App\Models\Realisasi;
use App\Models\Upload;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EvidenceService
{
    public function __construct(
        protected GeminiService $gemini,
        protected PromptManager $promptManager,
        protected ResponseValidator $validator,
        protected KPIService $kpiService,
        protected InsightService $insightService,
    ) {}

    public function processEvidence(Upload $upload): AiResult
    {
        $upload->update(['status' => 'processing']);
        $this->logAudit('request_ai', $upload, ['status' => 'processing']);

        try {
            $measurement = $upload->measurement;
            $initiatives = $measurement->initiatives->pluck('initiative')->toArray();

            $prompt = $this->promptManager->generatePrompt($measurement, $initiatives);

            $fileContent = Storage::disk('local')->get($upload->file_path);
            $fileBase64 = base64_encode($fileContent);
            $mimeType = $upload->file_type;

            $response = $this->gemini->analyzeEvidence($prompt, $fileBase64, $mimeType);

            $this->logAudit('response_ai', $upload, [
                'success' => $response['success'],
                'processing_time' => $response['processing_time'] ?? null,
            ]);

            if (!$response['success']) {
                $dto = AiResultDTO::fromError(
                    error: $response['error'] ?? 'Unknown AI error',
                    processingTime: $response['processing_time'] ?? 0,
                    rawResponse: $response['raw_response'] ?? null,
                );
                return $this->storeFailedResult($upload, $dto);
            }

            $validation = $this->validator->validate($response['data']);

            $this->logAudit('validate_result', $upload, [
                'valid' => $validation['valid'],
                'errors' => $validation['errors'],
            ]);

            if (!$validation['valid']) {
                $dto = AiResultDTO::fromError(
                    error: 'Validation failed: ' . implode(', ', $validation['errors']),
                    processingTime: $response['processing_time'] ?? 0,
                    rawResponse: $response['raw_response'] ?? null,
                );
                return $this->storeFailedResult($upload, $dto);
            }

            $dto = AiResultDTO::fromAiResponse($validation['data'], $response);
            $aiResult = $this->storeAiResult($upload, $dto);

            if ($dto->evidenceValid) {
                $this->updateRealisasi($upload, $dto->realisasi);

                $this->kpiService->calculateScore(
                    $upload->measurement_id,
                    $upload->quarter,
                    $upload->year
                );

                $this->logAudit('calculate_kpi', $upload, [
                    'measurement_id' => $upload->measurement_id,
                    'quarter' => $upload->quarter,
                    'year' => $upload->year,
                ]);

                // Refresh the narrative insight so the reports page always
                // reflects the latest evidence. Wrapped in try-catch so an
                // AI hiccup here can never break the upload flow.
                try {
                    $this->insightService->generateInsight(
                        $upload->measurement_id,
                        $upload->quarter,
                        $upload->year
                    );
                } catch (\Throwable $e) {
                    Log::warning('Insight refresh after evidence processing failed (suppressed)', [
                        'upload_id' => $upload->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $upload->update(['status' => 'completed']);
            return $aiResult;

        } catch (\Exception $e) {
            Log::error('Evidence processing failed', [
                'upload_id' => $upload->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $upload->update(['status' => 'failed']);

            $dto = AiResultDTO::fromError(error: $e->getMessage());

            return AiResult::create([
                'upload_id' => $upload->id,
                ...$dto->toArray(),
            ]);
        }
    }

    /**
     * Persist a failed/error AI result derived from a DTO.
     */
    protected function storeFailedResult(Upload $upload, AiResultDTO $dto): AiResult
    {
        $upload->update(['status' => 'failed']);

        return AiResult::create([
            'upload_id' => $upload->id,
            ...$dto->toArray(),
        ]);
    }

    /**
     * Persist a successful AI result derived from a DTO.
     */
    protected function storeAiResult(Upload $upload, AiResultDTO $dto): AiResult
    {
        return AiResult::create([
            'upload_id' => $upload->id,
            ...$dto->toArray(),
        ]);
    }

    protected function updateRealisasi(Upload $upload, float $value): Realisasi
    {
        return Realisasi::updateOrCreate(
            [
                'measurement_id' => $upload->measurement_id,
                'quarter' => $upload->quarter,
                'year' => $upload->year,
            ],
            [
                'value' => $value,
                'source' => 'ai',
            ]
        );
    }

    protected function logAudit(string $action, Upload $upload, array $newValues = []): void
    {
        try {
            AuditTrail::create([
                'user_id' => $upload->uploaded_by,
                'action' => $action,
                'model_type' => Upload::class,
                'model_id' => $upload->id,
                'new_values' => $newValues,
                'ip_address' => request()->ip() ?? null,
                'user_agent' => request()->userAgent() ?? null,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log audit trail', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
