<?php

namespace Database\Seeders;

use App\Models\Measurement;
use App\Models\Target;
use Illuminate\Database\Seeder;

/**
 * Seeds per-quarter targets for the 8 OMTI 2026 KPIs (#1, 2, 3, 4, 5, 7, 8, 9)
 * extracted verbatim from docs/traceability/Draft OMTI 2026.pdf.
 *
 * Target columns in the PDF are laid out as "2026 (=annual), TW I, TW II, TW III".
 * We treat these as CUMULATIVE per-quarter targets — i.e. each quarter shows
 * the running total expected by end of that quarter, with Q4 cumulative =
 * the annual "2026" column. This matches:
 *   - #1 Penyelesaian: Q1=1, Q2=2, Q3=4 → cumulative; annual=7 → Q4=7.
 *   - #5 Realisasi Investasi: Q1=0, Q2=18.04, Q3=34.13 → cumulative; annual=65 → Q4=65.
 *   - #8 AI count: Q1=0, Q2=1, Q3=2 → cumulative; annual=3 → Q4=3.
 *   - #9 IT Maturity: biennial assessment; Q1=Q2=Q3=0 (no interim), Q4=3.85 (annual).
 *
 * Special cases:
 *   - #2 Cybersecurity Incident is zero-tolerance: target = 0 for every
 *     quarter and the formula is "lower is better" — ScoreCalculator already
 *     treats target=0 as zero-tolerance (realisasi=0 → achievement 100,
 *     any incident → achievement 0).
 *   - #3 Traceability and #7 EA use "Based on Project Charter" targets of
 *     100% per quarter because the PDF marks these cells as "Based on Project
 *     Charter" without a numeric annual value.
 *   - #4 Percepatan: PDF says "Hari 14 14 14 14" (cycle-time target = 14 days
 *     per quarter). The IT proxy used by this app is the SLA Aplikasi target
 *     (92%), which the evidence (docs/percepatan/SLA-*.pdf) measures. Both
 *     targets are kept: the SLA target Q1-Q4 = 92 (default 95%), so the
 *     SLA Network 98% / Aplikasi 92% terms in the definition drive the
 *     achievement computation. The 14-hari shared-KPI figure is preserved
 *     in the definition rather than the target column.
 */
class TargetSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        $targets = [
            // Pemenuhan Sertifikasi ISO 27001 (OMTI Depops #4) — annual 100,
            // TW I=0, TW II=0, TW III=80 (Pelaksanaan), Q4=100 (Lulus Audit).
            'Pemenuhan Sertifikasi Internasional ISO 27001' => ['Q1' => 0, 'Q2' => 0, 'Q3' => 80, 'Q4' => 100],

            // Implementasi Inisiatif RSTI (OMTI Depops #3) — annual 2,
            // cumulative 0/0/0/2 (TW I-III = 0, completion toward Q4).
            'Implementasi Inisiatif Rencana Strategis Teknologi Informasi (RSTI)' => ['Q1' => 0, 'Q2' => 0, 'Q3' => 0, 'Q4' => 2],

            // #1 Penyelesaian Implementasi — annual 7, cumulative 1/2/4/7.
            'Penyelesaian Implementasi Sistem Aplikasi Upgrade/Baru'       => ['Q1' => 1,    'Q2' => 2,     'Q3' => 4,     'Q4' => 7],

            // #2 Cybersecurity Incident — zero-tolerance.
            'Cybersecurity Incident'                                       => ['Q1' => 0,    'Q2' => 0,     'Q3' => 0,     'Q4' => 0],

            // #3 Traceability — Based on Project Charter (target 100% each Q).
            'Pencapaian Project Management: Traceability'                  => ['Q1' => 100,  'Q2' => 100,   'Q3' => 100,   'Q4' => 100],

            // #4 Percepatan pembayaran — sharing KPI: maksimum 14 hari siklus
            // pembayaran per triwulan (unit Hari, Lower is Better).
            'Percepatan proses pembayaran (sharing KPI)'                   => ['Q1' => 14,   'Q2' => 14,    'Q3' => 14,    'Q4' => 14],

            // #5 Realisasi Nilai Investasi — annual 65, cumulative 0/18.04/34.13/65.
            'Realisasi Nilai Investasi (KPI BP.BUMN)'                       => ['Q1' => 0,    'Q2' => 18.04, 'Q3' => 34.13, 'Q4' => 65],

            // #7 EA 3-stage — Based on Project Charter (100% per Q, since
            // each project BAST means 100% achievement against its charter).
            'Pencapaian Project Management: Implementasi Enterprise Architecture guna Mendukung Pilar Security Solutions dan Pilar SPBE dalam Pemenuhan Strategic Initiative Digital Platform dan Technology Capabilities'
                => ['Q1' => 100,  'Q2' => 100,   'Q3' => 100,   'Q4' => 100],

            // #8 supporting-unit AI count — annual 3, cumulative 0/1/2/3.
            'Jumlah proses supporting unit yang menggunakan AI'             => ['Q1' => 0,    'Q2' => 1,     'Q3' => 2,     'Q4' => 3],

            // #9 IT Maturity Level — biennial assessment, target Skor 3.85 in Q4.
            'IT Maturity Level (KPI BP BUMN)'                              => ['Q1' => 0,    'Q2' => 0,     'Q3' => 0,     'Q4' => 3.85],
        ];

        foreach ($targets as $measurementName => $quarterlyTargets) {
            $measurement = Measurement::where('measurement', $measurementName)->first();
            if (!$measurement) {
                continue;
            }

            foreach ($quarterlyTargets as $quarter => $target) {
                // Idempotent: keyed on the unique (measurement, year, quarter)
                // constraint so re-running the seeder never duplicates rows.
                Target::firstOrCreate(
                    [
                        'measurement_id' => $measurement->id,
                        'year'           => $year,
                        'quarter'        => $quarter,
                    ],
                    [
                        'target' => $target,
                    ]
                );
            }
        }
    }
}