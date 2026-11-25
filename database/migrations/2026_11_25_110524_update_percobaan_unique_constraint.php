<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percobaan', function (Blueprint $table) {
            $table->dropUnique(['perangkat_id', 'nomor']);
            $table->unique(['perangkat_id', 'trial_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::table('percobaan', function (Blueprint $table) {
            $table->dropUnique(['perangkat_id', 'trial_id', 'nomor']);
            $table->unique(['perangkat_id', 'nomor']);
        });
    }
};
