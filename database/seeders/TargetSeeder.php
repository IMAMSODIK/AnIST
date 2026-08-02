<?php

namespace Database\Seeders;

use App\Models\Measurement;
use App\Models\Target;
use Illuminate\Database\Seeder;

class TargetSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y');

        $targets = [
            'IT Investment Efficiency' => ['Q1' => 85, 'Q2' => 88, 'Q3' => 90, 'Q4' => 95],
            'IT Cost Reduction' => ['Q1' => 5, 'Q2' => 8, 'Q3' => 10, 'Q4' => 15],
            'System Availability' => ['Q1' => 99.5, 'Q2' => 99.5, 'Q3' => 99.7, 'Q4' => 99.9],
            'User Satisfaction Index' => ['Q1' => 3.5, 'Q2' => 3.7, 'Q3' => 3.8, 'Q4' => 4.0],
            'Implementasi Sistem Digital' => ['Q1' => 2, 'Q2' => 4, 'Q3' => 6, 'Q4' => 8],
            'Cybersecurity Compliance Index' => ['Q1' => 70, 'Q2' => 75, 'Q3' => 80, 'Q4' => 85],
            'Payment System Modernization' => ['Q1' => 25, 'Q2' => 50, 'Q3' => 75, 'Q4' => 100],
            'Enterprise Architecture Adoption Rate' => ['Q1' => 60, 'Q2' => 70, 'Q3' => 80, 'Q4' => 90],
            'Artificial Intelligence Implementation' => ['Q1' => 1, 'Q2' => 2, 'Q3' => 3, 'Q4' => 5],
            'IT Certification Achievement' => ['Q1' => 5, 'Q2' => 10, 'Q3' => 15, 'Q4' => 20],
            'Innovation Index' => ['Q1' => 3.0, 'Q2' => 3.2, 'Q3' => 3.5, 'Q4' => 4.0],
        ];

        foreach ($targets as $measurementName => $quarterlyTargets) {
            $measurement = Measurement::where('measurement', $measurementName)->first();
            if (!$measurement) continue;

            foreach ($quarterlyTargets as $quarter => $target) {
                Target::create([
                    'measurement_id' => $measurement->id,
                    'year' => $year,
                    'quarter' => $quarter,
                    'target' => $target,
                ]);
            }
        }
    }
}
