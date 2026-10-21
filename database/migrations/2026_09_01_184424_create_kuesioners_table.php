<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kuesioner', function (Blueprint $table) {
            $table->id()->comment('ID unik instrumen kuesioner induk');
            $table->string('title', 150)->comment('Judul instrumen kuesioner (misal: Tracer Study 2026)');
            $table->text('description')->nullable()->comment('Deskripsi penjelasan instrumen kuesioner');
            $table->year('year')->comment('Tahun periode pelaksanaan tracer study');
            $table->boolean('is_active')->default(true)->comment('Status keaktifan kuesioner bagi responden');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kuesioner');
    }
};
