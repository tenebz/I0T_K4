<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
     *
     * @return array{ph: float, tds: float, kekeruhan: float, suhu: float}
     */
    public function rataRata(): array
    {
        $avg = $this->percobaan()
            ->selectRaw('AVG(ph) as ph, AVG(tds) as tds, AVG(kekeruhan) as kekeruhan, AVG(suhu) as suhu')
            ->first();

        return [
            'ph' => round((float) $avg->ph, 2),
            'tds' => round((float) $avg->tds, 1),
            'kekeruhan' => round((float) $avg->kekeruhan, 2),
            'suhu' => round((float) $avg->suhu, 2),
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

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'started_at' => $mulai->translatedFormat('j M Y H:i'),
            'ended_at' => $selesai?->translatedFormat('j M Y H:i'),
            'jumlah_percobaan' => $this->percobaan()->count(),
            'rata_rata' => $this->is_active ? null : $this->rataRata(),
            'percobaan' => $this->percobaan->map->untukDashboard()->values(),
        ];
    }
}
