<?php

namespace Database\Seeders;

use App\Models\Initiative;
use App\Models\Measurement;
use Illuminate\Database\Seeder;

class InitiativeSeeder extends Seeder
{
    public function run(): void
    {
        $initiatives = [
            'IT Investment Efficiency' => [
                'Optimalisasi penggunaan cloud infrastructure',
                'Konsolidasi lisensi software enterprise',
                'Implementasi FinOps framework',
            ],
            'IT Cost Reduction' => [
                'Migrasi ke cloud-native architecture',
                'Automasi proses operasional IT',
                'Renegosiasi kontrak vendor',
            ],
            'System Availability' => [
                'Implementasi High Availability cluster',
                'Penyempurnaan disaster recovery plan',
                'Monitoring proaktif dengan AIOps',
            ],
            'User Satisfaction Index' => [
                'Peningkatan response time helpdesk',
                'Implementasi self-service portal',
                'User experience improvement program',
            ],
            'Implementasi Sistem Digital' => [
                'Implementasi Core Banking System',
                'Digital Onboarding Platform',
                'Mobile Banking Enhancement',
                'API Gateway Implementation',
            ],
            'Cybersecurity Compliance Index' => [
                'ISO 27001 Certification renewal',
                'Penetration testing quarterly',
                'Security awareness training program',
                'SOC implementation dan monitoring',
            ],
            'Payment System Modernization' => [
                'Implementasi Real-Time Gross Settlement',
                'QR Payment integration',
                'Cross-border payment gateway',
            ],
            'Enterprise Architecture Adoption Rate' => [
                'EA framework documentation',
                'Architecture review board establishment',
                'Technology standards compliance audit',
            ],
            'Artificial Intelligence Implementation' => [
                'AI-powered fraud detection system',
                'Chatbot customer service',
                'Predictive analytics for risk management',
                'Document AI processing',
            ],
            'IT Certification Achievement' => [
                'AWS/Azure/GCP certification program',
                'CISSP/CISM certification sponsorship',
                'Agile/Scrum certification program',
            ],
            'Innovation Index' => [
                'Internal hackathon program',
                'Innovation lab establishment',
                'Technology exploration POC program',
            ],
        ];

        foreach ($initiatives as $measurementName => $initiativeList) {
            $measurement = Measurement::where('measurement', $measurementName)->first();
            if (!$measurement) continue;

            foreach ($initiativeList as $initiative) {
                Initiative::create([
                    'measurement_id' => $measurement->id,
                    'initiative' => $initiative,
                ]);
            }
        }
    }
}
