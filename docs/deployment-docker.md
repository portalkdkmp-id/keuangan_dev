# Deployment Docker Production

Untuk mode lokal tanpa Nginx dan setup deployment SSH GitHub Actions, lihat `docs/deployment-github-actions.md`.

## Arsitektur

Application VM menjalankan Nginx secara native serta container PHP 8.4 FPM. PostgreSQL tetap berada di Database VM terpisah. Setup ini tidak membuat container Nginx, PostgreSQL, Redis, atau Node runtime.

Checkout aplikasi wajib berada di `/var/www/keuangan`. Path yang sama dipakai sebagai `root` Nginx host dan `WORKDIR` container sehingga `SCRIPT_FILENAME` FastCGI valid di kedua sisi. `./public` di-bind mount agar Nginx host dapat membaca asset Vite yang disalin dari image saat container mulai. `./storage` juga di-bind mount, sehingga upload private/public, session filesystem (bila dipakai), cache filesystem, dan log bertahan saat container atau image diganti.

Service `app` selalu aktif dan hanya memetakan FPM ke `127.0.0.1:9000`. Service `queue` memakai image yang sama dan tersedia melalui profile opsional `queue`. Source tidak memiliki scheduled task, sehingga scheduler container tidak dibuat. Saat inspeksi juga tidak ditemukan queued job; queue tetap tersedia jika kebutuhan itu ditambahkan kemudian.

Healthcheck container memvalidasi konfigurasi FPM. Endpoint Laravel `GET /up` sudah disediakan framework dan dapat dipantau melalui Nginx setelah host dikonfigurasi.

## Prasyarat server

- Docker Engine dan plugin Docker Compose
- Nginx pada Application VM
- checkout repository di `/var/www/keuangan`
- akses TCP dari Application VM ke private IP Database VM port 5432
- firewall PostgreSQL hanya mengizinkan IP/private subnet Application VM; jangan membuka 5432 ke `0.0.0.0/0`
- DNS dan sertifikat TLS untuk domain production

Pastikan user deployment dapat menjalankan Docker dan membaca checkout. Entrypoint mengatur ownership hanya pada `storage`, `bootstrap/cache`, dan hasil `public/build`; ia tidak mengubah database.

## Environment production

Salin contoh environment hanya untuk deployment pertama, lalu edit nilainya:

```sh
cd /var/www/keuangan
cp .env.example .env
chmod 600 .env
```

Nilai minimum yang harus diperiksa:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://finance.example.com
APP_KEY=<existing-persistent-production-key>

LOG_LEVEL=warning

DB_CONNECTION=pgsql
DB_HOST=<PRIVATE_IP_DATABASE_VM>
DB_PORT=5432
DB_DATABASE=<DATABASE_NAME>
DB_USERNAME=<DATABASE_USER>
DB_PASSWORD=<DATABASE_PASSWORD>

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
```

Jangan commit `.env`, memasukkannya ke image, mengganti `APP_KEY` saat redeploy, atau menjalankan `key:generate` jika key production sudah tersedia. `FPM_PORT=9000` dapat ditambahkan jika port localhost perlu diganti; sesuaikan pula `fastcgi_pass` Nginx.

## First deployment

```sh
sudo mkdir -p /var/www/keuangan
sudo chown "$USER":"$USER" /var/www/keuangan
git clone <repository-url> /var/www/keuangan
cd /var/www/keuangan
cp .env.example .env
# Edit .env dan isi seluruh nilai production, termasuk APP_KEY existing.
docker compose build
docker compose up -d
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
docker compose ps
```

Route cache telah dievaluasi dan dapat diuji sebagai bagian deployment. Jika perubahan route di masa depan membuat command gagal, perbaiki route closure tersebut sebelum deployment; script berhenti agar cache setengah jadi tidak diabaikan.

Migration tidak pernah dijalankan otomatis. Hanya jika operator telah meninjau migration dan memang diperlukan:

```sh
docker compose exec app php artisan migrate --force
```

Jangan gunakan `migrate:fresh`, `db:wipe`, reset, atau seed pada database production existing.

Jika public storage memang akan dilayani langsung, buat symlink sekali (aplikasi saat ini terutama memakai disk private):

```sh
docker compose exec app php artisan storage:link
```

## Nginx host dan TLS

Salin `deploy/nginx/app.conf.example`, ganti `server_name`, lalu aktifkan site:

```sh
sudo cp deploy/nginx/app.conf.example /etc/nginx/sites-available/keuangan.conf
sudo ln -s /etc/nginx/sites-available/keuangan.conf /etc/nginx/sites-enabled/keuangan.conf
sudo nginx -t
sudo systemctl reload nginx
```

Tambahkan server block TLS/certificate sesuai pengelola sertifikat VM. Redirect HTTP ke HTTPS setelah TLS aktif. Root host dan path container sengaja sama-sama `/var/www/keuangan/public`; jangan mengganti salah satunya saja. Port FPM tidak dapat diakses melalui interface publik karena Compose bind ke loopback.

## Routine deployment

Review perubahan dan backup database sesuai prosedur organisasi, lalu:

```sh
cd /var/www/keuangan
git pull --ff-only
./scripts/deploy.sh
```

Jalankan app, queue worker, dan scheduler dengan image yang sama:

```sh
docker compose up -d
```

Script tidak melakukan pull agar pemilihan branch/revision tetap eksplisit. Script melakukan build, menghentikan worker sebelum perubahan skema, menjalankan migration secara default, membuat ulang cache konfigurasi-route-view, menyalakan queue dan scheduler, lalu memverifikasi seluruh service. Gunakan `RUN_MIGRATIONS=false` hanya ketika deployment memang tidak membawa migration. Script tidak melakukan seed, reset database, atau key generation.

## Pemeriksaan

```sh
docker compose config
docker compose ps
docker compose logs --tail=100 app
docker compose exec app php -v
docker compose exec app php artisan --version
docker compose exec app php -m | grep pdo_pgsql
docker compose exec app test -f public/build/manifest.json
curl --fail https://finance.example.com/up
```

Tes jaringan PostgreSQL dari host dan koneksi read-only dari Laravel:

```sh
nc -vz <DB_HOST> 5432
docker compose exec app php artisan migrate:status
```

`migrate:status` membaca status migration dan tidak menjalankan migration. Alternatif pemeriksaan query read-only:

```sh
docker compose exec app php artisan tinker
DB::select('select 1');
```

## Operasional

```sh
# Log container
docker compose logs -f app
docker compose logs -f queue scheduler

# Log Laravel
docker compose exec app tail -f storage/logs/laravel.log

# Restart
docker compose restart app

# Rebuild bersih
docker compose build --no-cache app
docker compose up -d

# Status
docker compose ps
```

## Rollback

Rollback source/image tidak boleh mereset database. Pilih commit/tag aplikasi yang kompatibel dengan skema database saat ini, lalu rebuild:

```sh
cd /var/www/keuangan
git switch --detach <known-good-tag-or-commit>
docker compose build
docker compose up -d --remove-orphans
docker compose exec app php artisan optimize:clear
docker compose exec app php artisan config:cache
docker compose exec app php artisan route:cache
docker compose exec app php artisan view:cache
```

Sebelum migration manual, rencanakan kompatibilitas rollback aplikasi dan backup database. Migration down/reset bukan bagian prosedur rollback ini.

## Risiko dan catatan

- `public` dan `storage` berada pada disk Application VM; keduanya harus ikut backup. Untuk multi-VM, pindahkan upload ke shared/object storage sebelum horizontal scaling.
- OPcache tidak memeriksa timestamp. Recreate container melalui deployment script setelah perubahan source.
- PHP menerima upload hingga 55 MiB dan Nginx/request body hingga 60 MiB, cukup untuk validasi import XLSX 50 MiB aplikasi.
- Database-backed session, cache, dan queue memerlukan tabel terkait sudah tersedia di database existing.
- Secrets tetap berada hanya di `.env` host. Rotasi credential dilakukan di sana lalu container direcreate.
