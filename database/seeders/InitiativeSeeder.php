<?php

namespace Database\Seeders;

use App\Models\Initiative;
use App\Models\Measurement;
use Illuminate\Database\Seeder;

/**
 * Seeds the initiatives per OMTI 2026 KPI, lifted directly from the
 * "Initiative" column of docs/traceability/Draft OMTI 2026.pdf. Initiatives
 * are stored as plain strings because the AI prompt joins them into a list
 * the model can match evidence against.
 */
class InitiativeSeeder extends Seeder
{
    public function run(): void
    {
        $initiatives = [
            // #1 Penyelesaian Implementasi Sistem Aplikasi Upgrade/Baru —
            // PDF lists 7 specific projects to deliver in 2026.
            'Penyelesaian Implementasi Sistem Aplikasi Upgrade/Baru' => [
                'Pengembangan Implementasi sistem terintegrasi yang berdampak pada peningkatan produktivitas/efisiensi atau yang berdampak pada kepuasan pelanggan secara berkelanjutan',
                'Integrasi Mesin Timbangan Uang Logam (Produksi)',
                'Sistem Layanan Reimburs Kesehatan (SDM)',
                'Implementasi Pakta Integritas Digital (Risk Management)',
                'Data Acqusition Single Note Inspection (Produksi)',
                'Implementasi BAST Digital (Dafasum)',
                'Pengembangan Sistem Pendukung Payroll - Time Management (SDM)',
                'Upgrade Teknologi Platform OMTI (Corporate Strategy dan Performance)',
            ],

            // #2 Cybersecurity Incident — PDF initiative is one sentence.
            'Cybersecurity Incident' => [
                'Memastikan kelancaran operasional dengan meminimalkan gangguan yang disebabkan oleh insiden keamanan',
            ],

            // #3 Pencapaian Project Management: Traceability — 5 PMO
            // governance initiatives from PDF.
            'Pencapaian Project Management: Traceability' => [
                'Melakukan pemantauan dan pengendalian yang ketat terhadap pencapaian milestone Project Charter',
                'Memitigasi risiko secara proaktif terhadap keterlambatan dokumen lifecycle proyek',
                'Menetapkan peran dan tanggung jawab setiap anggota tim proyek',
                'Optimalisasi sumber daya dan kompetensi dalam penyusunan Kajian, TOR, SPK, Implementasi, dan BAST',
                'Membangun komunikasi yang rutin dan jelas antar anggota tim proyek (sprint/stand-up)',
            ],

            // #4 Percepatan proses pembayaran — sharing KPI; IT contribution
            // is the SLA Network 98% dan Aplikasi 92%.
            'Percepatan proses pembayaran (sharing KPI)' => [
                'Ketersediaan Infrastruktur dan Aplikasi dengan pemenuhan SLA Network 98% dan Aplikasi sebesar 92%',
            ],

            // #5 Realisasi Nilai Investasi — action plan pengajuan + evaluasi
            // + klarifikasi teknis tepat waktu.
            'Realisasi Nilai Investasi (KPI BP.BUMN)' => [
                'Merealisasikan investasi pada tahun berjalan dengan action plan pengajuan investasi, evaluasi dan klarifikasi teknis tepat waktu',
            ],

            // #7 EA 3-stage — overall + 3 tahap lifecycle split out per-stage.
            'Pencapaian Project Management: Implementasi Enterprise Architecture guna Mendukung Pilar Security Solutions dan Pilar SPBE dalam Pemenuhan Strategic Initiative Digital Platform dan Technology Capabilities' => [
                'Pelaksanaan project yang telah ditetapkan guna penyelarasan dan eksekusi strategi melalui implementasi Enterprise Architecture tahun 2026',
                'Tahap Perencanaan (TOR, EE) = 25% sesuai timeline Project Charter',
                'Tahap Development (SPK, FGD) = 80% sesuai timeline Project Charter',
                'Tahap Implementasi (BAST) = 100% (Go Live) sesuai timeline Project Charter',
            ],

            // #8 Jumlah proses supporting unit yang menggunakan AI — 3
            // specific supporting-unit AI adoptions listed in PDF.
            'Jumlah proses supporting unit yang menggunakan AI' => [
                'Supporting AI pada proses atau program unit kerja disesuaikan dengan kebutuhan',
                'Implementasi AI pada proses Mid Year Survey',
                'Implementasi AI pada Proses Recruitment',
                'Implementasi AI pada proses E-invoice',
            ],

            // #9 IT Maturity Level — biennial assessment.
            'IT Maturity Level (KPI BP BUMN)' => [
                'Pelaksanaan asesmen IT Maturity Level (berlaku 2 tahun)',
            ],
        ];

        foreach ($initiatives as $measurementName => $initiativeList) {
            $measurement = Measurement::where('measurement', $measurementName)->first();
            if (!$measurement) {
                continue;
            }

            foreach ($initiativeList as $initiative) {
                Initiative::create([
                    'measurement_id' => $measurement->id,
                    'initiative'     => $initiative,
                ]);
            }
        }
    }
}