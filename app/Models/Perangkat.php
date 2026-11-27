<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Perangkat extends Model
{
    protected $table = 'perangkat';

    /** Detik minimum antar penulisan terakhir_online (lihat tandaiOnline()). */
    private const JEDA_TULIS_ONLINE_DETIK = 15;

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

    /**
     * Alat bisa ping tiap beberapa detik. Batas offline dihitung dalam menit, jadi menulis
     * ulang terakhir_online lebih sering dari JEDA_TULIS_ONLINE_DETIK tidak mengubah status
     * online apa pun — hanya menambah penulisan ke SQLite. Penulisan itu dilewati.
     */
    public function tandaiOnline(): void
    {
        if ($this->terakhir_online !== null && $this->terakhir_online->gt(now()->subSeconds(self::JEDA_TULIS_ONLINE_DETIK))) {
            return;
        }

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

    /**
     * Semua data yang dibutuhkan halaman dashboard: data Sync ditambah ringkasan
     * trial yang sudah selesai (tanpa percobaannya; dimuat saat detail dibuka).
     *
     * @return array<string, mixed>
     */
    public function dataDashboard(): array
    {
        return $this->dataSinkron() + [
            'completed_trials' => $this->ringkasanTrialSelesai(),
        ];
    }

    /**
     * Data untuk tombol Sync: perangkat, trial aktif, dan percobaannya. Trial yang sudah
     * selesai tidak berubah saat Sync, jadi tidak ikut dikirim ulang.
     *
     * @return array{perangkat: array<string, mixed>, percobaan: Collection<int, array<string, mixed>>, active_trial: ?array<string, mixed>}
     */
    public function dataSinkron(): array
    {
        $activeTrial = Trial::where('perangkat_id', $this->id)
            ->where('is_active', true)
            ->with('percobaan')
            ->first();

        return [
            'perangkat' => $this->untukDashboard(),
            'percobaan' => $activeTrial
                ? $activeTrial->percobaan->map->untukDashboard()->values()
                : collect(),
            // Ringkasan saja: percobaannya sudah ada di 'percobaan' (tidak dikirim dua kali).
            'active_trial' => $activeTrial?->ringkasan(),
        ];
    }

    /**
     * Sync bertahap: browser sudah memegang percobaan trial $sejakTrial sampai nomor
     * $sejakNomor, jadi hanya percobaan sesudahnya yang dikirim. Bila trial aktif sudah
     * berganti (atau tidak ada), dikembalikan data lengkap seperti dataSinkron().
     *
     * @return array<string, mixed>
     */
    public function dataSinkronTambahan(int $sejakTrial, int $sejakNomor): array
    {
        $activeTrial = Trial::where('perangkat_id', $this->id)
            ->where('is_active', true)
            ->first();

        if (! $activeTrial || $activeTrial->id !== $sejakTrial) {
            return $this->dataSinkron();
        }

        return [
            'perangkat' => $this->untukDashboard(),
            'mode' => 'tambahan',
            'percobaan_baru' => $activeTrial->percobaan()
                ->where('nomor', '>', $sejakNomor)
                ->get()
                ->map->untukDashboard()
                ->values(),
            'active_trial' => $activeTrial->ringkasan(),
        ];
    }

    /**
     * Trial yang sudah selesai, terbaru dulu, dengan jumlah & rata-rata dari SQL.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function ringkasanTrialSelesai(): Collection
    {
        return Trial::where('perangkat_id', $this->id)
            ->where('is_active', false)
            ->orderByDesc('ended_at')
            ->denganRingkasan()
            ->get()
            ->map->ringkasan()
            ->values();
    }
}
