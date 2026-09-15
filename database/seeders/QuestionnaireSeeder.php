<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(KuesionerSeeder::class);
    }
}
