<?php

namespace App\Jobs;

use App\Models\Upload;
use App\Services\EvidenceService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;
    public int $backoff = 60;

    public function __construct(
        public Upload $upload,
    ) {}

    public function handle(EvidenceService $evidenceService): void
    {
        Log::info('Processing evidence', ['upload_id' => $this->upload->id]);

        try {
            $evidenceService->processEvidence($this->upload);
            Log::info('Evidence processed successfully', ['upload_id' => $this->upload->id]);
        } catch (\Exception $e) {
            Log::error('Evidence processing job failed', [
                'upload_id' => $this->upload->id,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Evidence processing job permanently failed', [
            'upload_id' => $this->upload->id,
            'error' => $exception->getMessage(),
        ]);

        $this->upload->update(['status' => 'failed']);
    }
}
