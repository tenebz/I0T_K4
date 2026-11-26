<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Trial extends Model
{
    protected $fillable = ['perangkat_id', 'name', 'is_active', 'started_at', 'ended_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(Perangkat::class);
    }

    public function percobaan(): HasMany
    {
        return $this->hasMany(Percobaan::class)->orderBy('nomor');
    }

    /**
     * Hitung rata-rata sensor dari semua percobaan dalam trial ini.
     * Null bila trial belum punya percobaan (tidak ada yang bisa dirata-rata).
     *
     * @return array{ph: float, tds: float, kekeruhan: float, suhu: float}|null
     */
    public function rataRata(): ?array
    {
        $avg = $this->percobaan()
            ->selectRaw('AVG(ph) as ph, AVG(tds) as tds, AVG(kekeruhan) as kekeruhan, AVG(suhu) as suhu')
            ->first();

        if ($avg === null || $avg->ph === null) {
            return null;
        }

        return $this->bulatkanRataRata($avg->ph, $avg->tds, $avg->kekeruhan, $avg->suhu);
    }

    /**
     * Sama dengan rataRata(), tetapi dihitung dari relasi percobaan yang sudah dimuat
     * sehingga tidak menambah query per trial saat menampilkan banyak trial sekaligus.
     *
     * @return array{ph: float, tds: float, kekeruhan: float, suhu: float}|null
     */
    private function rataRataDariMuatan(Collection $percobaan): ?array
    {
        if ($percobaan->isEmpty()) {
            return null;
        }

        return $this->bulatkanRataRata(
            $percobaan->avg('ph'),
            $percobaan->avg('tds'),
            $percobaan->avg('kekeruhan'),
            $percobaan->avg('suhu'),
        );
    }

    /** @return array{ph: float, tds: float, kekeruhan: float, suhu: float} */
    private function bulatkanRataRata(mixed $ph, mixed $tds, mixed $kekeruhan, mixed $suhu): array
    {
        return [
            'ph' => round((float) $ph, 2),
            'tds' => round((float) $tds, 1),
            'kekeruhan' => round((float) $kekeruhan, 2),
            'suhu' => round((float) $suhu, 2),
        ];
    }

    /**
     * Data yang dikirim ke frontend.
     *
     * @return array<string, mixed>
     */
    public function untukDashboard(): array
    {
        $zona = config('kualitas_air.zona_waktu');
        $mulai = $this->started_at->copy()->setTimezone($zona);
        $selesai = $this->ended_at?->copy()->setTimezone($zona);

        // Satu kali muat (atau pakai hasil eager load ->with('percobaan')),
        // lalu jumlah & rata-rata dihitung dari koleksi ini tanpa query tambahan.
        $percobaan = $this->percobaan;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'started_at' => $mulai->translatedFormat('j M Y H:i'),
            'ended_at' => $selesai?->translatedFormat('j M Y H:i'),
            'jumlah_percobaan' => $percobaan->count(),
            'rata_rata' => $this->is_active ? null : $this->rataRataDariMuatan($percobaan),
            'percobaan' => $percobaan->map->untukDashboard()->values(),
        ];
    }
}
