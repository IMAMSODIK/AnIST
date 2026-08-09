<?php

namespace App\Services;

use App\DTO\DocumentExtractionDTO;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\PdfToText\Pdf;
use Throwable;

/**
 * DocumentExtractorService
 * -------------------------
 * Bertanggung jawab mengekstrak informasi strategis non-sensitif dari
 * dokumen referensi (RJPP / MPTI) yang berukuran ribuan halaman menjadi
 * ringkasan terstruktur yang siap dikirim ke Gemini.
 *
 * Tahapan:
 *  1. Konversi PDF -> text via `pdftotext` (poppler) melalui wrapper Spatie.
 *  2. Defragmentasi paragraf yang terpotong oleh page break.
 *  3. Parsing Table of Contents (Daftar Isi) untuk memetakan section relevan.
 *  4. Heuristik deteksi tipe dokumen (RJPP vs MPTI).
 *  5. Ekstraksi section relevan:
 *       - Sasaran Strategis / Tujuan Jangka Panjang
 *       - Indikator Kinerja Kunci / KPI
 *       - Inisiatif Strategis / PTI
 *       - Ringkasan Eksekutif
 *  6. Sanitasi: hapus nama orang, nominal angka spesifik, nomor regulasi
 *     tertentu untuk meminimalkan exposure data sensitif.
 *
 * Output: DocumentExtractionDTO (non-sensitif) siap kirim ke PromptManager.
 */
class DocumentExtractorService
{
    /** Headers section yang akan diekstrak isinya (lowercase, dipakai sebagai substring match). */
    protected const RELEVANT_SECTION_HEADERS = [
        'ringkasan eksekutif',
        'sasaran strategis',
        'tujuan jangka panjang',
        'indikator kinerja',
        'indikator kinerja kunci',
        'kpi',
        'inisiatif strategis',
        'inisiatif pti',
        'arah pti',
        'arah strategi',
        'rencana strategis',
        'visi dan misi',
        'visi & misi',
    ];

    /** Marker tipe dokumen pada baris awal / title halaman. */
    protected const RJPP_MARKERS = ['rencana jangka panjang perusahaan', 'rjpp'];
    protected const MPTI_MARKERS = ['master plan teknologi informasi', 'master plan ti', 'mpti'];

    /** Path absolut binary pdftotext (dapat di-override via config). */
    protected ?string $pdfBinary = null;

    public function __construct()
    {
        $this->pdfBinary = $this->resolvePdfBinary();
    }

    /** Extract dari path file PDF di disk (absolute atau relatif storage).
     *  @param int $maxPages  Limitasi jumlah halaman yang dibaca pdftotext (0 = tanpa limit).
     *                        Berguna untuk Strategic Advisor yang targetnya hanya section
     *                        strategis awal dokumen (visi/misi/KPI/inisiatif), agar ekstraksi
     *                        dokumen 200-1000 halaman tidak memakan 30+ detik. */
    public function extract(string $pdfPath, int $maxPages = 0): DocumentExtractionDTO
    {
        $absPath = $this->resolvePath($pdfPath);

        try {
            if (! is_file($absPath)) {
                throw new \RuntimeException("File tidak ditemukan: {$absPath}");
            }

            $text = $this->pdfToText($absPath, $maxPages);
            $text = $this->defragment($text);
            $totalPages = $this->countPdfPages($absPath);

            $documentType = $this->detectDocumentType($text);
            $period = $this->extractPeriod($text);
            $company = $this->extractCompany($text);
            $toc = $this->parseTableOfContents($text);
            $executiveSummary = $this->extractSection($text, 'ringkasan eksekutif', 2500);

            $kpiText = $this->extractSectionAny($text, ['indikator kinerja kunci', 'kpi', 'indikator kinerja'], 9000) ?? '';
            $initiativeText = $this->extractSectionAny($text, ['inisiatif strategis', 'inisiatif pti', 'arah pti'], 9000) ?? '';
            $soText = $this->extractSectionAny($text, ['sasaran strategis', 'tujuan jangka panjang', 'rencana strategis'], 5000) ?? '';

            $sections = array_filter([
                'executive_summary' => $executiveSummary,
                'kpi' => $kpiText,
                'initiatives' => $initiativeText,
                'strategic_objectives' => $soText,
            ], fn ($v) => ! empty($v));

            Log::debug('DocumentExtractor pipeline', [
                'exec_summary_len' => strlen($executiveSummary ?? ''),
                'kpi_text_len' => strlen($kpiText ?? ''),
                'initiative_text_len' => strlen($initiativeText ?? ''),
                'so_text_len' => strlen($soText ?? ''),
                'sections_count' => count($sections),
            ]);

            $kpis = $this->parseKpis($kpiText);
            $initiatives = $this->parseInitiatives($initiativeText);
            $strategicObjectives = $this->parseStrategicObjectives($soText);
            $metrics = array_unique(array_merge(
                array_column($kpis, 'measurement'),
                array_column($kpis, 'unit'),
            ));

            return new DocumentExtractionDTO(
                documentType: $documentType,
                company: $company,
                period: $period,
                sourceFile: basename($absPath),
                totalPages: $totalPages,
                toc: $toc,
                sections: $sections,
                kpis: $kpis,
                initiatives: $initiatives,
                strategicObjectives: $strategicObjectives,
                metrics: array_values(array_filter($metrics)),
                executiveSummary: $executiveSummary,
            );
        } catch (Throwable $e) {
            Log::error('DocumentExtractor error', [
                'path' => $absPath,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1000),
            ]);

            return new DocumentExtractionDTO(
                documentType: 'unknown',
                company: '',
                period: null,
                sourceFile: basename($absPath),
                totalPages: 0,
                toc: [],
                sections: [],
                kpis: [],
                initiatives: [],
                strategicObjectives: [],
                metrics: [],
                executiveSummary: null,
                errorMessage: $e->getMessage(),
            );
        }
    }

    // ---------- pipeline ----------

    /** Konversi PDF ke text murni. Pakai -layout untuk mempertahankan tabel.
     *  @param int $maxPages  0 = tanpa limit. >0 pass `-l <n>` agar pdftotext
     *                        berhenti setelah halaman ke-<n>. */
    protected function pdfToText(string $absPath, int $maxPages = 0): string
    {
        // Spatie wrapper: setiap elemen array di-prefix dengan "-".
        // Untuk pasangan flag+value (mis. -enc UTF-8), gunakan satu string
        // "enc UTF-8" agar prefix "-" hanya menempel pada flag.
        $options = [
            'layout',
            'enc UTF-8',
        ];
        if ($maxPages > 0) {
            $options[] = 'l ' . (int) $maxPages;
        }

        if ($this->pdfBinary) {
            return Pdf::getText($absPath, $this->pdfBinary, $options);
        }

        return Pdf::getText($absPath, null, $options);
    }

    /** Hitung jumlah halaman PDF via pdfinfo jika tersedia, fallback heuristik. */
    protected function countPdfPages(string $absPath): int
    {
        $pdfinfo = $this->resolveBinary('pdfinfo');
        if ($pdfinfo) {
            $cmd = '"'.$pdfinfo.'" "'.$absPath.'"';
            $output = @shell_exec($cmd.' 2>&1');
            if ($output && preg_match('/Pages:\s+(\d+)/i', $output, $m)) {
                return (int) $m[1];
            }
        }

        return 0;
    }

    /** Defragmentasi paragraf yang dipotong page break.
     *  pdftotext memakai 0x0C (form feed) sebagai delimiter halaman, yang TIDAK
     *  di-strip oleh trim() bawaan. Kita pecah menjadi halaman, lalu kumpulkan
     *  baris dengan trim yang inklusif terhadap 0x0C. */
    protected function defragment(string $text): string
    {
        // Normalisasi line endings dulu, lalu ganti 0x0C dengan newline.
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = str_replace("\x0C", "\n", $text);

        $lines = explode("\n", $text);
        $trimMask = " \t\n\r\0\x0B\x0C";
        $out = [];
        foreach ($lines as $line) {
            $line = trim($line, $trimMask);
            // hapus halaman nomor murni (1-3 digit saja di baris kosong)
            if (preg_match('/^\s*\d{1,3}\s*$/', $line) && empty($out)) {
                continue;
            }
            if ($line === '') {
                $out[] = '';
                continue;
            }
            $out[] = $line;
        }
        $text = implode("\n", $out);

        return $text;
    }

    /** Deteksi tipe dokumen berdasarkan kata kunci di awal konten. */
    protected function detectDocumentType(string $text): string
    {
        $head = Str::lower(mb_substr($text, 0, 2000));
        $rjppHit = 0;
        $mptiHit = 0;
        foreach (self::RJPP_MARKERS as $m) {
            if (str_contains($head, $m)) {
                $rjppHit++;
            }
        }
        foreach (self::MPTI_MARKERS as $m) {
            if (str_contains($head, $m)) {
                $mptiHit++;
            }
        }
        if ($rjppHit > 0 && $mptiHit === 0) {
            return 'RJPP';
        }
        if ($mptiHit > 0 && $rjppHit === 0) {
            return 'MPTI';
        }
        if ($mptiHit > $rjppHit) {
            return 'MPTI';
        }
        if ($rjppHit >= $mptiHit && $rjppHit > 0) {
            return 'RJPP';
        }

        return 'unknown';
    }

    /** Extract periode 4 digit-4 digit (mis. 2025-2029). */
    protected function extractPeriod(string $text): ?string
    {
        $head = mb_substr($text, 0, 2000);
        if (preg_match('/(19|20)\d{2}\s*[-–]\s*(19|20)\d{2}/', $head, $m)) {
            return trim($m[0]);
        }

        return null;
    }

    /** Extract nama perusahaan: cari "PT ... (Persero)" atau "PT ... (Persero)" di awal. */
    protected function extractCompany(string $text): string
    {
        $head = mb_substr($text, 0, 3000);
        if (preg_match('/PT\.?\s+[A-Z][A-Z0-9\s&\.]+(?:\s*\(PERSERO\)|\(Persero\))?/u', $head, $m)) {
            return trim(preg_replace('/\s+/', ' ', $m[0]));
        }

        return '';
    }

    /** Parsing TOC sederhana: baris berpola "BAB ... <title> <number>" atau "X.Y <title> <number>". */
    protected function parseTableOfContents(string $text): array
    {
        $toc = [];
        $lines = explode("\n", $text);
        $inToc = false;
        $tocEndMarkerIdx = -1;
        foreach ($lines as $i => $line) {
            $orig = trim($line);
            $lower = Str::lower($orig);
            if ($lower === 'daftar isi') {
                $inToc = true;
                continue;
            }
            if (! $inToc) {
                continue;
            }

            // akhir daftar isi: terlihat header BAB I / kata "BAB I PENDAHULUAN" yang tidak memiliki nomor halaman di akhir
            if ($inToc && preg_match('/^BAB\s+[IVX]+\s+/i', $orig) && ! preg_match('/\s+\d{1,4}\s*$/', $orig) && count($toc) > 3) {
                $tocEndMarkerIdx = $i;
                break;
            }

            // Match: "BAB I" / "1.1" / "LAMPIRAN A" + judul + nomor halaman
            // 1) "BAB I PENDAHULUAN ... 9"
            if (preg_match('/^(BAB\s+[IVXLCDM]+|LAMPIRAN\s+[A-Z])\s+(.+?)\s+(\d{1,4})\s*$/u', $orig, $m)) {
                $toc[] = ['code' => $m[1], 'title' => trim($m[2]), 'page' => (int) $m[3]];
                continue;
            }
            // 2) "1.1 Latar Belakang 9"
            if (preg_match('/^(\d+\.\d+)\s+(.+?)\s+(\d{1,4})\s*$/u', $orig, $m)) {
                $toc[] = ['code' => $m[1], 'title' => trim($m[2]), 'page' => (int) $m[3]];
                continue;
            }
        }
        // filter entry non relevan yang judul lebih dari 80 karakter (noise)
        $toc = array_values(array_filter($toc, fn ($e) => strlen($e['title']) <= 120 && strlen($e['title']) >= 2));

        return $toc;
    }

    /** Extract konten sebuah section berdasarkan header (lowercase, partial match). */
    protected function extractSection(string $text, string $headerLower, int $maxChars = 5000): ?string
    {
        $text = $this->extractSectionAny($text, [$headerLower], $maxChars);

        return $text;
    }

    /** Extract section dengan mencoba beberapa header sekaligus. */
    protected function extractSectionAny(string $text, array $headerLowers, int $maxChars = 5000): ?string
    {
        $lines = explode("\n", $text);

        foreach ($headerLowers as $headerLower) {
            $headerLower = Str::lower($headerLower);
            $startIdx = null;
            foreach ($lines as $i => $line) {
                $cleanLine = trim($line);
                if ($cleanLine === '' || strlen($cleanLine) > 80) {
                    continue;
                }
                $lower = Str::lower($cleanLine);
                if (! Str::contains($lower, $headerLower)) {
                    continue;
                }
                // WAJIB: baris harus berupa section heading (memiliki prefix
                // kode: "6.4", "BAB VI", "LAMPIRAN B", atau kata kapital
                // penuh). Ini untuk menghindari match pada paragraf narasi.
                $isHeading = preg_match('/^(\d+\.\d+|BAB\s+[IVXLCDM]+|LAMPIRAN\s+[A-Z])\s+/i', $cleanLine)
                    || preg_match('#^[A-Z][A-Z\s,&()./-]{6,}$#u', $cleanLine);
                if (! $isHeading) {
                    continue;
                }
                // SKIP entri Daftar Isi (TOC): heading TOC selalu berakhir dgn
                // nomor halaman (mis. "6.4   Indikator Kinerja Kunci (KPI) 127").
                // Heading body sebenarnya TIDAK punya trailing page number.
                if (preg_match('/\s+\d{1,4}\s*$/', $cleanLine)) {
                    continue;
                }
                $startIdx = $i;
                break;
            }
            if ($startIdx === null) {
                continue;
            }

            // ambil paragraf hingga header berikutnya berawalan BAB / angka X.Y / halaman baru mencolok
            $buf = [];
            $collected = 0;
            for ($j = $startIdx + 1; $j < count($lines); $j++) {
                $line = trim($lines[$j]);
                if ($line === '') {
                    continue;
                }
                // berhenti jika menemui header BAB / sub-bab tegas berikutnya
                if (preg_match('/^BAB\s+[IVXLCDM]+\s+/i', $line)) {
                    break;
                }
                if (preg_match('/^\d+\.\d+\s+[A-Z]/', $line)) {
                    break;
                }
                if (preg_match('/^LAMPIRAN\s+[A-Z]/i', $line)) {
                    break;
                }
                $buf[] = $line;
                $collected += strlen($line);
                if ($collected >= $maxChars) {
                    break;
                }
            }
            $content = implode("\n", $buf);
            $content = $this->sanitizeSection($content);
            $content = Str::limit($content, $maxChars);

            return $content;
        }

        return null;
    }

    /**
     * Sanitasi konten section sebelum disimpan/dikirim ke AI:
     *  - hapus nama orang mistar berulang ("(__________________________)")
     *  - hapus whitespace baris baru berlebih.
     *  Catatan: 2+ space DIKEEP agar parser tabel parseKpis masih bisa
     *  membedakan kolom dari output pdftotext -layout.
     */
    protected function sanitizeSection(string $content): string
    {
        $content = preg_replace('/_{5,}/', '', $content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim($content);
    }

    /** Parse baris KPI. Pola tabel mpdf 7-kolom yang sering terlipat wrap.
     *  Strategi: kumpulkan baris yang dimulai dengan "KPI-XXX-NN" + baris
     *  lanjutan hingga baris KPI berikutnya, lalu split per 2+ spasi.
     *
     *  Catatan: pdftotext -layout menempatkan *wrap text* cell measurement
     *  DI ATAS baris KPI yang sebenarnya (karena vertical baseline alignment).
     *  Oleh karena itu, baris non-KPI yang berada DI ANTARA dua baris KPI
     *  diatributkan ke KPI BERIKUTNYA (prepend), bukan ke KPI sebelumnya. */
    protected function parseKpis(string $kpiText): array
    {
        $kpis = [];
        if (empty($kpiText)) {
            return $kpis;
        }
        $lines = explode("\n", $kpiText);

        // Blokir baris-baris yang merupakan record KPI: algoritma prepend
        // untuk wrap text yang muncul di atas baris berawalan "KPI-XXX-NN".
        $blocks = [];
        $pendingWrap = [];
        $currentCode = null;
        $currentBuf = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            if (preg_match('/^(KPI-[A-Z]{3}-\d{2}|KPI-[A-Z]+\d+)/u', $line)) {
                if ($currentCode !== null) {
                    $blocks[] = ['code' => $currentCode, 'lines' => $currentBuf];
                }
                $spPos = strpos($line, ' ');
                $currentCode = $spPos === false ? $line : substr($line, 0, $spPos);
                // wrap text dari cell measurement KPI berikut -> prepend
                $currentBuf = array_merge(
                    $pendingWrap,
                    [trim(substr($line, strlen($currentCode)))],
                );
                $pendingWrap = [];
                continue;
            }
            if ($currentCode === null) {
                // baris header tabel ("Kode Perspective Measurement ...") - skip
                continue;
            }
            // berikutnya: bisa wrap dari KPI saat ini (ekor baris panjang) ATAU
            // wrap dari KPI berikutnya. Heuristik sederhana: anggap semua sebagai
            // wrap dari KPI berikutnya -> simpan ke pendingWrap.
            $pendingWrap[] = $line;
        }
        if ($currentCode !== null) {
            $blocks[] = ['code' => $currentCode, 'lines' => $currentBuf];
        }

        // Untuk setiap blok, gunakan \n + 2+ spasi sebagai pemisah cell. Jangan
        // ratakan seluruh whitespace (kolaps) sebelum split karena akan
        // menghapus separator kolom 2+ space dari output -layout.
        foreach ($blocks as $block) {
            $joined = implode("\n", $block['lines']);
            $cells = preg_split('/\s{2,}|\n+/', $joined);
            $cells = array_values(array_filter(array_map('trim', $cells), fn ($v) => $v !== ''));
            if (empty($cells)) {
                continue;
            }
            $code = $block['code'];
            // ambil measurement sebagai cell terpanjang selain kode
            $measurement = '';
            $unit = '';
            $target = '';
            $weight = '';
            // Temukan unit & target ujung kanan.
            // Urutan kolom PDF: ... | Unit | Target | Weight.
            // Cell terkanan = Weight, kedua-terkanan = Target, ketiga = Unit.
            $unitRegex = '/^(%|ratio|Hour|jam|Score|Index|count|jt|Rp|M|tCO2e|ppm|kWh\/lot)$/u';
            $numCount = 0;
            for ($k = count($cells) - 1; $k >= 1; $k--) {
                $c = trim($cells[$k]);
                $isNum = (bool) preg_match('/^\d+(\.\d+)?$/', $c);
                if ($isNum) {
                    $numCount++;
                    if ($numCount === 1) {
                        $weight = $c;          // terkanan
                    } elseif ($numCount === 2) {
                        $target = $c;          // kedua terkanan
                    }
                    continue;
                }
                if ($unit === '' && preg_match($unitRegex, $c)) {
                    $unit = $c;
                }
            }
            // Measurement = cell kedua atau ketiga (yang bukan kode) paling informatif.
            for ($k = 1; $k < count($cells); $k++) {
                $c = trim($cells[$k]);
                if (strlen($c) > strlen($measurement)) {
                    $measurement = $c;
                }
            }
            $kpis[] = [
                'code' => $code,
                'measurement' => $measurement,
                'unit' => $unit,
                'target' => $target,
                'weight' => $weight,
            ];
        }

        // deduplikasi by code
        $seen = [];
        $uniq = [];
        foreach ($kpis as $k) {
            $key = $k['code'] ?? '';
            if ($key === '' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniq[] = $k;
        }

        return $uniq;
    }

    /** Parse daftar inisiatif strategis. Toleransi format "IS-001 : Nama" atau numerik "1. Nama". */
    protected function parseInitiatives(string $initText): array
    {
        $initiatives = [];
        if (empty($initText)) {
            return $initiatives;
        }
        $lines = explode("\n", $initText);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            // Pola: "IS-001 : Implementasi ERP Core" / "PTI-001 : ..." / "1. Implementasi ..."
            if (preg_match('/^(IS|PTI)-?0*[0-9]{2,3}\s*[:\-]\s*(.+)$/u', $line, $m)) {
                $initiatives[] = ['code' => $m[1], 'name' => trim($m[2])];
                continue;
            }
            if (preg_match('/^\d+\.\s+(.+)$/', $line, $m) && strlen($m[1]) > 10 && count($initiatives) < 200) {
                $initiatives[] = ['code' => null, 'name' => trim($m[1])];
            }
        }
        // dedup
        $seen = [];
        $uniq = [];
        foreach ($initiatives as $i) {
            $key = Str::lower($i['name']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniq[] = $i;
        }

        return $uniq;
    }

    /** Parse daftar sasaran strategis (TJP / SS-... / bullet).
     *  Format kolom RJPP setelah kolaps spasi: "SS-01 Nama Sasaran Internal Process".
     *  Perspective (Internal Process / Customer / Financial / Learning) dipisahkan
     *  ke field tersendiri agar tidak ikut ke name. */
    protected function parseStrategicObjectives(string $soText): array
    {
        $sos = [];
        if (empty($soText)) {
            return $sos;
        }
        $perspectives = ['Internal Process', 'Customer', 'Financial', 'Learning & Growth', 'Learning'];
        $lines = explode("\n", $soText);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || strlen($line) < 8) {
                continue;
            }
            // pola "TJP-01: ..." / "SS-01: .."  / "SS-TI-01 ..."  / "SS-01 Nama ..."
            if (preg_match('/^((?:TJP|SS)(?:-TI)?-?0*\d{1,2})\s*[:\-\s]\s*(.+)$/u', $line, $m)) {
                $code = trim($m[1]);
                $name = trim($m[2]);
                $perspective = '';
                foreach ($perspectives as $p) {
                    if (preg_match('/\s+'.preg_quote($p, '/').'\s*$/ui', $name)) {
                        $perspective = $p;
                        $name = trim(preg_replace('/\s+'.preg_quote($p, '/').'\s*$/ui', '', $name));
                        break;
                    }
                }
                $sos[] = ['code' => $code, 'name' => $name, 'perspective' => $perspective];
                continue;
            }
            // pola numerik bullet "1. Meningkatkan ..."
            if (preg_match('/^\d+\.\s+[A-Z][^.]{20,}$/u', $line, $m) && count($sos) < 20) {
                $sos[] = ['code' => null, 'name' => trim($m[1]), 'perspective' => ''];
            }
        }
        $seen = [];
        $uniq = [];
        foreach ($sos as $s) {
            $key = Str::lower($s['name']);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $uniq[] = $s;
        }

        return $uniq;
    }

    // ---------- binary resolution ----------

    protected function resolvePdfBinary(): ?string
    {
        // 1) config override
        $configured = config('services.pdftotext.binary');
        if ($configured && $this->binaryWorks($configured)) {
            return $configured;
        }
        // 2) PATH
        $onPath = $this->resolveBinary('pdftotext');
        if ($onPath) {
            return $onPath;
        }
        // 3) heuristik lokasi Laragon
        $candidates = [
            'D:\laragon\bin\git\mingw64\bin\pdftotext.exe',
            'C:\Program Files\Git\mingw64\bin\pdftotext.exe',
            '/usr/bin/pdftotext',
            '/usr/local/bin/pdftotext',
        ];
        foreach ($candidates as $c) {
            if ($this->binaryWorks($c)) {
                return $c;
            }
        }

        return null;
    }

    protected function resolveBinary(string $name): ?string
    {
        $where = @shell_exec('where '.escapeshellarg($name).' 2>&1');
        if ($where && preg_match('/^(.+\.exe|.+\/[a-z_-]+)$/m', trim($where), $m)) {
            $candidate = trim($m[1]);
            if ($this->binaryWorks($candidate)) {
                return $candidate;
            }
        }
        // Fallback windows Get-Command
        $gcm = @shell_exec('powershell -NoProfile -Command "(Get-Command -Name '.escapeshellarg($name).' -ErrorAction SilentlyContinue).Source"');
        if ($gcm) {
            $candidate = trim($gcm);
            if ($candidate !== '' && $this->binaryWorks($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    protected function binaryWorks(string $path): bool
    {
        if (! file_exists($path)) {
            return false;
        }
        if (PHP_OS_FAMILY === 'Windows') {
            return true; // tidak bisa cek -version dengan aman, andalkan keberadaan file
        }
        $out = @shell_exec('"'.$path.'" -v 2>&1');

        return $out !== null && stripos($out, 'pdftotext') !== false;
    }

    protected function resolvePath(string $pdfPath): string
    {
        if (file_exists($pdfPath)) {
            return realpath($pdfPath) ?: $pdfPath;
        }
        $storage = $this->storagePublicPath($pdfPath);
        if (file_exists($storage)) {
            return realpath($storage) ?: $storage;
        }
        $abs = base_path($pdfPath);
        if (file_exists($abs)) {
            return realpath($abs) ?: $abs;
        }

        return $pdfPath;
    }

    protected function storagePublicPath(string $path): string
    {
        $root = storage_path('app/public');

        return rtrim($root, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.ltrim($path, DIRECTORY_SEPARATOR);
    }

    /** Public helper: dump raw text dari PDF (untuk keperluan debug command). */
    public function extractRawText(string $absPath): string
    {
        return $this->defragment($this->pdfToText($absPath));
    }
}