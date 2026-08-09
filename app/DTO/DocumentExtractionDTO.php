<?php

namespace App\DTO;

/**
 * Hasil ekstraksi dokumen strategis (RJPP / MPTI) oleh DocumentExtractorService.
 *
 * Objek ini bersifat non-sensitif: hanya berisi struktur & ringkasan informasi
 * strategis yang relevan untuk dikirim ke Gemini. Data numerik, nama orang,
 * dan angka spesifik tidak ikut dipertahankan secara verbatim.
 */
class DocumentExtractionDTO
{
    public function __construct(
        public readonly string $documentType,
        public readonly string $company,
        public readonly ?string $period,
        public readonly string $sourceFile,
        public readonly int $totalPages,
        public readonly array $toc,
        public readonly array $sections,
        public readonly array $kpis,
        public readonly array $initiatives,
        public readonly array $strategicObjectives,
        public readonly array $metrics,
        public readonly ?string $executiveSummary,
        public readonly ?string $errorMessage = null,
    ) {}

    public function toArray(): array
    {
        return [
            'document_type' => $this->documentType,
            'company' => $this->company,
            'period' => $this->period,
            'source_file' => $this->sourceFile,
            'total_pages' => $this->totalPages,
            'toc' => $this->toc,
            'sections' => $this->sections,
            'kpis' => $this->kpis,
            'initiatives' => $this->initiatives,
            'strategic_objectives' => $this->strategicObjectives,
            'metrics' => $this->metrics,
            'executive_summary' => $this->executiveSummary,
            'error_message' => $this->errorMessage,
        ];
    }

    public function toJson(int $options = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE): string|false
    {
        return json_encode($this->toArray(), $options);
    }

    /** Estimasi token kasar (~4 karakter per token) untuk budgeting prompt Gemini. */
    public function estimatedTokens(): int
    {
        $text = json_encode($this->toArray(), JSON_UNESCAPED_UNICODE) ?: '';

        return (int) ceil(strlen($text) / 4);
    }
}