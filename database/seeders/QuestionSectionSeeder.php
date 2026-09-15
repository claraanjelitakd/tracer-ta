<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class QuestionSectionSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KelompokPertanyaanSeeder::class);
    }
}
