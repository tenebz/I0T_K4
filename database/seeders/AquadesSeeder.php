<?php

namespace Database\Seeders;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Contoh trial aktif "Aquades" (air suling) dengan 10 percobaan, untuk melihat grafik.
 *
 * Aquades biasanya ber-TDS dan berkekeruhan sangat rendah, dengan pH sedikit asam
 * karena menyerap CO₂; beberapa nilai sengaja menyentuh batas agar status
 * normal, mendekati batas, dan di luar batas ikut tampil. Aman dijalankan ulang.
 */
class AquadesSeeder extends Seeder
{
    private const NAMA_TRIAL = 'Aquades';

    /**
     * Menit sebelum sekarang dan nilai sensor tiap percobaan, urut dari yang paling awal.
     *
     * @var array<int, array{menit_lalu: int, ph: float, tds: float, kekeruhan: float, suhu: float}>
     */
    private const BACAAN = [
        ['menit_lalu' => 270, 'ph' => 6.9, 'tds' => 3, 'kekeruhan' => 0.3, 'suhu' => 26.1],
        ['menit_lalu' => 240, 'ph' => 6.8, 'tds' => 4, 'kekeruhan' => 0.4, 'suhu' => 26.4],
        ['menit_lalu' => 210, 'ph' => 6.7, 'tds' => 4, 'kekeruhan' => 0.3, 'suhu' => 26.9],
        ['menit_lalu' => 180, 'ph' => 6.6, 'tds' => 5, 'kekeruhan' => 0.5, 'suhu' => 27.3],
        ['menit_lalu' => 150, 'ph' => 6.3, 'tds' => 6, 'kekeruhan' => 0.6, 'suhu' => 27.8],
        ['menit_lalu' => 120, 'ph' => 6.5, 'tds' => 5, 'kekeruhan' => 0.4, 'suhu' => 28.6],
        ['menit_lalu' => 90, 'ph' => 6.8, 'tds' => 4, 'kekeruhan' => 0.3, 'suhu' => 29.6],
        ['menit_lalu' => 60, 'ph' => 7.0, 'tds' => 3, 'kekeruhan' => 0.2, 'suhu' => 28.9],
        ['menit_lalu' => 30, 'ph' => 6.9, 'tds' => 2, 'kekeruhan' => 0.2, 'suhu' => 27.7],
        ['menit_lalu' => 5, 'ph' => 6.8, 'tds' => 3, 'kekeruhan' => 0.3, 'suhu' => 27.2],
    ];

    public function run(): void
    {
        $zona = config('kualitas_air.zona_waktu');
        $perangkat = Perangkat::utama();
        $perangkat->forceFill([
            'menyala' => true,
            'terakhir_online' => now(),
            'terakhir_sinkron' => now(),
        ])->save();

        // Hanya satu trial aktif: trial aktif lain ditutup, trial Aquades lama dibuat ulang.
        Trial::query()
            ->where('perangkat_id', $perangkat->id)
            ->where('is_active', true)
            ->where('name', '!=', self::NAMA_TRIAL)
            ->update(['is_active' => false, 'ended_at' => now()]);

        Trial::query()
            ->where('perangkat_id', $perangkat->id)
            ->where('name', self::NAMA_TRIAL)
            ->get()
            ->each(function (Trial $trial): void {
                $trial->percobaan()->delete();
                $trial->delete();
            });

        $trial = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => self::NAMA_TRIAL,
            'is_active' => true,
            'started_at' => Carbon::now($zona)->subMinutes(self::BACAAN[0]['menit_lalu'] + 10)->utc(),
        ]);

        foreach (self::BACAAN as $index => $bacaan) {
            $perangkat->percobaan()->create([
                'trial_id' => $trial->id,
                'nomor' => $index + 1,
                'diukur_pada' => Carbon::now($zona)->subMinutes($bacaan['menit_lalu'])->utc(),
                'ph' => $bacaan['ph'],
                'tds' => $bacaan['tds'],
                'kekeruhan' => $bacaan['kekeruhan'],
                'suhu' => $bacaan['suhu'],
            ]);
        }
    }
}
