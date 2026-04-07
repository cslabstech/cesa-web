# Rekrutmen Public API

Dokumen ini menjelaskan endpoint API publik untuk modul rekrutmen yang tersedia saat ini.

## Base Path

```text
/api
```

## Endpoint

### 1. Get Job List

```http
GET /api/jobs
```

Mengambil daftar lowongan yang:
- `is_published = true`
- `closing_date` masih kosong atau belum lewat

#### Contoh response sukses

```json
{
  "success": true,
  "message": "Daftar lowongan berhasil diambil.",
  "data": [
    {
      "title": "Backend Developer",
      "slug": "backend-developer",
      "location": "Jakarta",
      "thumbnail_url": "/storage/rekrutmen/job-postings/backend-thumb.jpg",
      "closing_date": "2026-04-30"
    }
  ]
}
```

### 2. Get Job Detail

```http
GET /api/jobs/{slug}
```

Mengambil detail lowongan berdasarkan `slug`.

#### Path parameter

| Nama | Tipe | Wajib | Keterangan |
|------|------|------|------------|
| `slug` | string | Ya | Slug lowongan |

#### Contoh response sukses

```json
{
  "success": true,
  "message": "Detail lowongan berhasil diambil.",
  "data": {
    "title": "Backend Developer",
    "slug": "backend-developer",
    "description": "Build APIs",
    "requirements": "Laravel",
    "location": "Jakarta",
    "thumbnail_path": "rekrutmen/job-postings/backend-thumb.jpg",
    "closing_date": "2026-04-30",
    "thumbnail_url": "/storage/rekrutmen/job-postings/backend-thumb.jpg",
    "application_form": [
      {
        "name": "full_name",
        "label": "Nama Lengkap",
        "type": "text",
        "required": true
      },
      {
        "name": "email",
        "label": "Email",
        "type": "email",
        "required": true
      },
      {
        "name": "gender",
        "label": "Jenis Kelamin",
        "type": "select",
        "required": true,
        "options": [
          {
            "value": "male",
            "label": "Laki-laki"
          },
          {
            "value": "female",
            "label": "Perempuan"
          }
        ]
      },
      {
        "name": "birth_date",
        "label": "Tanggal Lahir",
        "type": "date",
        "required": true
      },
      {
        "name": "marital_status",
        "label": "Status",
        "type": "select",
        "required": true,
        "options": [
          {
            "value": "single",
            "label": "Single"
          },
          {
            "value": "married",
            "label": "Married"
          },
          {
            "value": "divorced",
            "label": "Divorced"
          }
        ]
      },
      {
        "name": "address_ktp",
        "label": "Alamat KTP",
        "type": "textarea",
        "required": true
      },
      {
        "name": "address_domicile",
        "label": "Alamat Domisili",
        "type": "textarea",
        "required": true
      },
      {
        "name": "whatsapp_number",
        "label": "No. WhatsApp",
        "type": "text",
        "required": true
      },
      {
        "name": "active_phone",
        "label": "No. Telp Aktif",
        "type": "text",
        "required": true
      },
      {
        "name": "emergency_contact_name",
        "label": "Nama Kontak Darurat",
        "type": "text",
        "required": true
      },
      {
        "name": "emergency_contact_relation",
        "label": "Hubungan Kontak Darurat",
        "type": "text",
        "required": true
      },
      {
        "name": "emergency_contact_phone",
        "label": "No. Kontak Darurat",
        "type": "text",
        "required": true
      },
      {
        "name": "photo",
        "label": "Photo",
        "type": "file",
        "required": true
      },
      {
        "name": "resume",
        "label": "CV/Resume",
        "type": "file",
        "required": true
      }
    ]
  }
}
```

#### Contoh response gagal

```json
{
  "success": false,
  "message": "Lowongan kerja tidak ditemukan."
}
```

Status HTTP:
- `200 OK` jika berhasil
- `404 Not Found` jika lowongan tidak ditemukan

### 3. Apply Job

```http
POST /api/jobs/{slug}/apply
```

Submit lamaran kerja untuk lowongan tertentu.

Request harus menggunakan `multipart/form-data`.

#### Path parameter

| Nama | Tipe | Wajib | Keterangan |
|------|------|------|------------|
| `slug` | string | Ya | Slug lowongan |

#### Request body

| Field | Tipe | Wajib | Validasi |
|------|------|------|----------|
| `full_name` | string | Ya | max 255 |
| `email` | string | Ya | email, max 255 |
| `gender` | string | Ya | `male`, `female` |
| `birth_date` | date | Ya | format tanggal valid |
| `marital_status` | string | Ya | `single`, `married`, `divorced` |
| `address_ktp` | string | Ya | bebas |
| `address_domicile` | string | Ya | bebas |
| `whatsapp_number` | string | Ya | max 30 |
| `active_phone` | string | Ya | max 30 |
| `emergency_contact_name` | string | Ya | max 255 |
| `emergency_contact_relation` | string | Ya | max 255 |
| `emergency_contact_phone` | string | Ya | max 30 |
| `photo` | file | Ya | image, `jpg`, `jpeg`, `png`, `webp`, max 5 MB |
| `resume` | file | Ya | `pdf`, `doc`, `docx`, max 5 MB |

#### Contoh cURL

```bash
curl -X POST http://localhost/api/jobs/backend-developer/apply \
  -F "full_name=Budi Santoso" \
  -F "email=budi@example.com" \
  -F "gender=male" \
  -F "birth_date=1995-01-10" \
  -F "marital_status=single" \
  -F "address_ktp=Jl. KTP No. 1, Jakarta" \
  -F "address_domicile=Jl. Domisili No. 2, Bekasi" \
  -F "whatsapp_number=081200000001" \
  -F "active_phone=081200000002" \
  -F "emergency_contact_name=Bunga" \
  -F "emergency_contact_relation=Adik Kandung" \
  -F "emergency_contact_phone=081200000003" \
  -F "photo=@/path/photo.jpg" \
  -F "resume=@/path/resume.pdf"
```

#### Contoh response sukses

```json
{
  "success": true,
  "message": "Lamaran berhasil dikirim.",
  "data": {
    "job_slug": "backend-developer",
    "applicant_name": "BUDI SANTOSO",
    "applicant_email": "budi@example.com",
    "status": "in_progress"
  }
}
```

Status HTTP:
- `201 Created` jika berhasil

#### Contoh response gagal karena lowongan tidak tersedia

```json
{
  "success": false,
  "message": "Lowongan kerja tidak ditemukan atau sudah tidak dibuka."
}
```

Status HTTP:
- `404 Not Found`

#### Contoh response gagal validasi

```json
{
  "message": "The photo field is required. (and 1 more error)",
  "errors": {
    "photo": [
      "Photo diri terbaru wajib diunggah."
    ],
    "resume": [
      "CV/Resume terbaru wajib diunggah."
    ]
  }
}
```

Status HTTP:
- `422 Unprocessable Entity`

## Nilai Enum

### `gender`

```text
male
female
```

### `marital_status`

```text
single
married
divorced
```

### `status` pada response apply

```text
in_progress
```

## Catatan Perilaku API

- Endpoint `GET /api/jobs` hanya menampilkan lowongan yang publish dan belum tutup.
- Endpoint `GET /api/jobs/{slug}` hanya mengecek lowongan publish.
- Endpoint `POST /api/jobs/{slug}/apply` mengecek lowongan publish dan belum tutup.
- Nama pelamar dan beberapa field kontak akan dinormalisasi saat disimpan, sehingga response bisa tampil dalam format huruf besar.
- Upload `photo` dan `resume` disimpan oleh sistem ke storage aplikasi.

