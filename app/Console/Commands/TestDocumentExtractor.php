<?php

namespace App\Console\Commands;

use App\Services\DocumentExtractorService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class TestDocumentExtractor extends Command
{
    protected $signature = 'document:extract
        {path : Path PDF relatif terhadap base_path() atau absolute}
        {--out= : Path file output JSON (opsional)}
        {--raw= : Path file output raw text hasil pdftotext (opsional)}
        {--debug : Tampilkan 3000 karakter pertama raw text hasil pdftotext}';

    protected $description = 'Uji DocumentExtractorService pada dokumen PDF strategis (RJPP/MPTI) dan tampilkan ringkasan hasil.';

    public function handle(DocumentExtractorService $service): int
    {
        $path = $this->argument('path');
        $debug = (bool) $this->option('debug');
        $rawOut = $this->option('raw');

        if ($debug || $rawOut) {
            $absPath = realpath(base_path($path)) ?: (file_exists($path) ? $path : null);
            if (! $absPath || ! file_exists($absPath)) {
                $this->error("File tidak ditemukan: {$path}");

                return self::FAILURE;
            }
            $raw = $service->extractRawText($absPath);
            if ($rawOut) {
                $absRaw = base_path($rawOut);
                @mkdir(dirname($absRaw), 0777, true);
                file_put_contents($absRaw, $raw);
                $this->info("Raw text ditulis ke: {$absRaw} (".strlen($raw)." bytes)");
            }
            if ($debug) {
                $this->newLine();
                $this->line('<fg=yellow>== Raw text (3000 karakter pertama) ==</fg>');
                $this->line(mb_substr($raw, 0, 3000));
                $this->newLine();
                $this->line('<fg=yellow>== Raw text (3000 karakter setelah TODO region) ==</fg>');
                $this->line(mb_substr($raw, 3000, 3000));
            }
        }

        $this->info("Ekstraksi dokumen: {$path}");
        $start = microtime(true);

        $dto = $service->extract($path);

        $elapsed = round((microtime(true) - $start) * 1000);

        if ($dto->errorMessage) {
            $this->error('Gagal: '.$dto->errorMessage);

            return self::FAILURE;
        }

        $this->newLine();
        $company = $dto->company ?: '(tidak terdetect)';
        $period = $dto->period ?: '(tidak terdetect)';
        $summary = $dto->executiveSummary ?: '(tidak terdeteksi)';
        $this->line("<fg=cyan>Dokumen :</fg> {$dto->documentType}");
        $this->line("<fg=cyan>Perusahaan :</fg> {$company}");
        $this->line("<fg=cyan>Periode :</fg> {$period}");
        $this->line("<fg=cyan>Halaman :</fg> {$dto->totalPages}");
        $this->line("<fg=cyan>Sumber :</fg> {$dto->sourceFile}");
        $this->line('<fg=cyan>TOC entries :</fg> '.count($dto->toc));
        $this->line('<fg=cyan>KPI terdeteksi :</fg> '.count($dto->kpis));
        $this->line('<fg=cyan>Inisiatif terdeteksi :</fg> '.count($dto->initiatives));
        $this->line('<fg=cyan>Sasaran strategis :</fg> '.count($dto->strategicObjectives));
        $this->line('<fg=cyan>Metric pool :</fg> '.count($dto->metrics));
        $this->line('<fg=cyan>Section terkumpul :</fg> '.count($dto->sections));
        $this->line('<fg=cyan>Estimasi token :</fg> '.number_format($dto->estimatedTokens()));
        $this->line("<fg=cyan>Waktu ekstraksi :</fg> {$elapsed} ms");

        $this->newLine();
        $this->line('<fg=yellow>== Daftar TOC (10 pertama) ==</fg>');
        foreach (array_slice($dto->toc, 0, 10) as $e) {
            $this->line("  {$e['code']} :: {$e['title']} (hal. {$e['page']})");
        }
        if (count($dto->toc) > 10) {
            $this->line('  ... dan '.(count($dto->toc) - 10).' entry lainnya');
        }

        $this->newLine();
        $this->line('<fg=yellow>== KPI terdeteksi (5 contoh) ==</fg>');
        foreach (array_slice($dto->kpis, 0, 5) as $k) {
            $this->line("  [{$k['code']}] {$k['measurement']} (unit: {$k['unit']}, target: {$k['target']})");
        }
        if (count($dto->kpis) > 5) {
            $this->line('  ... dan '.(count($dto->kpis) - 5).' KPI lainnya');
        }

        $this->newLine();
        $this->line('<fg=yellow>== Inisiatif terdeteksi (5 contoh) ==</fg>');
        foreach (array_slice($dto->initiatives, 0, 5) as $i) {
            $code = $i['code'] ?: '—';
            $this->line("  [{$code}] {$i['name']}");
        }
        if (count($dto->initiatives) > 5) {
            $this->line('  ... dan '.(count($dto->initiatives) - 5).' inisiatif lainnya');
        }

        $this->newLine();
        $this->line('<fg=yellow>== Ringkasan Eksekutif (preview) ==</fg>');
        $summary = $dto->executiveSummary ?: '(tidak terdeteksi)';
        $this->line(Str::limit($summary, 500));
        if (strlen($summary) > 500) {
            $this->line('  ... ('.(strlen($summary) - 500).' karakter lagi)');
        }

        $outFile = $this->option('out');
        if ($outFile) {
            $absOut = base_path($outFile);
            $dir = dirname($absOut);
            if (! is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            file_put_contents($absOut, $dto->toJson());
            $this->newLine();
            $this->info("Hasil lengkap ditulis ke: {$absOut}");
        }

        return self::SUCCESS;
    }
}