<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class QuestionOptionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RefSubpertanyaanDetilSeeder::class);
    }
}
