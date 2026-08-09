<?php

namespace App\Services;

use App\AI\GeminiService;
use App\AI\PromptManager;
use App\DTO\DocumentExtractionDTO;
use App\Models\StrategicRecommendation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * StrategicAdvisorService
 * -----------------------
 * Orchestrates the "Strategic Advisor" feature: user uploads a strategic
 * reference PDF (RJPP / MPTI / external research paper), the service:
 *
 *   1. Persists the uploaded file under `storage/app/strategic-advisor/`.
 *   2. Creates a `StrategicRecommendation` record with status=processing.
 *   3. Extracts the document structure via `DocumentExtractorService::extract()`
 *      (returns a non-sensitive `DocumentExtractionDTO`).
 *   4. Builds a Strategic Advisor prompt via `PromptManager::generateStrategicAdvisorPrompt()`.
 *   5. Calls Gemini with **Google Search grounding** enabled via
 *      `GeminiService::analyzeWithSearch()` so the AI can cite current
 *      internet trends beyond its training cutoff.
 *   6. Parses the JSON response and persists the analysis, recommendations,
 *      trends, perspective coverage and matched KPIs back to the record.
 *
 * Failure handling: any exception OR unsuccessful Gemini call is recorded on
 * the same record with status=failed and the user-facing error message, so the
 * upload is never silently lost. Raw Gemini response is always persisted.
 *
 * No queue is used — the feature is intended for interactive use (synchronous
 * call). A typical PDF + extraction + grounded Gemini call takes 15-45s.
 */
class StrategicAdvisorService
{
    public function __construct(
        protected DocumentExtractorService $extractor,
        protected GeminiService $gemini,
        protected PromptManager $promptManager,
    ) {}

    /**
     * Process an uploaded strategic reference PDF and return the populated
     * StrategicRecommendation record (status=completed on success, status=failed
     * on AI/parse errors — both with raw_response persisted for audit).
     */
    public function process(UploadedFile $file, int $userId): StrategicRecommendation
    {
        // Ekstraksi PDF (~20s) + Gemini grounded call (~40s) sering melebihi
        // PHP default 60s. Backup guard juga di service agar aman di-call dari
        // console command atau queue job yang mungkin melewatkan controller.
        set_time_limit(300);

        $originalName = $file->getClientOriginalName();
        $safeName = Str::random(20) . '-' . Str::slug(pathinfo($originalName, PATHINFO_FILENAME)) . '.pdf';

        // Persist to storage/app/strategic-advisor/ (private disk).
        $storedPath = $file->storeAs('strategic-advisor', $safeName, 'local');
        if (! $storedPath) {
            throw new \RuntimeException('Gagal menyimpan file upload. Cek permission storage/app/strategic-advisor.');
        }

        $record = StrategicRecommendation::create([
            'user_id'     => $userId,
            'source_file' => $originalName,
            'file_path'   => $storedPath,
            'status'      => 'processing',
        ]);

        $absPath = Storage::disk('local')->path($storedPath);

        try {
            // 1) Extract structure. Batasi 200 halaman pertama untuk menjaga
            // waktu ekstraksi tetap di bawah ~15s — section strategis
            // (visi/misi/KPI/inisiatif) lazim berada di bab awal dokumen.
            $dto = $this->extractor->extract($absPath, maxPages: 200);

            $record->update([
                'document_type' => $dto->documentType,
                'company'       => $dto->company,
                'period'         => $dto->period,
                'total_pages'   => $dto->totalPages,
                'extraction_json' => $this->compactExtraction($dto),
            ]);

            if ($dto->errorMessage) {
                Log::warning('StrategicAdvisor: extraction reported non-fatal error', [
                    'record_id' => $record->id,
                    'error'     => $dto->errorMessage,
                ]);
            }

            // 2) Build prompt & call Gemini WITH grounding
            $prompt  = $this->promptManager->generateStrategicAdvisorPrompt($dto);
            $started = microtime(true);
            $result  = $this->gemini->analyzeWithSearch($prompt);
            $elapsed = round(microtime(true) - $started, 2);

            if (! ($result['success'] ?? false)) {
                $record->update([
                    'status'           => 'failed',
                    'error_message'    => $result['error'] ?? 'Gemini call failed (unknown reason).',
                    'processing_time'  => $result['processing_time'] ?? $elapsed,
                    'raw_response_json' => $result['raw_response'] ?? null,
                ]);

                Log::error('StrategicAdvisor: Gemini call failed', [
                    'record_id' => $record->id,
                    'error'     => $result['error'] ?? null,
                ]);

                return $record->fresh();
            }

            $data = $result['data'] ?? [];
            $grounded = (bool) ($result['grounded'] ?? false);
            $fallbackReason = $result['fallback_reason'] ?? null;

            $record->update([
                'analysis'                 => $this->takeString($data['analysis'] ?? null),
                'recommendations_json'     => $this->takeArray($data['recommendations'] ?? null),
                'popular_trends_json'      => $this->takeArray($data['popular_trends'] ?? null),
                'perspective_coverage_json'=> null,
                'matched_kpis_json'        => null,
                'matched_initiatives_json' => null,
                'raw_response_json'        => $result['raw_response'] ?? null,
                'status'                   => 'completed',
                'grounded'                 => $grounded,
                'error_message'            => $fallbackReason, // null bila grounded, disclaimer bila fallback
                'processing_time'          => $result['processing_time'] ?? $elapsed,
            ]);

            Log::info('StrategicAdvisor: completed', [
                'record_id' => $record->id,
                'grounded'  => $grounded,
                'recs'      => count($record->fresh()->recommendations_json ?? []),
                'trends'    => count($record->fresh()->popular_trends_json ?? []),
            ]);

            return $record->fresh();
        } catch (Throwable $e) {
            Log::error('StrategicAdvisor: exception', [
                'record_id' => $record->id,
                'error'     => $e->getMessage(),
                'trace'     => Str::limit($e->getTraceAsString(), 1500),
            ]);

            $record->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return $record->fresh();
        }
    }

    /** Delete a record and its uploaded file. */
    public function delete(StrategicRecommendation $record): void
    {
        if ($record->file_path) {
            Storage::disk('local')->delete($record->file_path);
        }
        $record->delete();
    }

    /**
     * Compact extraction for persistence — strips large free-text sections
     * (kept in the prompt only) and keeps just the structured arrays + small
     * metadata, to keep the column size reasonable.
     */
    protected function compactExtraction(DocumentExtractionDTO $dto): array
    {
        return [
            'document_type'       => $dto->documentType,
            'company'             => $dto->company,
            'period'              => $dto->period,
            'source_file'         => $dto->sourceFile,
            'total_pages'         => $dto->totalPages,
            'toc'                 => $dto->toc,
            'kpis'                => $dto->kpis,
            'initiatives'        => $dto->initiatives,
            'strategic_objectives'=> $dto->strategicObjectives,
            'metrics'             => $dto->metrics,
            'executive_summary'   => Str::limit($dto->executiveSummary ?? '', 1200),
            'error_message'       => $dto->errorMessage,
        ];
    }

    protected function takeString(mixed $v): ?string
    {
        if (is_string($v) && trim($v) !== '') {
            return $v;
        }
        return null;
    }

    protected function takeArray(mixed $v): ?array
    {
        return is_array($v) && ! empty($v) ? $v : null;
    }
}