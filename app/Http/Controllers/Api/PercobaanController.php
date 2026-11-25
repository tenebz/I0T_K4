<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perangkat;
use App\Models\Trial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PercobaanController extends Controller
{
    /** Alat IoT mengirim satu hasil pembacaan 4 sensor. */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ph' => ['required', 'numeric', 'between:0,14'],
            'tds' => ['required', 'numeric', 'min:0'],
            'kekeruhan' => ['required', 'numeric', 'min:0'],
            'suhu' => ['required', 'numeric', 'between:-10,100'],
            'diukur_pada' => ['nullable', 'date'],
        ]);

        $perangkat = Perangkat::utama();
        $perangkat->tandaiOnline();

        if (! $perangkat->menyala) {
            return response()->json(['message' => 'Alat sedang dimatikan dari dashboard; data tidak disimpan.'], 409);
        }

        // Cari trial aktif, jika ada.
        $activeTrial = Trial::where('perangkat_id', $perangkat->id)
            ->where('is_active', true)
            ->first();

        // Nomor percobaan dimulai dari 1 dalam setiap trial.
        $nomorBerikutnya = $activeTrial
            ? (int) $activeTrial->percobaan()->max('nomor') + 1
            : (int) $perangkat->percobaan()->whereNull('trial_id')->max('nomor') + 1;

        $percobaan = $perangkat->percobaan()->create([
            'trial_id' => $activeTrial?->id,
            'nomor' => $nomorBerikutnya,
            'diukur_pada' => isset($data['diukur_pada'])
                ? Carbon::parse($data['diukur_pada'], config('kualitas_air.zona_waktu'))->utc()
                : now(),
            'ph' => $data['ph'],
            'tds' => $data['tds'],
            'kekeruhan' => $data['kekeruhan'],
            'suhu' => $data['suhu'],
        ]);

        return response()->json(['percobaan' => $percobaan->untukDashboard()], 201);
    }
}
