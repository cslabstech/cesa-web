# UAT Recruitment End-to-End Checklist

Dokumen ini dipakai untuk UAT proses rekrutmen CESA dari `Request Man Power` sampai `Recruitment Progress Report`.

## Tujuan

- Memastikan alur bisnis rekrutmen tetap linear dan tidak keluar dari pipeline.
- Memastikan data `job_applications` dan `job_application_histories` konsisten.
- Memastikan laporan timeline, per-position, dan overview membaca angka yang sama.
- Memastikan akses activity log dan report hanya tersedia untuk role yang berwenang.

## Prasyarat UAT

- Environment memakai migrasi rekrutmen terbaru sampai:
  `2026_04_08_230000_rekrutmen_add_filter_indexes`
- Seed atau master data tersedia:
  - user HR / recruiter
  - approver request manpower
  - minimal 1 pipeline dengan beberapa stage
- Role dan permission tersedia:
  - view/create job application history
  - akses resource job posting, job application, request manpower
- Frontend assets sudah sesuai environment:
  - jika UI tidak berubah, jalankan `npm run build` atau `npm run dev`

## Data UAT Minimum

- 2 user:
  - requester
  - HR/recruiter
- 1 request manpower baru
- 1 pipeline rekrutmen aktif
- 1 job posting aktif
- 3 kandidat:
  - kandidat lolos
  - kandidat gagal
  - kandidat pending

## Checklist UAT

### 1. Request Man Power

- Buat request manpower baru dari form/public flow.
- Pastikan field inti tersimpan:
  - company
  - posisi dibutuhkan
  - jumlah kebutuhan
  - estimasi join
  - requirement
  - job description
- Ekspektasi:
  - status awal `pending`
  - `status_response_id` terbentuk
  - request muncul di resource admin

### 2. Approval Request

- Approve request manpower dari panel admin.
- Ekspektasi:
  - status request berubah sesuai aksi
  - approver tercatat
  - data request tetap utuh

### 3. Job Posting

- Buat job posting dari request approved.
- Hubungkan ke pipeline yang benar.
- Ekspektasi:
  - posting punya relasi ke `request_man_power_id`
  - posting punya `rekrutmen_pipeline_id`
  - posting muncul di portal publik jika `is_published = true`

### 4. Portal Lowongan

- Buka endpoint daftar lowongan dan detail lowongan.
- Ekspektasi:
  - lowongan tampil
  - detail lowongan tampil sesuai locale
  - route apply tetap dapat diakses

### 5. Lamaran Kerja

- Submit 3 lamaran untuk 1 posting.
- Ekspektasi:
  - tiap kandidat masuk ke `job_applications`
  - `current_stage_id` otomatis ke stage awal pipeline
  - status awal kandidat `in_progress`
  - riwayat awal terbentuk jika alur existing memang membuat initial history

### 6. Pipeline Board

- Buka pipeline board untuk posting tersebut.
- Pastikan 3 kandidat tampil di stage aktif.
- Ekspektasi:
  - board membaca kandidat sesuai `current_stage_id`
  - kandidat terminal nanti tetap tampil dengan context status yang benar

### 7. Activity Log Batch

- Catat 1 aktivitas batch dengan input minimal:
  - job posting
  - stage
  - activity type
  - activity date
  - 3 kandidat dengan hasil berbeda
- Gunakan hasil:
  - kandidat A `passed`
  - kandidat B `failed`
  - kandidat C `pending`
- Ekspektasi:
  - `activity_title` terbentuk otomatis
  - `performed_by` mengikuti user login
  - semua kandidat berada dalam posting dan stage yang sama
  - gagal submit jika kandidat aktif tidak sesuai stage
  - gagal submit jika `failed` tanpa notes

### 8. Efek Pipeline Setelah Activity

- Refresh job applications dan pipeline board.
- Ekspektasi:
  - kandidat A pindah ke next stage
  - kandidat B berubah ke `rejected`
  - kandidat C tetap di stage lama
  - history batch tersimpan dengan `activity_group_id` yang sama

### 9. Terminal Decision

- Uji status terminal tambahan dari flow manual jika tersedia:
  - hired
  - rejected
  - withdrawn
- Ekspektasi:
  - kandidat terminal tidak bisa diproses lagi sebagai kandidat aktif pada batch berikutnya
  - board menampilkan context status terminal dengan benar

### 10. Recruitment Progress Report

- Buka report panel.
- Cek tab:
  - Timeline
  - Per Position
  - Overview
- Gunakan filter:
  - date range
  - job posting
  - activity type
  - company
- Ekspektasi:
  - summary konsisten dengan data kandidat aktual
  - timeline menampilkan 1 batch activity dengan count benar
  - per-position menampilkan funnel dan activity history yang sama
  - overview menampilkan latest activity dan fulfillment yang sama

### 11. Access Control

- Login sebagai user tanpa permission history/report.
- Ekspektasi:
  - tidak bisa akses activity log
  - tidak bisa create activity
  - tidak bisa akses recruitment progress report page

### 12. Localization

- Ubah locale ke `id` dan `en`.
- Ekspektasi:
  - label activity type
  - result label
  - board status context
  - report labels
  tampil sesuai locale

## Evidence Yang Harus Dikumpulkan

- Screenshot request manpower sebelum dan sesudah approval
- Screenshot job posting dan pipeline assignment
- Screenshot pipeline board sebelum activity
- Screenshot activity log create form
- Screenshot report timeline / overview sesudah batch
- Export atau capture data:
  - 1 row `job_applications` per kandidat
  - 1 group `job_application_histories` untuk batch

## Exit Criteria UAT

- Semua skenario 1 sampai 12 lulus
- Tidak ada mismatch angka antara board, activity log, dan report
- Tidak ada akses tanpa permission ke activity/report
- Tidak ada error validation yang berubah menjadi exception runtime
- Semua hasil batch mengubah state kandidat secara deterministik

## Catatan Residual

- UAT ini memvalidasi correctness fungsional.
- Untuk volume besar, tetap perlu observasi query di staging/production dengan dataset riil.
