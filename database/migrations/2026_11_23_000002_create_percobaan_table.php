<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('percobaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perangkat_id')->constrained('perangkat')->cascadeOnDelete();
            $table->unsignedInteger('nomor');
            $table->timestamp('diukur_pada');
            $table->decimal('ph', 4, 2);
            $table->decimal('tds', 8, 1);
            $table->decimal('kekeruhan', 6, 2);
            $table->decimal('suhu', 5, 2);
            $table->timestamps();

            $table->unique(['perangkat_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('percobaan');
    }
};
