<?php

namespace Database\Seeders;

use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Data contoh dashboard yang mencakup nilai normal, peringatan, dan di luar batas. */
class KualitasAirSeeder extends Seeder
{
    public function run(): void
    {
        $zona = config('kualitas_air.zona_waktu');

        $perangkat = Perangkat::updateOrCreate(
            ['nama' => 'Perangkat Air A'],
            [
                'sampel' => 'Air A',
                'menyala' => true,
                'terakhir_online' => Carbon::now($zona)->utc(),
                'terakhir_sinkron' => Carbon::now($zona)->utc(),
            ],
        );

        // Bersihkan data lama
        $perangkat->percobaan()->delete();
        Trial::where('perangkat_id', $perangkat->id)->delete();

        // ===== Trial 1: AQUA (Selesai — semua normal) =====
        $trial1 = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => 'AQUA',
            'is_active' => false,
            'started_at' => Carbon::now($zona)->subDays(12)->utc(),
            'ended_at' => Carbon::now($zona)->subDays(7)->utc(),
        ]);

        $data1 = [
            ['hari_lalu' => 12, 'jam' => '09:15', 'ph' => 7.2, 'tds' => 320, 'kekeruhan' => 3.1, 'suhu' => 27.5],
            ['hari_lalu' => 11, 'jam' => '10:02', 'ph' => 6.7, 'tds' => 340, 'kekeruhan' => 3.0, 'suhu' => 27.1],
            ['hari_lalu' => 10, 'jam' => '13:40', 'ph' => 7.0, 'tds' => 450, 'kekeruhan' => 3.2, 'suhu' => 28.0],
            ['hari_lalu' => 9, 'jam' => '08:55', 'ph' => 7.4, 'tds' => 360, 'kekeruhan' => 4.2, 'suhu' => 27.9],
            ['hari_lalu' => 8, 'jam' => '14:50', 'ph' => 7.2, 'tds' => 320, 'kekeruhan' => 3.1, 'suhu' => 25.2],
        ];

        $this->buatPercobaan($perangkat, $trial1, $data1, $zona);

        // ===== Trial 2: Le Minerale (Selesai — ada yang buruk) =====
        $trial2 = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => 'Le Minerale',
            'is_active' => false,
            'started_at' => Carbon::now($zona)->subDays(6)->utc(),
            'ended_at' => Carbon::now($zona)->subDays(3)->utc(),
        ]);

        $data2 = [
            ['hari_lalu' => 6, 'jam' => '11:20', 'ph' => 5.9, 'tds' => 340, 'kekeruhan' => 2.9, 'suhu' => 27.4],
            ['hari_lalu' => 5, 'jam' => '15:30', 'ph' => 8.7, 'tds' => 500, 'kekeruhan' => 3.0, 'suhu' => 28.1],
            ['hari_lalu' => 4, 'jam' => '08:40', 'ph' => 7.3, 'tds' => 350, 'kekeruhan' => 5.2, 'suhu' => 26.9],
            ['hari_lalu' => 3, 'jam' => '12:05', 'ph' => 7.0, 'tds' => 360, 'kekeruhan' => 3.3, 'suhu' => 24.0],
        ];

        $this->buatPercobaan($perangkat, $trial2, $data2, $zona);

        // ===== Trial 3: Pristine (Aktif — saat ini sedang berjalan) =====
        $trial3 = Trial::create([
            'perangkat_id' => $perangkat->id,
            'name' => 'Pristine',
            'is_active' => true,
            'started_at' => Carbon::now($zona)->subDays(2)->utc(),
        ]);

        $data3 = [
            ['hari_lalu' => 2, 'jam' => '16:15', 'ph' => 7.2, 'tds' => 320, 'kekeruhan' => 3.1, 'suhu' => 31.0],
            ['hari_lalu' => 1, 'jam' => '10:45', 'ph' => 8.3, 'tds' => 470, 'kekeruhan' => 4.5, 'suhu' => 29.7],
            ['hari_lalu' => 0, 'jam' => '09:30', 'ph' => 7.2, 'tds' => 520, 'kekeruhan' => 6.0, 'suhu' => 27.5],
        ];

        $this->buatPercobaan($perangkat, $trial3, $data3, $zona);
    }

    /**
     * Nomor percobaan selalu dimulai dari 1 dalam setiap trial.
     *
     * @param  array<int, array{hari_lalu: int, jam: string, ph: float, tds: float, kekeruhan: float, suhu: float}>  $data
     */
    private function buatPercobaan(Perangkat $perangkat, Trial $trial, array $data, string $zona): void
    {
        foreach ($data as $index => $bacaan) {
            $perangkat->percobaan()->create([
                'trial_id' => $trial->id,
                'nomor' => $index + 1,
                'diukur_pada' => Carbon::now($zona)
                    ->subDays($bacaan['hari_lalu'])
                    ->setTimeFromTimeString($bacaan['jam'])
                    ->utc(),
                'ph' => $bacaan['ph'],
                'tds' => $bacaan['tds'],
                'kekeruhan' => $bacaan['kekeruhan'],
                'suhu' => $bacaan['suhu'],
            ]);
        }
    }
}
