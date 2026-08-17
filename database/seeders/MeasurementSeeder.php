<?php

namespace Database\Seeders;

use App\Models\Measurement;
use Illuminate\Database\Seeder;

/**
 * Seeds the 8 OMTI 2026 KPIs the user explicitly asked for
 * (measurement #1, 2, 3, 4, 5, 7, 8, dan 9) sourced verbatim from
 * docs/traceability/Draft OMTI 2026.pdf. KPIs #6 and #10 are intentionally
 * excluded — the user only asked for the eight above.
 *
 * Per-quarter target values (in TargetSeeder) are interpreted as CUMULATIVE
 * achievement against the annual target column "2026" in the PDF, because the
 * PDF table layout lists "2026, TW I, TW II, TW III" with cumulative deltas
 * typical of project-delivery KPIs (e.g. #1: 1 / 2 / 4 → annual 7 means Q4
 * cumulative = 7; #8: 0 / 1 / 2 → annual 3 means Q4 cumulative = 3).
 */
class MeasurementSeeder extends Seeder
{
    public function run(): void
    {
        $measurements = [
            // #4 (OMTI Depops) — INTERNAL BUSINESS PROCESS / Operational
            // Excellence. Certification fulfillment percentage with the
            // OMTI action-plan milestones: 80% Pelaksanaan, 100% Lulus
            // Audit. Aggregated as MAX across evidence per period.
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Pemenuhan Sertifikasi Internasional ISO 27001',
                'definition'  => 'Pemenuhan standar sertifikasi ISO/IEC 27001:2022 (Information Security Management System). Penyediaan dokumen, evidence dan pendampingan dengan action plan: 80% Pelaksanaan (penyiapan dokumen, evidence, pendampingan audit) dan 100% Lulus Audit (sertifikat terbit / audit surveillance lulus).',
                'formula'     => 'Higher is Better',
                'unit'        => '%',
                'weight'      => 8,
            ],

            // #3 (OMTI Depops) — INTERNAL BUSINESS PROCESS / Operational
            // Excellence. Counts the registered RSTI roadmap initiatives
            // (B.1.3.4 ALB, B.1.5.12 SIEM) whose status in the quarterly
            // Monitoring MPTI report is "Selesai". Annual target = 2 with
            // TW I-III = 0 (completion expected toward Q4).
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Implementasi Inisiatif Rencana Strategis Teknologi Informasi (RSTI)',
                'definition'  => 'Persentase inisiatif strategis yang tercantum dalam Rencana Strategis Teknologi Informasi (RSTI) yang berhasil direalisasikan sesuai jadwal roadmap. Implementasi roadmap strategis TI dengan tema Enhancing Analytic Capability and Connected System 2026.',
                'formula'     => 'Higher is Better',
                'unit'        => 'Jumlah',
                'weight'      => 8,
            ],

            // #1 — CUSTOMER / Innovation
            [
                'perspective' => 'Customer',
                'objective'   => 'Innovation',
                'measurement' => 'Penyelesaian Implementasi Sistem Aplikasi Upgrade/Baru',
                'definition'  => 'Indikator yang mengukur keberhasilan, ketepatan waktu, dan pemenuhan kualitas dari seluruh tahapan proses penerapan sistem aplikasi yang baru/upgrade, mulai dari fase perencanaan, pengujian, hingga sistem tersebut siap beroperasi (go-live) untuk mendukung operasional bisnis perusahaan.',
                'formula'     => 'Higher is Better',
                'unit'        => 'Jumlah',
                'weight'      => 10,
            ],

            // #2 — INTERNAL BUSINESS PROCESS / Operational Excellence
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Cybersecurity Incident',
                'definition'  => 'Indikator yang menunjukkan jumlah kejadian kasus pelanggaran keamanan cyber yang berdampak pada bisnis Peruri.',
                'formula'     => 'Lower is Better',
                'unit'        => 'Jumlah',
                'weight'      => 10,
            ],

            // #3 — INTERNAL BUSINESS PROCESS / Operational Excellence
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Pencapaian Project Management: Traceability',
                'definition'  => 'Indikator yang menunjukkan terpenuhinya target/objectives/sasaran dari project manajement sesuai timeline yang telah ditetapkan pada Project Charter. Lifecycle 5 tahap: Kajian (20%), TOR (40%), SPK (60%), Implementasi (80%), BAST/Go Live (100%).',
                'formula'     => 'Based on Project Charter (Higher is Better)',
                'unit'        => '%',
                'weight'      => 10,
            ],

            // #4 — INTERNAL BUSINESS PROCESS / Operational Excellence
            // Sharing KPI: satuan Hari, target 14 hari per triwulan (siklus
            // penerimaan dokumen/tagihan s.d. pembayaran diproses) — makin
            // kecil makin baik. SLA Network 98% / Aplikasi 92% adalah teks
            // kolom Initiative (lihat InitiativeSeeder), BUKAN target KPI.
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Percepatan proses pembayaran (sharing KPI)',
                'definition'  => 'Indikator yang mengukur efektivitas dalam mempercepat siklus proses pembayaran, mulai dari penerimaan dokumen/tagihan hingga pembayaran berhasil diproses sesuai target yang ditetapkan.',
                'formula'     => 'Lower is Better',
                'unit'        => 'Hari',
                'weight'      => 10,
            ],

            // #5 — INTERNAL BUSINESS PROCESS / Operational Excellence
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Realisasi Nilai Investasi (KPI BP.BUMN)',
                'definition'  => 'Indikator yang menunjukkan persentase pencapaian program Pengadaan Capex sesuai RKAP 2026. Merealisasikan investasi pada tahun berjalan dengan action plan pengajuan investasi, evaluasi dan klarifikasi teknis tepat waktu.',
                'formula'     => 'Higher is Better',
                'unit'        => '%',
                'weight'      => 10,
            ],

            // #7 — INTERNAL BUSINESS PROCESS / Value Creation (3-stage EA
            // Project Management lifecycle).
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Value Creation',
                'measurement' => 'Pencapaian Project Management: Implementasi Enterprise Architecture guna Mendukung Pilar Security Solutions dan Pilar SPBE dalam Pemenuhan Strategic Initiative Digital Platform dan Technology Capabilities',
                'definition'  => 'Indikator yang menunjukkan terpenuhinya target/objectives/sasaran dari project manajement sesuai timeline yang telah ditetapkan pada Project Charter. Lifecycle 3 tahap: (1) Tahap Perencanaan (TOR, EE) = 25%, (2) Tahap Development (SPK, FGD) = 80%, (3) Tahap Implementasi (BAST) = 100%.',
                'formula'     => 'Based on Project Charter (Higher is Better)',
                'unit'        => '%',
                'weight'      => 10,
            ],

            // #8 — INTERNAL BUSINESS PROCESS / Operational Excellence (count
            // of supporting-unit processes live with AI).
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Operational Excellence',
                'measurement' => 'Jumlah proses supporting unit yang menggunakan AI',
                'definition'  => 'Indikator yang menunjukkan jumlah proses kerja pada supporting unit perusahaan yang telah mengimplementasikan dan menggunakan teknologi Artificial Intelligence (AI) secara aktif untuk mendukung otomatisasi, analisis, pengambilan keputusan, peningkatan efisiensi, atau peningkatan kualitas layanan operasional.',
                'formula'     => 'Higher is Better',
                'unit'        => 'Jumlah',
                'weight'      => 10,
            ],

            // #9 — INTERNAL BUSINESS PROCESS / Value Creation (biennial IT
            // Maturity assessment, target Skor 3.85).
            [
                'perspective' => 'Internal Process',
                'objective'   => 'Value Creation',
                'measurement' => 'IT Maturity Level (KPI BP BUMN)',
                'definition'  => 'Indikator yang menunjukkan kematangan tingkat keberlangsungan dari sebuah proses menuju kematangan teknologi informasi. Pelaksanaan asesmen IT Maturity Level berlaku 2 tahun (biennial).',
                'formula'     => 'Higher is Better',
                'unit'        => 'Skor',
                'weight'      => 10,
            ],
        ];

        foreach ($measurements as $data) {
            // Idempotent: keyed on the unique measurement name so re-running
            // the seeder never duplicates rows.
            Measurement::firstOrCreate(
                ['measurement' => $data['measurement']],
                $data
            );
        }
    }
}