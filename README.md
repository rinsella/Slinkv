# SlinkV

URL shortener SaaS dengan analytics real-time, proteksi bot, dan dashboard admin lengkap.

## Stack

- Laravel 11 · PHP 8.2+
- MySQL atau SQLite
- Tailwind CSS + Alpine.js + Chart.js (CDN)

## Instalasi (Web Installer)

1. Clone / unggah seluruh berkas ke server.
2. Pastikan `storage/` dan `bootstrap/cache/` writable (`chmod -R 775`).
3. Jalankan `composer install --no-dev --optimize-autoloader`.
4. Arahkan browser ke `https://domain-anda/install.php`.
5. Ikuti wizard: Welcome → Requirements → Database → Site → Admin → Install → Done.
6. **Hapus `public/install.php`** setelah selesai.

## Instalasi Manual

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

Buat user admin lewat tinker:
```bash
php artisan tinker
>>> \App\Models\User::create(['name'=>'Admin','email'=>'admin@example.com','password'=>bcrypt('secret123'),'role'=>'admin','status'=>'active']);
```

## Menjalankan (dev)

```bash
php artisan serve
```

## Struktur Penting

- `app/Http/Controllers/RedirectController.php` — engine pengarah link pendek
- `app/Http/Controllers/Dashboard/*` — area user
- `app/Http/Controllers/Admin/*` — area admin
- `app/Services/BotDetectionService.php` — deteksi bot
- `app/Services/ShortLinkService.php` — generator slug + reserved list
- `resources/views/` — Blade templates

## Dokumentasi Lain

- [DEPLOYMENT.md](DEPLOYMENT.md) — panduan deploy produksi
- [SECURITY.md](SECURITY.md) — pengerasan keamanan

## Lisensi

Proprietary © SlinkV
