# 🚀 Cesa Rekrutmen: Public API Documentation

Dokumentasi ini ditujukan bagi tim _Frontend_ yang mengembangkan halaman publik (seperti **Oceanspace Landing Page**) guna berintegrasi dengan sistem Rekrutmen (Pipeline & Applicant Tracking System) dari Cesa.

Seluruh _endpoint_ berjalan menggunakan JSON (sebagai _request_ maupun _response_ bergantung parameternya), tidak memerlukan layar autentikasi (*publicly accessible*), dan mengembalikan format respons bertipe Seragam (Uniform/Standar).

---

## 🏗 Struktur Respons Global

Setiap panggilan yang bermuara pada _endpoint_ ini mematuhi standar JSON di bawah ini:

```json
{
  "success": true,
  "message": "Pesan balasan...",
  "data": { ... } // (opsional, tergantung konteks)
}
```

---

## 📡 Endpoints

### 1. Mengambil Daftar Lowongan Pekerjaan
Berfungsi memuat seluruh posisi yang aktif (status _published_) dan waktu penutupannya belum kedaluwarsa. Sangat tepat digunakan pada halaman depan _Careers_.

* **Endpoint:** `GET /api/jobs`
* **Response: `200 OK`**

```json
{
  "success": true,
  "message": "Daftar pekerjaan berhasil dimuat.",
  "data": [
    {
      "title": "Backend Developer",
      "slug": "backend-developer",
      "location": "Jakarta, Indonesia",
      "thumbnail_url": "https://cesa.test/storage/jobs/thumb.png",
      "closing_date": "2026-12-31T23:59:59.000000Z"
    }
  ]
}
```

---

### 2. Mengambil Detail & Skema Dinamis _(Job Description)_
Berfungsi untuk memuat keterangan lengkap pekerjaan saat pelamar menekan tombol "Detail". Mengembalikan spesifikasi teknis plus daftar _field/input_ dinamis (`application_form`) yang **wajib _dirender_** oleh _frontend API consumer_ demi menjaga fleksibilitas halaman aplikasinya.

* **Endpoint:** `GET /api/jobs/{slug}`
* **Response: `200 OK`**

```json
{
  "success": true,
  "message": "Detail pekerjaan berhasil dimuat.",
  "data": {
    "title": "Backend Developer",
    "slug": "backend-developer",
    "description": "Deskripsi pekerjaan menggunakan teks polos (plain text).\nBisa memuat baris baru sesuai input Textarea admin.",
    "requirements": "1. Menguasai Laravel Ecosystem\n2. Terbiasa dengan TDD\n3. Portofolio github",
    "location": "Jakarta, Indonesia",
    "thumbnail_url": "https://...",
    "closing_date": "2026-12-31T23:59:59.000000Z",
    "application_form": [
      {
        "name": "full_name",
        "label": "Nama Lengkap",
        "type": "text",
        "required": true
      },
      {
        "name": "gender",
        "label": "Jenis Kelamin",
        "type": "select",
        "required": true,
        "options": [
          { "value": "male", "label": "Laki-laki" },
          { "value": "female", "label": "Perempuan" }
        ]
      }
      // ... input lainnya
    ]
  }
}
```

---

### 3. Mengajukan Lamaran (*Apply Application*)
Menangkap _submission_ form lamaran kerja yang dirangkai dari `application_form` di antarmuka depan. 

> [!WARNING]
> Sangat Penting! Form ini menerima *file uploads* (pasfoto & CV/Resume), **mohon pastikan HTTP Header request dari Anda dikirim sebagai `multipart/form-data`**!

* **Endpoint:** `POST /api/jobs/{slug}/apply`
* **Content-Type:** `multipart/form-data`
* **Payload Parameters (Semuanya Wajib _/ Required_):**

| Parameter | Tipe | Batasan & Keterangan |
| :--- | :--- | :--- |
| `full_name` | String | Nama lengkap kandidat sesuai KTP (maks. 255 karakter). |
| `email` | String | Alamat email aktif kandidat. |
| `gender` | Enum | Harus bernilai **`male`** atau **`female`**. |
| `birth_date` | Date | Tanggal lahir kandidat (format `YYYY-MM-DD`). |
| `marital_status` | Enum | Harus bernilai **`single`** / **`married`** / **`divorced`**. |
| `address_ktp` | String | Alamat domisili sah di KTP. |
| `address_domicile` | String | Alamat rumah pelamar tinggal sekarang. |
| `whatsapp_number` | String | Nomor telepon _WhatsApp_ aktif tanpa spasi (maks. 30). |
| `active_phone` | String | Nomor telepon lain jika ada (samakan seperti `whatsapp_number` bila tak ada). |
| `emergency_contact_name` | String | Nama referensi orang terdekat kandidat (maks. 255). |
| `emergency_contact_relation` | String | Relasi kontak darurat (misal: "Ibu Kandung", "Sahabat"). |
| `emergency_contact_phone` | String | Nomor kontak darurat yang dapat dihubungi. |
| `photo` | File (Gambar) | Pasfoto kandidat. Format `jpg, jpeg, png, webp` (Max 5MB). |
| `resume` | File (Dokumen) | File CV / Riwayat Hidup. Format `pdf, doc, docx` (Max 5MB). |

* **Response (Berhasil): `201 Created`**
Secara sukses melempar balasan bahwa pelamar baru (*Applicant*) sah diinjeksikan pada sistem Pipeline di *stage* urutan pertama (paling kiri) pada Kanban Board modul administrasi *HR*.

```json
{
  "success": true,
  "message": "Lamaran berhasil dikirimkan.",
  "data": {
    "job_slug": "backend-developer",
    "applicant_name": "Budi Santoso",
    "applicant_email": "budi.santoso@gmail.com",
    "status": "in_progress"
  }
}
```

* **Response (Validasi Gagal): `422 Unprocessable Entity`**
```json
{
  "message": "The full name field is required. (and 1 more error)",
  "errors": {
    "full_name": ["The full name field is required."],
    "resume": ["The resume must be a file of type: pdf, doc, docx."]
  }
}
```
