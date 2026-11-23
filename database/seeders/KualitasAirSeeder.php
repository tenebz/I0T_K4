<?php

namespace Database\Seeders;

use App\Models\Perangkat;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Data contoh dari desain: 5 percobaan sampel Air A. Aman dijalankan ulang. */
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
                'terakhir_online' => now(),
                'terakhir_sinkron' => Carbon::parse('2026-11-23 14:52', $zona)->utc(),
            ],
        );

        $perangkat->percobaan()->delete();

        $data = [
            ['2026-11-16 09:15', 7.1, 290, 2.4, 26.8],
            ['2026-11-17 10:02', 5.9, 340, 3.0, 27.1],
            ['2026-11-19 13:40', 7.0, 410, 6.8, 28.0],
            ['2026-11-21 08:55', 7.4, 360, 4.2, 27.9],
            ['2026-11-23 14:50', 7.2, 320, 3.1, 27.5],
        ];

        foreach ($data as $i => [$waktu, $ph, $tds, $kekeruhan, $suhu]) {
            $perangkat->percobaan()->create([
                'nomor' => $i + 1,
                'diukur_pada' => Carbon::parse($waktu, $zona)->utc(),
                'ph' => $ph,
                'tds' => $tds,
                'kekeruhan' => $kekeruhan,
                'suhu' => $suhu,
            ]);
        }
    }
}
