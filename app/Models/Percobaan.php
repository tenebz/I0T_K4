<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Percobaan extends Model
{
    protected $table = 'percobaan';

    protected $fillable = ['perangkat_id', 'trial_id', 'nomor', 'diukur_pada', 'ph', 'tds', 'kekeruhan', 'suhu'];

    protected function casts(): array
    {
        return [
            'diukur_pada' => 'datetime',
            'ph' => 'float',
            'tds' => 'float',
            'kekeruhan' => 'float',
            'suhu' => 'float',
        ];
    }

    public function perangkat(): BelongsTo
    {
        return $this->belongsTo(Perangkat::class);
    }

    public function trial(): BelongsTo
    {
        return $this->belongsTo(Trial::class);
    }

    public function untukDashboard(): array
    {
        $waktu = $this->diukur_pada->copy()->setTimezone(config('kualitas_air.zona_waktu'))->locale('en');

        return [
            'no' => $this->nomor,
            'tanggal' => $waktu->translatedFormat('j M Y'),
            'jam' => $waktu->format('H:i'),
            'ph' => $this->ph,
            'tds' => $this->tds,
            'kekeruhan' => $this->kekeruhan,
            'suhu' => $this->suhu,
            'trial_id' => $this->trial_id,
        ];
    }
}
