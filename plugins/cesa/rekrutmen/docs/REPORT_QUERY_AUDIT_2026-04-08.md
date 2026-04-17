# Recruitment Report Query Audit

Tanggal audit: `2026-04-08`  
Scope: query utama untuk `RecruitmentProgressReportService`, API report, dan Livewire report.

## Ringkasan

Audit dilakukan dalam 2 konteks:

- Database lokal aktif:
  - `sqlite`
  - file: `database/database.sqlite`
  - volume aktual sangat kecil
- Database sintetis audit:
  - file: `/tmp/rekrutmen_report_audit.sqlite`
  - dipakai untuk baseline `EXPLAIN QUERY PLAN` dan timing

## Kondisi Database Lokal Saat Audit

- `rekrutmen_request_man_powers`: 6
- `rekrutmen_job_postings`: 5
- `rekrutmen_job_applications`: 3
- `rekrutmen_job_application_histories`: 8

Kesimpulan: database lokal terlalu kecil untuk dipakai sebagai acuan performa.

## Dataset Sintetis Audit

Dataset sintetis dibuat khusus untuk audit dengan volume:

- `rekrutmen_request_man_powers`: 50
- `rekrutmen_job_postings`: 500
- `rekrutmen_job_applications`: 20.000
- `rekrutmen_job_application_histories`: 100.000

Distribusi sintetis:

- 25 pipeline
- 8 stage per pipeline
- status kandidat bercampur:
  - `in_progress`
  - `hired`
  - `rejected`
- activity type bercampur:
  - `screening`
  - `interview_hrd`
  - `interview_user`
  - `test_teknis`

## Query Yang Diaudit

### 1. Posting Summary Stats

Query shape:

- agregasi `COUNT` kandidat per posting
- filter `job_posting_id IN (...)`
- breakdown status `in_progress`, `hired`, `rejected`

`EXPLAIN QUERY PLAN`:

```text
SEARCH rekrutmen_job_applications USING COVERING INDEX rekrutmen_job_apps_posting_status_index (job_posting_id=?)
```

Kesimpulan:

- Sudah memakai index yang tepat.
- Aman untuk agregasi summary per posting.

### 2. Current Stage Counts

Query shape:

- hitung kandidat aktif per stage
- filter `job_posting_id IN (...)` dan `status = 'in_progress'`
- group by `job_posting_id, current_stage_id`

`EXPLAIN QUERY PLAN`:

```text
SEARCH rekrutmen_job_applications USING COVERING INDEX rekrutmen_job_apps_posting_status_stage_index (job_posting_id=? AND status=?)
```

Kesimpulan:

- Composite index baru dipakai dengan benar.
- Query funnel stage sudah ter-cover.

### 3. Filtered History Fetch

Query shape:

- ambil history batch untuk report
- filter:
  - `activity_group_id IS NOT NULL`
  - `activity_type IS NOT NULL`
  - `job_application_id IN (subquery posting)`
  - `activity_date range`
  - `activity_type`
- order by `activity_date DESC, created_at DESC`

`EXPLAIN QUERY PLAN`:

```text
SEARCH rekrutmen_job_application_histories USING INDEX rekrutmen_histories_app_date_type_index (job_application_id=? AND activity_date>? AND activity_date<?)
LIST SUBQUERY
SEARCH rekrutmen_job_applications USING COVERING INDEX rekrutmen_job_apps_posting_status_stage_index (job_posting_id=?)
USE TEMP B-TREE FOR ORDER BY
```

Timing sintetis:

- sekitar `0.06s` pada 100.000 history rows

Kesimpulan:

- Filter utama sudah memakai index.
- Sorting masih memakai temporary B-tree.
- Untuk volume audit saat ini masih aman, tetapi ini adalah titik yang perlu diawasi pada dataset produksi jauh lebih besar.

### 4. Passed Counts by Stage

Query shape:

- hitung kandidat `passed` per stage
- filter `to_stage_id`, `result = 'passed'`, dan subquery job applications by posting

`EXPLAIN QUERY PLAN`:

```text
SEARCH rekrutmen_job_application_histories USING INDEX rekrutmen_histories_stage_result_app_index (to_stage_id=? AND result=? AND job_application_id=?)
LIST SUBQUERY
SEARCH rekrutmen_job_applications USING COVERING INDEX rekrutmen_job_apps_posting_status_stage_index (job_posting_id=?)
```

Kesimpulan:

- Index stage-result-app dipakai.
- Query pipeline progress sudah cukup sehat.

### 5. Posting IDs By Company Filter

Query shape:

- resolve posting dari filter `company_id`
- join `job_postings -> request_man_powers`

#### Sebelum index tambahan

`EXPLAIN QUERY PLAN`:

```text
SCAN rekrutmen_job_postings
BLOOM FILTER ON rekrutmen_request_man_powers (id=?)
SEARCH rekrutmen_request_man_powers USING INTEGER PRIMARY KEY (rowid=?)
```

Kesimpulan:

- Ini masih full scan pada `rekrutmen_job_postings`.
- Titik ini memang perlu hardening.

#### Sesudah index tambahan

Index yang disimulasikan:

- `rekrutmen_request_man_powers(company_id)`
- `rekrutmen_job_postings(request_man_power_id)`

`EXPLAIN QUERY PLAN`:

```text
SEARCH rekrutmen_request_man_powers USING COVERING INDEX rekrutmen_request_man_powers_company_id_index (company_id=?)
SEARCH rekrutmen_job_postings USING COVERING INDEX rekrutmen_job_postings_request_man_power_index (request_man_power_id=?)
```

Timing sintetis:

- sekitar `0.00s`

Kesimpulan:

- Improvement valid.
- Karena itu index filter tambahan diimplementasikan ke migrasi plugin.

## Hardening Yang Diimplementasikan

Migrasi baru yang ditambahkan:

- `2026_04_08_220000_rekrutmen_add_reporting_indexes`
- `2026_04_08_230000_rekrutmen_add_filter_indexes`

Index penting yang sekarang ada:

- `rekrutmen_job_applications(job_posting_id, status, current_stage_id)`
- `rekrutmen_job_application_histories(job_application_id, activity_date, activity_type)`
- `rekrutmen_job_application_histories(to_stage_id, result, job_application_id)`
- `rekrutmen_request_man_powers(company_id)`
- `rekrutmen_job_postings(request_man_power_id)`
- `rekrutmen_job_postings(rekrutmen_pipeline_id)`

## Kesimpulan Audit

- Query summary per posting: sehat
- Query funnel per stage: sehat
- Query passed count per stage: sehat
- Query company filter: sebelumnya lemah, sekarang sudah di-hardening dengan index
- Query history fetch utama: cukup sehat, tetapi sorting masih membangun temporary B-tree

## Residual Risk

- Audit ini memakai SQLite sintetis sebagai baseline, bukan MySQL produksi.
- `USE TEMP B-TREE FOR ORDER BY` pada history fetch perlu dipantau di staging/production jika:
  - date range sangat lebar
  - posting yang dipilih sangat banyak
  - history > jutaan row

## Rekomendasi Lanjutan

- Jalankan `EXPLAIN` ulang di staging yang memakai engine produksi sesungguhnya.
- Amati query paling berat dari endpoint:
  - `/api/recruitment/progress-report`
  - `/api/recruitment/progress-report/timeline`
  - `/api/recruitment/progress-report/overview`
- Jika volume history tumbuh besar, pertimbangkan:
  - observability query runtime
  - pagination atau pembatasan periode default yang lebih ketat
  - optimasi ordering atau materialized summary bila diperlukan
