# Security

## Pengerasan Wajib di Produksi

- `APP_DEBUG=false` di `.env`
- `APP_ENV=production`
- HTTPS dipaksa (set `APP_URL=https://...` + redirect 80→443 di Nginx)
- Hapus `public/install.php` setelah instalasi
- File `storage/installed.lock` tidak boleh terhapus
- File permission: `storage/` & `bootstrap/cache/` writable hanya untuk user web (`www-data`), bukan world-writable

## Security Headers

Diatur otomatis oleh `app/Http/Middleware/SecurityHeaders.php`:
- `X-Frame-Options: SAMEORIGIN`
- `X-Content-Type-Options: nosniff`
- `Referrer-Policy: strict-origin-when-cross-origin`
- CSP membatasi script & style ke domain self + CDN yang dipercaya (cdn.tailwindcss.com, cdn.jsdelivr.net, fonts.bunny.net)

## CSRF

Semua form POST/PUT/PATCH/DELETE menggunakan `@csrf`. Endpoint redirect publik (`GET /{slug}`) dikecualikan karena read-only.

## Rate Limiting

Middleware `throttle` diterapkan di:
- `auth` routes (login/register/password)
- `redirect` engine (per-IP)
- `quick-shorten` (anonymous)

## Bot Protection

`BotDetectionService` mengevaluasi user agent, header, dan perilaku. Klik bot dicatat ke `bot_logs` & tidak menambah counter human.

## Data Sensitif

- Password user di-hash bcrypt.
- IP visitor di-hash SHA-256 sebelum disimpan ke `click_logs.ip_hash`.
- Tidak ada IP plaintext yang disimpan.

## Backup & Audit

- Backup database harian.
- Audit log admin tersedia (klik logs, bot logs, payment status).

## Pelaporan Kerentanan

Laporkan ke `support@slinkv.net`. Jangan publish exploit sebelum patch dirilis.
