# I0T_K4

Kelompok ini radak stress

## Dashboard Monitoring Kualitas Air — IoT

Aplikasi Laravel untuk memantau kualitas air (sampel Air A) dari alat IoT dengan 4 sensor: pH, TDS, kekeruhan, dan suhu. Setiap pembacaan dicatat sebagai satu percobaan, lalu dievaluasi otomatis sebagai **Baik** atau **Buruk**.

Fitur:

- Dashboard: status terkini, kartu sensor dengan tren, grafik riwayat (satu sensor atau 2×2)
- Evaluasi: tabel semua percobaan, saringan Baik/Buruk, pencarian, detail per percobaan
- Saklar nyala/mati alat dan tombol sinkronisasi
- Notifikasi percobaan yang buruk atau mendekati batas

## Menjalankan

Kebutuhan: PHP 8.3+ dan Composer (misalnya lewat Laragon).

```bash
composer install
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate
php artisan migrate --seed  # membuat tabel + 5 percobaan contoh
php artisan serve
```

Buka <http://localhost:8000>.

## API untuk alat IoT

| Metode | Endpoint | Isi | Fungsi |
| --- | --- | --- | --- |
| POST | `/api/percobaan` | `{"ph":7.2,"tds":320,"kekeruhan":3.1,"suhu":27.5}` | Menyimpan satu pembacaan sensor |
| POST | `/api/perangkat/ping` | – | Menandai alat online; balasan `{"menyala":true}` memberi tahu alat harus menyala atau mati |

Contoh:

```bash
curl -X POST http://localhost:8000/api/percobaan \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"ph":7.2,"tds":320,"kekeruhan":3.1,"suhu":27.5}'
```

Pengaturan di `.env`:

- `IOT_API_KEY=...` — bila diisi, alat wajib mengirim header `X-Api-Key` dengan nilai yang sama.
- `IOT_BATAS_OFFLINE_MENIT=2` — alat dianggap offline bila tidak mengirim ping/data selama 2 menit. Default `0` (deteksi offline mati).

Ambang batas sensor ada di [`config/kualitas_air.php`](config/kualitas_air.php).

## Struktur utama

- `resources/views/dashboard.blade.php` — tampilan halaman
- `public/js/dashboard.js` — logika interaktif (Alpine.js)
- `public/css/dashboard.css` — gaya tampilan
- `app/Http/Controllers/` — controller halaman dan API
- `app/Models/Perangkat.php`, `app/Models/Percobaan.php` — model data
