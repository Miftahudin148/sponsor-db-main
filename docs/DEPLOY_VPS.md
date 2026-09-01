# Deploy VPS — ICM Sponsor (50 user baca)

> Target: VPS MySQL 8.x + nginx + php-fpm, 50 orang buka bersamaan (mostly baca). SQLite **jangan** dipakai produksi.

## 1. Persiapan .env produksi
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sponsor.icm.or.id

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sponsor_db
DB_USERNAME=icm_user
DB_PASSWORD=***

SESSION_DRIVER=database   # MySQL row-lock aman
CACHE_STORE=file          # file lebih ringan dari database untuk 50 baca
QUEUE_CONNECTION=database
```

## 2. Instalasi
```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
php artisan db:seed --class=MasterDataSeeder   # taksonomi awal
php artisan storage:link
npm install && npm run build
```

## 3. Optimasi Laravel
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 4. nginx + php-fpm (jangan php artisan serve)
```nginx
server {
    listen 80;
    server_name sponsor.icm.or.id;
    root /var/www/sponsor-db-main/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1y; add_header Cache-Control "public, immutable";
    }
}
```

## 5. OPcache (php.ini)
```
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
```

## 6. Cache aplikasi
- `KontakStatsOverview`, `KontakKategoriBarChart`, `TopEventBarChart`, `PetaNomorPerusahaan` sudah `Cache::remember 60s` + invalidate on save/delete (`AppServiceProvider::boot`).
- Pencarian `debounce 600ms` untuk hindari scan `LIKE '%...%'` bertubi.

## 7. Verifikasi beban
- 50 tab buka `/admin` → <1s
- 1 Import Excel (300 baris) + 5 Export CSV bersamaan → tidak `500`

## 8. Rollback ke SQLite (dev/test saja)
```env
DB_CONNECTION=sqlite
DB_DATABASE=/path/to/database.sqlite
# pragma tetap WAL/NORMAL/5000 di config/database.php:43
```
