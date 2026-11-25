<?php

return [

    /*
    | Zona waktu untuk menampilkan tanggal & jam di dashboard (WIB).
    */
    'zona_waktu' => 'Asia/Jakarta',

    /*
    | Perangkat dianggap offline bila tidak mengirim ping/data selama N menit.
    | 0 = deteksi offline dimatikan (perangkat selalu dianggap online).
    | Isi misalnya 2 setelah alat IoT rutin memanggil POST /api/perangkat/ping.
    */
    'batas_offline_menit' => (int) env('IOT_BATAS_OFFLINE_MENIT', 0),

    /*
    | Kunci opsional untuk endpoint yang dipanggil alat IoT.
    | Bila diisi, alat wajib mengirim header "X-Api-Key: <kunci>".
    */
    'api_key' => env('IOT_API_KEY'),

    /*
    | Ambang setiap sensor.
    | min / max     : batas aman (null = tidak ada batas di sisi itu)
    | ketat         : true = nilai yang SAMA dengan max sudah dianggap buruk
    | waspada_*     : mulai dari nilai ini status "mendekati batas"
    */
    'sensor' => [
        'ph' => [
            'nama' => 'pH', 'satuan' => '', 'desimal' => 1,
            'min' => 6.5, 'max' => 8.5, 'ketat' => false,
            'waspada_bawah' => 6.8, 'waspada_atas' => 8.2,
            'rentang' => '6.5–8.5',
        ],
        'tds' => [
            'nama' => 'TDS', 'satuan' => 'ppm', 'desimal' => 0,
            'min' => null, 'max' => 500, 'ketat' => true,
            'waspada_bawah' => null, 'waspada_atas' => 450,
            'rentang' => '< 500 ppm',
        ],
        'kekeruhan' => [
            'nama' => 'Turbidity', 'satuan' => 'NTU', 'desimal' => 1,
            'min' => null, 'max' => 5, 'ketat' => true,
            'waspada_bawah' => null, 'waspada_atas' => 4,
            'rentang' => '< 5 NTU',
        ],
        'suhu' => [
            'nama' => 'Temperature', 'satuan' => '°C', 'desimal' => 1,
            'min' => 25, 'max' => 30, 'ketat' => false,
            'waspada_bawah' => 25.5, 'waspada_atas' => 29.5,
            'rentang' => '25–30 °C',
        ],
    ],

];
