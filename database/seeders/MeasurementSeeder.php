<?php

namespace Database\Seeders;

use App\Models\Measurement;
use Illuminate\Database\Seeder;

class MeasurementSeeder extends Seeder
{
    public function run(): void
    {
        $measurements = [
            // Financial Perspective
            [
                'perspective' => 'Financial',
                'objective' => 'Optimize IT Investment',
                'measurement' => 'IT Investment Efficiency',
                'definition' => 'Mengukur efisiensi penggunaan anggaran IT terhadap target yang ditetapkan',
                'formula' => 'Higher is Better',
                'unit' => '%',
                'weight' => 10,
            ],
            [
                'perspective' => 'Financial',
                'objective' => 'Reduce Operational Cost',
                'measurement' => 'IT Cost Reduction',
                'definition' => 'Mengukur persentase pengurangan biaya operasional IT',
                'formula' => 'Higher is Better',
                'unit' => '%',
                'weight' => 8,
            ],

            // Customer Perspective
            [
                'perspective' => 'Customer',
                'objective' => 'Improve Service Quality',
                'measurement' => 'System Availability',
                'definition' => 'Mengukur ketersediaan sistem utama (uptime)',
                'formula' => 'Higher is Better',
                'unit' => '%',
                'weight' => 10,
            ],
            [
                'perspective' => 'Customer',
                'objective' => 'Enhance User Satisfaction',
                'measurement' => 'User Satisfaction Index',
                'definition' => 'Mengukur tingkat kepuasan pengguna terhadap layanan IT',
                'formula' => 'Higher is Better',
                'unit' => 'Score',
                'weight' => 8,
            ],

            // Internal Process Perspective
            [
                'perspective' => 'Internal Process',
                'objective' => 'Digital Transformation',
                'measurement' => 'Implementasi Sistem Digital',
                'definition' => 'Mengukur jumlah implementasi sistem digital baru sesuai roadmap',
                'formula' => 'Higher is Better',
                'unit' => 'Number',
                'weight' => 12,
            ],
            [
                'perspective' => 'Internal Process',
                'objective' => 'Strengthen Cybersecurity',
                'measurement' => 'Cybersecurity Compliance Index',
                'definition' => 'Mengukur tingkat kepatuhan terhadap standar keamanan siber',
                'formula' => 'Higher is Better',
                'unit' => 'Index',
                'weight' => 10,
            ],
            [
                'perspective' => 'Internal Process',
                'objective' => 'Modernize Payment System',
                'measurement' => 'Payment System Modernization',
                'definition' => 'Mengukur progres modernisasi sistem pembayaran',
                'formula' => 'Higher is Better',
                'unit' => '%',
                'weight' => 10,
            ],
            [
                'perspective' => 'Internal Process',
                'objective' => 'Enterprise Architecture Compliance',
                'measurement' => 'Enterprise Architecture Adoption Rate',
                'definition' => 'Mengukur tingkat adopsi enterprise architecture dalam pengembangan sistem',
                'formula' => 'Higher is Better',
                'unit' => '%',
                'weight' => 8,
            ],

            // Learning & Growth Perspective
            [
                'perspective' => 'Learning & Growth',
                'objective' => 'Develop AI Capabilities',
                'measurement' => 'Artificial Intelligence Implementation',
                'definition' => 'Mengukur jumlah implementasi solusi AI/ML dalam operasional',
                'formula' => 'Higher is Better',
                'unit' => 'Number',
                'weight' => 12,
            ],
            [
                'perspective' => 'Learning & Growth',
                'objective' => 'Enhance IT Competency',
                'measurement' => 'IT Certification Achievement',
                'definition' => 'Mengukur jumlah sertifikasi IT yang berhasil diperoleh karyawan',
                'formula' => 'Higher is Better',
                'unit' => 'Number',
                'weight' => 6,
            ],
            [
                'perspective' => 'Learning & Growth',
                'objective' => 'Innovation Culture',
                'measurement' => 'Innovation Index',
                'definition' => 'Mengukur tingkat inovasi dan inisiatif improvement yang dihasilkan',
                'formula' => 'Higher is Better',
                'unit' => 'Index',
                'weight' => 6,
            ],
        ];

        foreach ($measurements as $data) {
            Measurement::create($data);
        }
    }
}
