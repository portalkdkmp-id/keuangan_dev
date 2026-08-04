# Phase 8 - Uang Panjar

## Ringkasan

Uang panjar menggunakan `financial_submissions` sebagai dokumen workflow utama. Review Finance Staff, Finance Approval, Director, pencairan, notifikasi, audit, reimbursement selisih, dan pengembalian sisa dana memakai layanan existing. Domain `advance_details` menyimpan data khusus panjar dan `fund_accountability_reports` dipakai kembali sebagai settlement dengan `source_type = advance`.

## Hak Akses

- Finance Staff dan Super Admin dapat membuat, mengubah, dan mengajukan panjar.
- PIC tidak dapat membuat uang panjar.
- Pembuat panjar tidak dapat melakukan validasi Finance atas pengajuannya sendiri.
- Penanggung jawab panjar tidak dapat mereview settlement miliknya sendiri.
- Finance Approver dan Finance Director mengikuti tahap workflow existing.

## Alur Pengajuan

1. Finance Staff memilih Uang Panjar dari dialog Tambah Pengajuan.
2. User mengisi informasi, estimasi, rekening penerima, deadline settlement, dan dokumen pendukung.
3. Draft diajukan ke Finance Staff lain untuk direview.
4. Pengajuan diteruskan ke Finance Approver dan Finance Director.
5. Setelah dana dicairkan, status domain menjadi `settlement_due` dan kewajiban settlement tampil untuk penanggung jawab.

## Alur Settlement

1. Penanggung jawab mengisi ringkasan dan transaksi aktual.
2. Setiap transaksi wajib memiliki minimal satu bukti pembelian/sewa dan satu bukti pembayaran.
3. Finance Staff lain mereview, lalu Finance Approver menyetujui settlement.
4. Jika realisasi sama dengan panjar, settlement langsung ditutup.
5. Jika ada sisa, status menjadi `return_pending` dan user membuat pengembalian dana.
6. Jika ada kekurangan, status menjadi `reimbursement_pending` dan sistem membuat draft reimbursement selisih.
7. Panjar ditutup setelah pengembalian atau reimbursement selesai.

## Konfigurasi

```env
ADVANCE_SUPPORTING_DOCUMENT_THRESHOLD=0
ADVANCE_DEFAULT_SETTLEMENT_DAYS=14
ADVANCE_MAX_SETTLEMENT_DAYS=30
```

Nilai threshold `0` mewajibkan dokumen pendukung untuk seluruh pengajuan panjar.

## Struktur Utama

- `advance_details`: detail, snapshot rekening, nominal, deadline, dan status domain panjar.
- `fund_accountability_reports.source_type`: membedakan laporan dana biasa dan settlement panjar.
- `fund_accountability_items`: transaksi realisasi, metode pembayaran, dan referensi.
- `fund_accountability_attachments.fund_accountability_item_id`: bukti per transaksi.
- `fund_returns.source_advance_detail_id`: sumber pengembalian sisa panjar.
- `reimbursement_details.source_advance_detail_id`: sumber reimbursement kekurangan panjar.
