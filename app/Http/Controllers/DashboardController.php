<?php

namespace App\Http\Controllers;

use App\Models\Perangkat;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $awal = Perangkat::utama()->dataDashboard() + [
            'sensor' => config('kualitas_air.sensor'),
            'url' => [
                'sinkron' => route('api.sinkron'),
                'daya' => route('api.daya'),
            ],
        ];

        return view('dashboard', ['awal' => $awal]);
    }
}
