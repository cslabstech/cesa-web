# Exit Clearance

Plugin ini menangani proses exit clearance karyawan, mulai dari pengajuan form, approval approver, sampai monitoring status request.

## Alur Input

Plugin ini punya 2 alur input request:

### 1. Public Input

Request dibuat dari form publik:

- Route publik: `/exit-clearance`
- Komponen: `Cesa\ExitClearance\Livewire\PublicExitClearanceRequestForm`
- Service submit: `Cesa\ExitClearance\Services\ExitClearanceRequestService::createPublicRequest()`

Karakteristik request dari alur publik:

- `created_by` akan bernilai `null`
- request otomatis meng-attach approver berdasarkan department
- request ini memang dianggap sebagai data global

Konsekuensi akses:

- request dengan `created_by = null` hanya bisa dilihat oleh user dengan `resource_permission = global`
- user dengan scope `individual` atau `group` tidak akan melihat request publik ini di admin panel

Catatan:

- behavior ini disengaja
- jangan ubah asumsi ini tanpa meninjau policy, query scope, dan resource permission yang dipakai plugin

### 2. Admin Input

Request juga bisa dibuat dari admin panel:

- Resource admin: `Cesa\ExitClearance\Filament\Resources\RequestResource`
- Model: `Cesa\ExitClearance\Models\Request`

Karakteristik request dari alur admin:

- `created_by` akan diisi otomatis dari user yang sedang login
- visibilitas record mengikuti resource permission scope yang berlaku

## Aturan Akses Request

Ringkasnya:

- `created_by = null`: hanya user global yang bisa lihat
- `created_by = user_id`: mengikuti permission scope user pada plugin security

Aturan ini berlaku untuk:

- query resource admin
- policy per-record
- visibilitas data pada halaman admin

## Konfigurasi Admin

Konfigurasi plugin dikelola dari:

- `/admin/exit-clearance/configurations/departments`
- `/admin/exit-clearance/configurations/approvers`

Untuk data yang sudah soft delete:

- record muncul di tab `Diarsipkan`
- action `Restore` dan `Force Delete` hanya dipakai dari tab `Diarsipkan`

## Dokumen Tambahan

Dokumentasi detail implementasi form publik ada di:

- [README-FORM.md](/Users/apriansyahrs/Documents/Code/web-cesa/plugins/cesa/exit-clearance/README-FORM.md)
