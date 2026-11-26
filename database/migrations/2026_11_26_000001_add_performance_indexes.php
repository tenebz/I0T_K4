<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk query yang dipakai di setiap request dashboard & alat IoT:
 * - trials: where perangkat_id + is_active, urut ended_at (trial aktif / riwayat).
 * - percobaan: where trial_id, urut nomor (eager load percobaan per trial).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trials', function (Blueprint $table) {
            $table->index(['perangkat_id', 'is_active', 'ended_at']);
        });

        Schema::table('percobaan', function (Blueprint $table) {
            $table->index(['trial_id', 'nomor']);
        });
    }

    public function down(): void
    {
        Schema::table('percobaan', function (Blueprint $table) {
            $table->dropIndex(['trial_id', 'nomor']);
        });

        Schema::table('trials', function (Blueprint $table) {
            $table->dropIndex(['perangkat_id', 'is_active', 'ended_at']);
        });
    }
};
