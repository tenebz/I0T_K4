<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Perangkat extends Model
{
    protected $table = 'perangkat';

    protected $fillable = ['nama', 'sampel', 'menyala', 'terakhir_online', 'terakhir_sinkron'];

    protected function casts(): array
    {
        return [
            'menyala' => 'boolean',
            'terakhir_online' => 'datetime',
            'terakhir_sinkron' => 'datetime',
        ];
    }

    public function percobaan(): HasMany
    {
        return $this->hasMany(Percobaan::class)->orderBy('nomor');
    }

    public function trials(): HasMany
    {
        return $this->hasMany(Trial::class);
    }

    /** Dashboard ini memantau satu alat (sampel Air A). */
    public static function utama(): self
    {
        return static::query()->orderBy('id')->first()
            ?? static::create(['nama' => 'Perangkat Air A', 'sampel' => 'Water A', 'menyala' => true]);
    }

    public function isOnline(): bool
    {
        $batas = (int) config('kualitas_air.batas_offline_menit');

        if ($batas <= 0) {
            return true;
        }

        return $this->terakhir_online !== null
            && $this->terakhir_online->gt(now()->subMinutes($batas));
    }

    public function tandaiOnline(): void
    {
        $this->forceFill(['terakhir_online' => now()])->save();
    }

    public function untukDashboard(): array
    {
        $zona = config('kualitas_air.zona_waktu');

        return [
            'nama' => $this->nama,
            'sampel' => $this->sampel,
            'menyala' => $this->menyala,
            'online' => $this->isOnline(),
            'terakhir_sinkron' => $this->terakhir_sinkron
                ? $this->terakhir_sinkron->copy()->setTimezone($zona)->format('H:i').' WIB'
                : null,
        ];
    }

    /** Semua data yang dibutuhkan halaman dashboard. */
    public function dataDashboard(): array
    {
        $activeTrial = Trial::where('perangkat_id', $this->id)
            ->where('is_active', true)
            ->with('percobaan')
            ->first();

        $completedTrials = Trial::where('perangkat_id', $this->id)
            ->where('is_active', false)
            ->orderByDesc('ended_at')
            ->with('percobaan')
            ->get()
            ->map->untukDashboard()
            ->values();

        return [
            'perangkat' => $this->untukDashboard(),
            'percobaan' => $activeTrial
                ? $activeTrial->percobaan->map->untukDashboard()->values()
                : collect(),
            'active_trial' => $activeTrial?->untukDashboard(),
            'completed_trials' => $completedTrials,
        ];
    }
}
