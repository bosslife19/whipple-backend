<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AdminConfiguration;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class AdminConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        AdminConfiguration::updateOrCreate([
            'referral_point' => 4,
            'deposit_charge' => 1,
            'deposit_charge_waived_point' => 0,
            'deposit_type' => 'percent',
            'withdraw_charge' => 2.5,
            'withdraw_charge_waived_point' => 20,
            'withdraw_type' => 'percent',
        ]);
    }
}
