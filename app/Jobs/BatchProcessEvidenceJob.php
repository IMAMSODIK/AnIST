<?php

namespace App\Jobs;

use App\Models\Upload;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class BatchProcessEvidenceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 600;

    public function __construct(
        public ?string $quarter = null,
        public ?int $year = null,
    ) {}

    public function handle(): void
    {
        Log::info('Starting batch evidence processing', [
            'quarter' => $this->quarter,
            'year' => $this->year,
        ]);

        $query = Upload::where('status', 'pending');

        if ($this->quarter) {
            $query->where('quarter', $this->quarter);
        }

        if ($this->year) {
            $query->where('year', $this->year);
        }

        $pendingUploads = $query->get();

        foreach ($pendingUploads as $upload) {
            ProcessEvidenceJob::dispatch($upload)
                ->onQueue('evidence');
        }

        Log::info('Batch evidence processing dispatched', [
            'count' => $pendingUploads->count(),
        ]);
    }
}
