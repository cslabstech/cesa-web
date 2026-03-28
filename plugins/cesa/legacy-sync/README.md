# Legacy Sync

Plugin ini dipakai untuk sinkronisasi data legacy SQL ke schema `web-cesa` yang sekarang.

## Ruang Lingkup

Command `legacy:sync` saat ini menangani modul berikut:

- `document`
- `form-transfer`
- `exit-clearance`
- `lead`
- `presensi`
- `helpdesk`
- `shelf`

Setiap modul dibaca dari satu source database legacy per eksekusi.

## Mapping Database Legacy

Setup yang dipakai saat migrasi aktual:

| Plugin | Source Database Legacy |
| --- | --- |
| `document` | `app_cesa` |
| `form-transfer` | `app_cesa` |
| `exit-clearance` | `app_cesa` |
| `lead` | `app_lead` |
| `presensi` | `app_presensi` |
| `helpdesk` | `app_helpdesk` |
| `shelf` | `app_shelf` |

## File Penting

- Command sync: `plugins/cesa/legacy-sync/src/Console/Commands/SyncLegacySqlData.php`
- Command sync-all: `plugins/cesa/legacy-sync/src/Console/Commands/SyncAllLegacyData.php`
- Config koneksi legacy: `plugins/cesa/legacy-sync/config/legacy-sync.php`
- Mapping sinkronisasi: `legacy_sync_mappings`

## Cara Kerja

`legacy:sync` membaca data langsung dari koneksi database legacy.

Sync berjalan dalam mode upsert:

- jika mapping sudah ada, record target akan di-update
- jika mapping belum ada, record target akan dibuat lalu disimpan ke `legacy_sync_mappings`

## Prasyarat

Sebelum menjalankan sync, pastikan migration plugin target sudah ada di database utama.

Minimal migration yang wajib ada:

```bash
php artisan migrate --path=plugins/cesa/legacy-sync/database/migrations --realpath --force --no-interaction
php artisan migrate --path=plugins/cesa/lead/database/migrations --realpath --force --no-interaction
```

Migration `legacy-sync` diperlukan untuk tabel `legacy_sync_mappings`.

Migration `lead` diperlukan untuk kolom `leads.public_response_id`.

## Catatan Registrasi Command

Di workspace ini, command `legacy:sync` dan `legacy:sync-all` sempat tidak muncul langsung di `php artisan`.

Jika itu terjadi, refresh autoload lebih dulu:

```bash
composer dump-autoload
php artisan package:discover --ansi
```

Jika command masih belum muncul, jalankan sync dengan bootstrap provider secara eksplisit:

```bash
php -r 'require "vendor/autoload.php"; $app = require "bootstrap/app.php"; $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class); $kernel->bootstrap(); $app->register(Cesa\LegacySync\LegacySyncServiceProvider::class); $code = Illuminate\Support\Facades\Artisan::call("legacy:sync", ["--module" => ["document"], "--database" => "app_cesa", "--no-interaction" => true]); echo Illuminate\Support\Facades\Artisan::output(); exit($code);'
```

Ganti nilai `--module` dan `--database` sesuai plugin yang ingin dijalankan.

## Restore Dari Fresh Install

Jika baru menjalankan `php artisan erp:install` dan ingin mengembalikan seluruh data legacy, cukup jalankan satu command:

```bash
php artisan legacy:sync-all \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root \
  --document-database=app_cesa \
  --form-transfer-database=app_cesa \
  --exit-clearance-database=app_cesa \
  --lead-database=app_lead \
  --presensi-database=app_presensi \
  --helpdesk-database=app_helpdesk \
  --shelf-database=app_shelf \
  --truncate \
  --trust-legacy-user-ids \
  --trust-legacy-company-ids
```

`sync-all` secara otomatis:

1. Install semua plugin yang belum ter-install (`kepegawaian`, `document`, `exit-clearance`, `form-transfer`, `lead`, `presensi`, `payroll`, `shelf`, `helpdesk`)
2. Jalankan `legacy:sync` per plugin dengan database masing-masing

Jika plugin sudah pernah di-install dan ingin di-install ulang, tambahkan `--force-install`.

Jika ingin skip install dan langsung sync, tambahkan `--skip-install`.

## Menjalankan Sync Per Plugin

### Document

```bash
php artisan legacy:sync \
  --module=document \
  --database=app_cesa \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Form Transfer

```bash
php artisan legacy:sync \
  --module=form-transfer \
  --database=app_cesa \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Exit Clearance

```bash
php artisan legacy:sync \
  --module=exit-clearance \
  --database=app_cesa \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Lead

```bash
php artisan legacy:sync \
  --module=lead \
  --database=app_lead \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Presensi

```bash
php artisan legacy:sync \
  --module=presensi \
  --database=app_presensi \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Helpdesk

```bash
php artisan legacy:sync \
  --module=helpdesk \
  --database=app_helpdesk \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

### Shelf

```bash
php artisan legacy:sync \
  --module=shelf \
  --database=app_shelf \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

## Menjalankan Sync Semua Plugin

`legacy:sync-all` sekarang dieksekusi per plugin, bukan per group database.

Default database per plugin:

- `--document-database=app_cesa`
- `--form-transfer-database=app_cesa`
- `--exit-clearance-database=app_cesa`
- `--lead-database=app_lead`
- `--presensi-database=app_presensi`
- `--helpdesk-database=app_helpdesk`
- `--shelf-database=app_shelf`

Contoh:

```bash
php artisan legacy:sync-all \
  --document-database=app_cesa \
  --form-transfer-database=app_cesa \
  --exit-clearance-database=app_cesa \
  --lead-database=app_lead \
  --presensi-database=app_presensi \
  --helpdesk-database=app_helpdesk \
  --shelf-database=app_shelf \
  --host=127.0.0.1 \
  --port=3306 \
  --username=root
```

## Opsi Penting `legacy:sync`

- `--module=` untuk memilih satu plugin tertentu
- `--database=` untuk memilih source database legacy
- `--host=`, `--port=`, `--username=`, `--password=` untuk override koneksi
- `--truncate` untuk mengosongkan tabel target sebelum sync
- `--chunk=` untuk ukuran chunk proses import
- `--skip-missing-users` untuk mencegah auto-create user legacy yang belum ada
- `--trust-legacy-user-ids` untuk memakai ID user legacy jika ID target memang sebaris
- `--trust-legacy-company-ids` untuk memakai ID company legacy jika ID target memang sebaris

## Auto Create User dan Company

Secara default, jika user atau company legacy tidak bisa dipetakan ke target, command akan membuat record fallback agar relasi tetap tersimpan.

Contoh output:

```text
Created missing user [legacy-user-2182@legacy-sync.local] from legacy user ID [2182].
Created missing company [PT. RISM] from legacy business entity ID [5].
```

Itu bukan error. Itu berarti source legacy memiliki relasi yang belum ada padanannya di target.

## Hasil Sync Aktual

Berikut hasil eksekusi yang sudah berhasil dijalankan:

### `document` dari `app_cesa`

- `Documents`: 2

### `form-transfer` dari `app_cesa`

- `Form transfer banks`: 12
- `Form transfers`: 4
- `Form transfer divisions`: 9
- `Form transfer reference notes`: 4
- `Form transfer approval workflows`: 9
- `Form transfer requests`: 149

### `exit-clearance` dari `app_cesa`

- `Exit clearance departments`: 49
- `Exit clearance approvers`: 28
- `Exit clearance department approvers`: 309
- `Exit clearance requests`: 44
- `Exit clearance request approvers`: 345

### `lead` dari `app_lead`

- `Leads`: 72,523

### `presensi` dari `app_presensi`

- `Presensi offices`: 4
- `Presensi shifts`: 4
- `Presensi user images`: 40
- `Presensi schedules`: 38
- `Presensi attendances`: 842
- `Presensi leaves`: 0
- `Presensi overtimes`: 10

### `helpdesk` dari `app_helpdesk`

- `Helpdesk priorities`: 5
- `Helpdesk ticket statuses`: 4
- `Helpdesk units`: 4
- `Helpdesk unit users`: 10
- `Helpdesk problem categories`: 25
- `Helpdesk tickets`: 318
- `Helpdesk comments`: 161
- `Helpdesk ticket histories`: 749

### `shelf` dari `app_shelf`

- `Shelf categories`: 135
- `Shelf brands`: 212
- `Shelf asset locations`: 76
- `Shelf vendors`: 78
- `Shelf custom asset attributes`: 6
- `Shelf assets`: 1,488
- `Shelf asset attributes`: 1,512
- `Shelf tasks`: 219
- `Shelf asset transfers`: 1,173
- `Shelf asset transfer details`: 1,385
- `Shelf company document settings`: 27
- `Shelf vehicle checksheets`: 1,551

Bagian shelf berikut di-skip karena tabel legacy tidak tersedia:

- `approval_levels`
- `asset_requests`
- `request_approvals`
- job positions / employees legacy

## Troubleshooting

### Tabel `legacy_sync_mappings` Belum Ada

Contoh:

```text
Table 'cesa.legacy_sync_mappings' doesn't exist
```

Solusi:

```bash
php artisan migrate --path=plugins/cesa/legacy-sync/database/migrations --realpath --force --no-interaction
```

### Kolom `leads.public_response_id` Belum Ada

Contoh:

```text
Unknown column 'public_response_id' in 'field list'
```

Solusi:

```bash
php artisan migrate --path=plugins/cesa/lead/database/migrations --realpath --force --no-interaction
```

### Modul Di-skip Karena Tabel Legacy Tidak Ada

Contoh:

```text
Skipping module [shelf]. Missing legacy table(s): approval_levels, ...
```

Artinya database legacy tersebut memang tidak memiliki tabel yang dibutuhkan modul atau fitur itu.

### Progress Bar Tercampur Dengan Pesan User

Output seperti ini:

```text
80/133 [...] Created missing user [...]
```

Itu hanya efek visual karena progress bar dan output info ditulis pada saat yang sama.

## Catatan

- Command `legacy:sync` menyimpan mapping ke tabel `legacy_sync_mappings`
- Sync berjalan dalam mode upsert secara default
- Sync per plugin lebih aman untuk debugging daripada sync gabungan
