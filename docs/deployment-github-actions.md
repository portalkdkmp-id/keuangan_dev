# Deployment Lokal dan GitHub Actions

## Menjalankan lokal tanpa Nginx

Mode lokal memakai `compose.local.yaml` dan menjalankan `php artisan serve` hanya di container lokal. Konfigurasi production di `compose.yaml` tetap menggunakan PHP-FPM.

Siapkan `.env` lokal:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=<IP_DATABASE_YANG_DAPAT_DIAKSES_DARI_DOCKER>
DB_PORT=5432
DB_DATABASE=<DATABASE_NAME>
DB_USERNAME=<DATABASE_USER>
DB_PASSWORD=<DATABASE_PASSWORD>
```

Jika `.env` lokal belum memiliki key, generate sekali saja untuk environment lokal:

```sh
cp .env.example .env
php artisan key:generate
```

Jalankan aplikasi:

```sh
./scripts/local-docker.sh up
```

Buka `http://localhost:8000`. Command lain dipisahkan berdasarkan operasi:

```sh
./scripts/local-docker.sh ps
./scripts/local-docker.sh logs
./scripts/local-docker.sh down
./scripts/local-docker.sh rebuild
./scripts/local-docker.sh up-with-queue
```

Jika port 8000 sudah digunakan:

```sh
LOCAL_APP_PORT=8080 docker compose -f compose.local.yaml up -d --build app
```

Kemudian gunakan `APP_URL=http://localhost:8080` dan buka `http://localhost:8080`. Migration tidak dijalankan otomatis pada mode lokal maupun production.

## Cara kerja workflow production

Workflow `.github/workflows/deploy.yml` berjalan:

- otomatis setelah workflow `tests` pada branch `main` sukses;
- manual melalui tab **Actions > deploy-production > Run workflow**;
- satu deployment pada satu waktu;
- melalui SSH dengan strict host-key checking;
- melakukan `git fetch`, fast-forward source, lalu menjalankan `scripts/deploy.sh` di VM;
- tidak menjalankan migration, seed, reset database, atau key generation.

Workflow menggunakan GitHub Environment bernama `production`. Sebaiknya aktifkan required reviewer pada environment tersebut jika deployment harus mendapat approval manusia.

## Persiapan Application VM

Contoh membuat user deployment:

```sh
sudo adduser deploy
sudo usermod -aG docker deploy
sudo mkdir -p /var/www/keuangan
sudo chown deploy:deploy /var/www/keuangan
```

Logout/login kembali setelah menambahkan group Docker. Clone repository dan siapkan environment production sebagai user `deploy`:

```sh
sudo -iu deploy
git clone <repository-url> /var/www/keuangan
cd /var/www/keuangan
cp .env.example .env
chmod 600 .env
# Edit .env menggunakan nilai production dan APP_KEY persistent.
docker compose build
docker compose up -d
```

Jika repository private, VM harus memiliki akses pull tersendiri. Gunakan GitHub Deploy Key read-only pada repository atau credential GitHub lain yang dibatasi hanya untuk repository ini. SSH key workflow yang menghubungkan runner ke VM adalah fungsi yang berbeda.

Pastikan perintah berikut berhasil tanpa `sudo` sebagai user deployment:

```sh
cd /var/www/keuangan
git fetch origin main
docker compose version
docker compose ps
```

## Membuat SSH key untuk GitHub Actions

Jalankan di komputer administrator, bukan di repository:

```sh
ssh-keygen -t ed25519 -a 64 -C "github-actions-keuangan" -f github-actions-keuangan
```

Untuk automation non-interaktif, biarkan passphrase kosong. Perintah menghasilkan:

- `github-actions-keuangan`: private key untuk secret `SSH_PRIVATE_KEY`;
- `github-actions-keuangan.pub`: public key yang dipasang pada VM.

Pasang public key untuk user deployment:

```sh
ssh-copy-id -i github-actions-keuangan.pub -p 22 deploy@<APPLICATION_VM_IP>
```

Atau tambahkan isi `.pub` ke `/home/deploy/.ssh/authorized_keys`. Pastikan permission server:

```sh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys
```

Uji sebelum memasukkan key ke GitHub:

```sh
ssh -i github-actions-keuangan -p 22 deploy@<APPLICATION_VM_IP>
```

Setelah secret tersimpan dan koneksi teruji, hapus salinan private key dari workstation bila kebijakan pengelolaan key organisasi mengharuskannya. Jangan commit kedua file key.

## Mendapatkan SSH known hosts

Ambil public host key dari jaringan tepercaya:

```sh
ssh-keyscan -p 22 -H <APPLICATION_VM_IP>
```

Sebelum menyimpannya, verifikasi fingerprint melalui console VM atau kanal administrator terpisah. Pada VM:

```sh
sudo ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub
```

Bandingkan dengan hasil berikut pada komputer administrator:

```sh
ssh-keyscan -p 22 <APPLICATION_VM_IP> | ssh-keygen -lf -
```

Setelah cocok, simpan seluruh baris hasil `ssh-keyscan -H` sebagai secret `SSH_KNOWN_HOSTS`. Ulangi proses jika host key VM berubah secara sah.

## GitHub Secrets yang dibutuhkan

Buka repository GitHub, lalu **Settings > Environments > New environment**, buat environment `production`. Tambahkan Environment secrets berikut:

| Secret | Nilai | Cara mendapatkannya |
|---|---|---|
| `SSH_HOST` | IP private/public atau hostname Application VM | Dari penyedia VM, DNS, atau administrator jaringan |
| `SSH_PORT` | Port SSH, biasanya `22` | Dari `/etc/ssh/sshd_config` atau administrator VM |
| `SSH_USER` | User deployment, contoh `deploy` | User Linux yang disiapkan di Application VM |
| `SSH_PRIVATE_KEY` | Seluruh isi private key termasuk header/footer | Isi file `github-actions-keuangan` yang dibuat dengan `ssh-keygen` |
| `SSH_KNOWN_HOSTS` | Baris host key server | Hasil `ssh-keyscan -p <port> -H <host>` setelah fingerprint diverifikasi |

Jangan menyimpan `.env`, `APP_KEY`, atau database password di workflow ini. Semua runtime secrets tetap berada di `/var/www/keuangan/.env` pada VM dan dibaca Compose melalui `env_file`.

## GitHub Variables yang dibutuhkan

Pada environment `production`, tambahkan variables berikut melalui **Settings > Environments > production > Environment variables**:

| Variable | Nilai default | Keterangan |
|---|---|---|
| `DEPLOY_PATH` | `/var/www/keuangan` | Absolute path checkout pada Application VM |
| `DEPLOY_BRANCH` | `main` | Branch yang di-fast-forward saat deployment |
| `DEPLOY_QUEUE` | `false` | Ubah menjadi `true` jika queue worker production harus dijalankan |

Workflow memiliki default di atas, tetapi mendefinisikannya secara eksplisit membuat konfigurasi environment mudah diaudit.

## Firewall dan konektivitas

GitHub-hosted runner perlu mencapai port SSH VM. Jika SSH hanya tersedia melalui private network, gunakan self-hosted runner di jaringan tersebut atau VPN/tunnel organisasi. Jangan membuka PostgreSQL ke GitHub runner. Database VM cukup mengizinkan TCP 5432 dari private IP Application VM.

Batasi SSH dengan firewall, key authentication, dan nonaktifkan password login bila sesuai kebijakan server. GitHub-hosted runner memakai rentang IP yang dapat berubah, sehingga allowlist statis perlu mengikuti metadata GitHub atau menggunakan self-hosted runner/bastion.

## Deployment manual dan troubleshooting

Jalankan workflow manual dari tab Actions. Di VM, periksa:

```sh
cd /var/www/keuangan
docker compose ps
docker compose logs --tail=100 app
git status --short
```

Workflow sengaja memakai fast-forward only. Deployment berhenti jika checkout VM memiliki perubahan source lokal atau branch telah divergen. `.env`, `storage`, dan `public/build` di-ignore dan tidak seharusnya menghalangi update source.

Migration tetap keputusan manual operator:

```sh
cd /var/www/keuangan
docker compose exec app php artisan migrate --force
```
