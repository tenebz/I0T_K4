<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perangkat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerangkatController extends Controller
{
    /** Tombol "Sinkronkan" di dashboard: ambil data terbaru dari server. */
    public function sinkron(Request $request): JsonResponse
    {
        // Opsional (dikirim dashboard): percobaan yang sudah dimiliki browser, supaya hanya
        // percobaan baru yang dikirim. Tanpa parameter ini responsnya tetap data lengkap.
        $sejak = $request->validate([
            'sejak_trial' => ['nullable', 'integer', 'min:1'],
            'sejak_nomor' => ['nullable', 'integer', 'min:0', 'required_with:sejak_trial'],
        ]);

        $perangkat = Perangkat::utama();

        if (! $perangkat->isOnline()) {
            return $this->tolak($perangkat, 'The device is offline. Make sure the IoT device is powered on and connected to Wi-Fi.');
        }

        if (! $perangkat->menyala) {
            return $this->tolak($perangkat, 'The device is turned off. Turn it on first, then sync.');
        }

        $perangkat->forceFill(['terakhir_sinkron' => now()])->save();

        if (isset($sejak['sejak_trial'])) {
            return response()->json($perangkat->dataSinkronTambahan((int) $sejak['sejak_trial'], (int) $sejak['sejak_nomor']));
        }

        return response()->json($perangkat->dataSinkron());
    }

    /** Saklar "Alat" di dashboard. Alat membaca status ini lewat ping. */
    public function daya(Request $request): JsonResponse
    {
        $data = $request->validate(['menyala' => ['required', 'boolean']]);
        $perangkat = Perangkat::utama();

        if (! $perangkat->isOnline()) {
            return $this->tolak($perangkat, 'The device is not responding. Check its connection, then try again.');
        }

        $perangkat->update(['menyala' => $data['menyala']]);

        return response()->json(['perangkat' => $perangkat->untukDashboard()]);
    }

    /** Dipanggil alat IoT secara berkala. Balasannya memberi tahu alat harus menyala atau mati. */
    public function ping(): JsonResponse
    {
        $perangkat = Perangkat::utama();
        $perangkat->tandaiOnline();

        return response()->json(['menyala' => $perangkat->menyala]);
    }

    private function tolak(Perangkat $perangkat, string $pesan): JsonResponse
    {
        return response()->json([
            'message' => $pesan,
            'perangkat' => $perangkat->untukDashboard(),
        ], 409);
    }
}
