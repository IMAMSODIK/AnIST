<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate(
            ['email' => 'admin@kpiadvisor.com'],
            [
                'name' => 'Administrator',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create demo user (hanya boleh mengakses Strategic Advisor)
        User::firstOrCreate(
            ['email' => 'demo@kpiadvisor.com'],
            [
                'name' => 'Demo User',
                'password' => Hash::make('password'),
                'role' => 'user',
            ]
        );

        // Seed master data
        $this->call([
            MeasurementSeeder::class,
            TargetSeeder::class,
            InitiativeSeeder::class,
        ]);
    }
}
