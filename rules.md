# Project Rules

Dokumen ini menjadi aturan kerja untuk pengembangan aplikasi pencatatan keuangan kantor KDKMP.

## Prinsip Umum

- Pertahankan pola Laravel + Inertia + React yang sudah ada.
- Jaga perubahan tetap kecil, jelas, dan sesuai domain modul yang diubah.
- Jangan menghapus atau mengganti alur bisnis tanpa memastikan dampaknya ke role PIC, finance staff, finance approval, dan super admin.
- Gunakan service class untuk logika bisnis yang mengubah data penting.
- Controller fokus pada authorization, request validation, pemanggilan service, dan response Inertia/redirect.
- Request validation harus berada di `app/Http/Requests` untuk flow utama.
- Semua mutasi penting harus memakai transaction jika menyentuh lebih dari satu tabel.

## Backend

- PHP mengikuti Laravel Pint.
- Model menggunakan UUID bila tabel menggunakan UUID primary key.
- Gunakan enum untuk status atau tipe yang punya daftar nilai tetap.
- Gunakan policy/permission sebelum membuka aksi ke user.
- Status pengajuan hanya boleh berubah lewat service status/submission yang jelas.
- Simpan audit log untuk aksi penting seperti create, update, delete, submit, review, revision, reject, dan forward approval.
- Hindari query ad hoc di React; data halaman harus disiapkan dari controller.
- Jangan menampilkan data lintas role tanpa policy.

## Frontend

- Gunakan React + Inertia pages di `resources/js/pages`.
- Gunakan komponen UI shadcn yang sudah tersedia di `resources/js/components/ui`.
- Semua list data harus menggunakan `components/ui/table.tsx`, bukan `<table>` manual.
- Semua select harus menggunakan `components/ui/select.tsx`, bukan native `<select>`.
- Gunakan Sonner untuk feedback success, warning, dan error.
- Form pengajuan dana harus mobile-first.
- Format tanggal tampilan menggunakan `dd/mm/yyyy`.
- Jangan tampilkan daftar pengajuan lain di halaman detail pengajuan.
- Attachment gambar harus ditampilkan sebagai preview bila mime type adalah image.

## Alur Pengajuan Dana

- Satu pengajuan dana adalah satu record utama di `financial_submissions`.
- Tabel `submission_items` hanya dipakai sebagai detail internal satu baris untuk kompatibilitas kalkulasi dan workflow lama.
- User tidak boleh mengirim banyak item pengajuan.
- PIC KDKMP hanya boleh memilih kategori:
  - Pengajuan Dana KDKMP
  - Pengajuan Reimbursement
- PIC KDKMP tidak boleh memilih Operasional tim Sales.
- Finance staff melakukan review pada status `finance_review`.
- Review finance staff menyimpan:
  - catatan staff keuangan
  - datetime direview
  - nominal review
  - alasan penolakan bila ditolak
- Status tetap `finance_review` selama staff hanya menyimpan review.
- Status berubah jika staff memilih aksi:
  - Request Revisi
  - Tolak Pengajuan
  - Ajukan ke Approval

## Database dan Migration

- Migration harus reversible dengan `down`.
- Foreign key harus jelas perilaku delete-nya.
- Data master yang wajib tersedia dibuat lewat seeder.
- Jangan mengubah migration lama yang sudah dianggap berjalan di environment bersama; buat migration baru untuk perubahan lanjutan.

## Perjalanan Dana dan Pertanggungjawaban

- Submission, disbursement, distribution, receipt confirmation, dan accountability adalah domain terpisah.
- Snapshot rekening pencairan/distribusi harus diambil backend dari master rekening.
- Nomor rekening tidak boleh tampil utuh dalam audit log atau notification.
- Distribusi wajib memakai transaction, `lockForUpdate`, dan perhitungan decimal-safe.
- Total distribusi tidak boleh melebihi pencairan dan konfirmasi penerimaan tidak boleh ganda.
- `received_amount` accountability berasal dari konfirmasi penerimaan; total realisasi dihitung backend dari item.
- Attachment dana selalu private dan download wajib melalui policy.

## Testing dan Verifikasi

Jalankan verifikasi setelah perubahan signifikan:

```bash
./vendor/bin/pint
npm run types:check
env DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test
npm run build
```

Catatan: wrapper test project ini bisa mengembalikan exit code `1` walaupun JSON output menyatakan semua test passed. Gunakan isi JSON sebagai rujukan hasil test.

## Git dan File

- Jangan revert perubahan user tanpa instruksi eksplisit.
- Jangan menjalankan command destruktif seperti `git reset --hard`.
- Jangan commit file build kecuali memang diminta.
- Hindari refactor besar yang tidak berkaitan langsung dengan task.
