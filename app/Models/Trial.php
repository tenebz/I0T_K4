<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
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
     * Jumlah & rata-rata percobaan dihitung database (COUNT/AVG dalam satu query daftar),
     * sehingga daftar Evaluasi tidak perlu memuat ribuan baris percobaan.
     *
     * @param  Builder<Trial>  $query
     */
    #[Scope]
    protected function denganRingkasan(Builder $query): void
    {
        $tanpaUrutan = fn ($q) => $q->reorder();

        $query->withCount(['percobaan' => $tanpaUrutan]);
        foreach (['ph', 'tds', 'kekeruhan', 'suhu'] as $kolom) {
            $query->withAvg(['percobaan' => $tanpaUrutan], $kolom);
        }
    }

    /**
     * Ringkasan trial untuk daftar (Evaluasi, trial aktif, hasil start/end/hapus):
     * tanpa daftar percobaan. Memakai agregat dari denganRingkasan() bila ada, atau
     * relasi yang sudah dimuat, dan baru menghitung sendiri bila keduanya tidak ada.
     *
     * @return array{id: int, name: string, is_active: bool, started_at: string, ended_at: ?string, jumlah_percobaan: int, rata_rata: ?array}
     */
    public function ringkasan(): array
    {
        $zona = config('kualitas_air.zona_waktu');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'is_active' => $this->is_active,
            'started_at' => $this->started_at->copy()->setTimezone($zona)->translatedFormat('j M Y H:i'),
            'ended_at' => $this->ended_at?->copy()->setTimezone($zona)->translatedFormat('j M Y H:i'),
            'jumlah_percobaan' => $this->jumlahPercobaan(),
            'rata_rata' => $this->is_active ? null : $this->rataRataRingkasan(),
        ];
    }

    /**
     * Ringkasan + semua percobaan: untuk dialog detail satu trial (dimuat saat dibuka).
     *
     * @return array<string, mixed>
     */
    public function untukDashboard(): array
    {
        return $this->ringkasan() + [
            'percobaan' => $this->percobaan->map->untukDashboard()->values(),
        ];
    }

    private function jumlahPercobaan(): int
    {
        if (array_key_exists('percobaan_count', $this->attributes)) {
            return (int) $this->attributes['percobaan_count'];
        }

        return $this->relationLoaded('percobaan') ? $this->percobaan->count() : $this->percobaan()->count();
    }

    /** @return array{ph: float, tds: float, kekeruhan: float, suhu: float}|null */
    private function rataRataRingkasan(): ?array
    {
        if (array_key_exists('percobaan_avg_ph', $this->attributes)) {
            if ($this->attributes['percobaan_avg_ph'] === null) {
                return null;
            }

            return $this->bulatkanRataRata(
                $this->attributes['percobaan_avg_ph'],
                $this->attributes['percobaan_avg_tds'],
                $this->attributes['percobaan_avg_kekeruhan'],
                $this->attributes['percobaan_avg_suhu'],
            );
        }

        return $this->relationLoaded('percobaan') ? $this->rataRataDariMuatan($this->percobaan) : $this->rataRata();
    }
}
