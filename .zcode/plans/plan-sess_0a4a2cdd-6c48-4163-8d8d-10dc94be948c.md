## Plan: Fix Realisasi Aggregation & Improve AI Insight Specificity

### Masalah 1: Realisasi tidak ter-update saat upload evidence ke-2

**Root Cause:** `EvidenceService::updateRealisasi()` menggunakan `updateOrCreate()` yang **menimpa (overwrite)** nilai sebelumnya, bukan menambahkan.

**Solusi:** Ubah logic menjadi **SUM** semua `realisasi` dari `ai_results` yang valid untuk measurement+quarter+year yang sama.

### Perubahan:

#### 1. `app/Services/EvidenceService.php`
- Ubah method `updateRealisasi()`:
  - Setelah menyimpan `AiResult` baru, query **semua** `AiResult` yang valid (`evidence_valid = true`) untuk measurement+quarter+year tersebut
  - **SUM** kolom `realisasi` dari semua `AiResult` valid tersebut
  - Simpan total SUM ke tabel `realisasi`

```php
protected function updateRealisasi(Upload $upload, float $value): Realisasi
{
    $totalRealisasi = AiResult::whereHas('upload', fn($q) => $q
        ->where('measurement_id', $upload->measurement_id)
        ->where('quarter', $upload->quarter)
        ->where('year', $upload->year))
    ->where('evidence_valid', true)
    ->sum('realisasi');

    return Realisasi::updateOrCreate(
        ['measurement_id' => ..., 'quarter' => ..., 'year' => ...],
        ['value' => $totalRealisasi, 'source' => 'ai']
    );
}
```

#### 2. `app/Services/InsightService.php`
- Ubah method `latestEvidenceContext()` → `allEvidenceContext()`:
  - Query **semua** `AiResult` valid (bukan hanya yang terbaru)
  - Gabungkan analysis dari semua evidence menjadi satu blok teks
  - Setiap evidence dilabeli dengan nama file dan initiative yang matched
  - Kirim blok gabungan ini ke prompt insight

```php
protected function allEvidenceContext(int $measurementId, string $quarter, int $year): array
{
    $aiResults = AiResult::whereHas('upload', fn($q) => $q
        ->where('measurement_id', $measurementId)
        ->where('quarter', $quarter)
        ->where('year', $year))
    ->where('evidence_valid', true)
    ->latest()
    ->get();

    if ($aiResults->isEmpty()) return ['', ''];

    $combinedAnalysis = $aiResults->map(fn($r) => 
        "[{$r->upload->file_name}] Initiative: {$r->matched_initiative}\n{$r->analysis}"
    )->join("\n\n---\n\n");

    return [$combinedAnalysis, $aiResults->first()->matched_initiative];
}
```

#### 3. `app/AI/PromptManager.php`
- Tambahkan instruksi di `generateInsightPrompt()` agar AI menjelaskan **status per aplikasi/tema** berdasarkan setiap evidence:
  - Jika evidence menunjukkan masih UAT → sebutkan: "Aplikasi X masih dalam tahap UAT"
  - Jika evidence menunjukkan go-live → sebutkan: "Aplikasi Y sudah go-live"
  - Jika ada gap → berikan rekomendasi spesifik: "Perlu dokumen Z untuk melengkapi evidence Aplikasi X"
- Tambahkan instruksi: "Beri analisis PER EVIDENCE/APLIKASI yang spesifik, bukan general"

### Files yang diubah:
1. `app/Services/EvidenceService.php` — logic SUM realisasi
2. `app/Services/InsightService.php` — gabungkan semua evidence context
3. `app/AI/PromptManager.php` — perbaiki prompt insight agar lebih spesifik

### Tidak perlu:
- Migration baru (struktur tabel sudah cukup)
- Perubahan di ReportController/KPIMonitoringController (sudah pakai `realisasi.value` yang akan ter-update)