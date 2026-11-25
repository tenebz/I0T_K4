<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('percobaan', function (Blueprint $table) {
            $table->foreignId('trial_id')->nullable()->after('perangkat_id')->constrained('trials')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('percobaan', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trial_id');
        });
    }
};
