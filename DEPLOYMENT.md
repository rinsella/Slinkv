# Deployment Guide

## Persyaratan Server

- PHP 8.2+ dengan ekstensi: PDO, OpenSSL, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath, Fileinfo
- MySQL 8+ (atau MariaDB 10.6+) atau SQLite 3.35+
- Nginx atau Apache dengan `mod_rewrite`
- Composer 2+
- Akses cron

## Langkah Deploy

```bash
git clone <repo> /var/www/slinkv
cd /var/www/slinkv
composer install --no-dev --optimize-autoloader
cp .env.example .env
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

Lalu buka `/install.php` lewat browser dan ikuti wizard. Setelah selesai:

```bash
rm public/install.php
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Nginx

```nginx
server {
    listen 443 ssl http2;
    server_name slinkv.net;
    root /var/www/slinkv/public;
    index index.php;

    ssl_certificate     /etc/letsencrypt/live/slinkv.net/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/slinkv.net/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

## Apache `.htaccess`

Laravel sudah menyediakan `public/.htaccess`. Pastikan `AllowOverride All`.

## Cron

```cron
* * * * * cd /var/www/slinkv && php artisan schedule:run >> /dev/null 2>&1
```

## Queue Worker (jika diperlukan)

Buat unit systemd `slinkv-queue.service`:

```ini
[Unit]
Description=SlinkV Queue Worker
After=network.target

[Service]
User=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/slinkv/artisan queue:work --sleep=3 --tries=3 --max-time=3600
WorkingDirectory=/var/www/slinkv

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable --now slinkv-queue
```

## Backup

- Database: `mysqldump` harian, simpan 14 hari terakhir.
- Storage: rsync `storage/app` ke remote.

## Update Aplikasi

```bash
php artisan down
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache route:cache view:cache
php artisan up
```
