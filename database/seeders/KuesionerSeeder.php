<?php

namespace Database\Seeders;

use App\Models\Kuesioner;
use Illuminate\Database\Seeder;

class KuesionerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Kuesioner::updateOrCreate(
            ['title' => 'Tracer Study UKDW 2021'],
            [
                'description' => 'Kuesioner Pelacakan Jejak Alumni Universitas Kristen Duta Wacana (Standar Tracer Study 2021)',
                'is_active' => true,
                'year' => 2021,
            ]
        );
    }
}
