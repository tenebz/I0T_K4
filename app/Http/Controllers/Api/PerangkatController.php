<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Perangkat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PerangkatController extends Controller
{
    /** Tombol "Sinkronkan" di dashboard: ambil data terbaru dari server. */
    public function sinkron(): JsonResponse
    {
        $perangkat = Perangkat::utama();

        if (! $perangkat->isOnline()) {
            return $this->tolak($perangkat, 'Perangkat sedang offline. Pastikan perangkat IoT menyala dan terhubung ke Wi-Fi.');
        }

        if (! $perangkat->menyala) {
            return $this->tolak($perangkat, 'Alat sedang mati. Nyalakan alat dulu, lalu sinkronkan.');
        }

        $perangkat->forceFill(['terakhir_sinkron' => now()])->save();

        return response()->json($perangkat->dataDashboard());
    }

    /** Saklar "Alat" di dashboard. Alat membaca status ini lewat ping. */
    public function daya(Request $request): JsonResponse
    {
        $data = $request->validate(['menyala' => ['required', 'boolean']]);
        $perangkat = Perangkat::utama();

        if (! $perangkat->isOnline()) {
            return $this->tolak($perangkat, 'Alat tidak merespons. Periksa koneksi alat, lalu coba lagi.');
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
