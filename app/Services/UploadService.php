<?php

namespace App\Services;

use App\Models\AuditTrail;
use App\Models\Upload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadService
{
    public function __construct(
        protected EvidenceService $evidenceService,
        protected KPIService $kpiService,
    ) {}

    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'image/jpeg',
        'image/jpg',
        'image/png',
    ];

    protected int $maxFileSize = 10485760;

    public function handleUpload(UploadedFile $file, int $measurementId, string $quarter, int $year): Upload
    {
        $this->validateFile($file);

        $filename = $this->generateFilename($file);
        $path = "evidence/{$year}/{$quarter}";

        $storedPath = $file->storeAs($path, $filename, 'local');

        $upload = Upload::create([
            'measurement_id' => $measurementId,
            'quarter' => $quarter,
            'year' => $year,
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $storedPath,
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by' => Auth::id(),
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        AuditTrail::create([
            'user_id' => Auth::id(),
            'action' => 'upload_evidence',
            'model_type' => Upload::class,
            'model_id' => $upload->id,
            'new_values' => [
                'file_name' => $upload->file_name,
                'measurement_id' => $measurementId,
                'quarter' => $quarter,
                'year' => $year,
            ],
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return $upload;
    }

    protected function validateFile(UploadedFile $file): void
    {
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            throw new \InvalidArgumentException(
                'File type not allowed. Allowed types: PDF, DOCX, XLSX, JPG, JPEG, PNG'
            );
        }

        if ($file->getSize() > $this->maxFileSize) {
            throw new \InvalidArgumentException(
                'File size exceeds maximum limit of ' . ($this->maxFileSize / 1048576) . 'MB'
            );
        }
    }

    protected function generateFilename(UploadedFile $file): string
    {
        return Str::uuid() . '.' . $file->getClientOriginalExtension();
    }

    public function deleteUpload(Upload $upload): bool
    {
        $measurementId = $upload->measurement_id;
        $quarter = $upload->quarter;
        $year = $upload->year;

        if (Storage::disk('local')->exists($upload->file_path)) {
            Storage::disk('local')->delete($upload->file_path);
        }

        if ($upload->aiResult) {
            $upload->aiResult->delete();
        }

        AuditTrail::create([
            'user_id' => Auth::id(),
            'action' => 'delete_evidence',
            'model_type' => Upload::class,
            'model_id' => $upload->id,
            'old_values' => $upload->toArray(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        $deleted = $upload->delete();

        // After removing the evidence, the previously aggregated realisasi is
        // now stale (it still includes the deleted evidence's contribution).
        // Recompute realisasi and downstream KPI score + insight so the
        // dashboard always reflects the remaining evidence.
        try {
            $this->evidenceService->recalculateRealisasi($measurementId, $quarter, $year);

            $this->kpiService->calculateScore($measurementId, $quarter, $year);

            // Refresh the narrative insight (non-blocking on failure).
            $this->evidenceService->refreshInsight($measurementId, $quarter, $year);
        } catch (\Throwable $e) {
            Log::warning('Post-delete realisasi/KPI recalculation failed (suppressed)', [
                'measurement_id' => $measurementId,
                'quarter' => $quarter,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);
        }

        return $deleted;
    }

    public function getAllowedMimeTypes(): array
    {
        return $this->allowedMimeTypes;
    }

    public function getMaxFileSizeMB(): int
    {
        return $this->maxFileSize / 1048576;
    }
}
