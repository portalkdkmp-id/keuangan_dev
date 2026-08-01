# Phase 5 - Finance Director Review dan Pencairan Dana

Phase 5 menyelesaikan workflow setelah Finance Approver menyetujui pengajuan.

## Status Baru

- `director_in_review`
- `director_revision_requested`
- `pending_disbursement`
- `fund_disbursed`
- `director_rejected`

`director_review` sudah ada dari Phase 4 dan sekarang menjadi antrean awal Finance Director.

## Alur

```text
Finance Approval Approve
-> director_review
-> director_in_review
-> pending_disbursement
-> fund_disbursed
```

Director juga dapat memilih:

- `director_in_review -> fund_disbursed` untuk approve dan bayar langsung.
- `director_in_review -> director_revision_requested` untuk revisi ke Finance Approver.
- `director_revision_requested -> director_review` setelah Finance Approver resubmit.
- `director_in_review -> director_rejected` untuk penolakan final.

## Tabel Baru

- `submission_director_reviews`: histori review dan keputusan Finance Director.
- `submission_disbursements`: histori pencairan satu kali per pengajuan pada Phase 5.
- `disbursement_attachments`: bukti transfer private.

## Service

- `DirectorSubmissionService`: seluruh mutasi workflow Finance Director.
- `DisbursementService`: nomor pencairan, snapshot rekening tujuan, upload bukti transfer, dan audit.
- `DirectorMonitoringService`: agregasi dashboard Finance Director.

Semua mutasi memakai transaction, row lock, status transition service, audit log, dan notification setelah commit.

## Nomor Pencairan

Format:

```text
DISB/YYYY/MM/000001
```

Nomor dibuat lewat `DocumentNumberService` dan tabel `document_sequences`, bukan `MAX(id)`.

## Batasan Phase 5

- Tidak ada partial payment.
- Tidak ada settlement penggunaan dana.
- Tidak ada rekonsiliasi bank.
- Tidak ada integrasi bank.
- Pencairan `fund_disbursed` bersifat final.
