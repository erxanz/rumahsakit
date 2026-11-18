# Backend API — Sistem Informasi Pasien / Klinik

REST API native PHP 8.3 (tanpa framework) untuk database `db_pasien` — MySQL 8.

## Struktur Folder

```
rumahsakit/
├── public/                     # Web root (satunya yang diekspos ke web)
│   ├── index.php               # Front controller / entry point
│   └── .htaccess               # Rewrite semua request ke index.php
├── app/
│   ├── Config/
│   │   └── Config.php          # Loader .env
│   ├── Core/                   # Micro-framework
│   │   ├── Autoloader.php      # Autoloader PSR-4 sederhana (App\ -> app/)
│   │   ├── Router.php          # Router REST + group + middleware pipeline
│   │   ├── Request.php         # HTTP request (JSON body, query, params, user)
│   │   ├── Response.php        # HTTP response JSON
│   │   ├── Database.php        # Koneksi PDO + transaction helper
│   │   ├── Model.php           # Base model (CRUD, paginate, search, filter)
│   │   ├── Controller.php      # Base controller
│   │   ├── CrudController.php  # CRUD generik (index/show/store/update/destroy)
│   │   ├── Validator.php       # Validasi deklaratif (required, unique, exists, ...)
│   │   ├── ValidationException.php
│   │   ├── HttpException.php   # Exception ber-status (401/403/404/409/422)
│   │   ├── Jwt.php             # JWT HS256 native (tanpa library eksternal)
│   │   ├── AuditLog.php        # Penulis audit log (tidak menggagalkan operasi utama)
│   │   └── Middleware/
│   │       ├── AuthMiddleware.php       # Verifikasi Bearer token + muat user/permission
│   │       └── PermissionMiddleware.php # Cek permission per route
│   ├── Controllers/            # Endpoint per resource
│   ├── Models/                 # 1 model per tabel
│   └── Routes/
│       └── api.php             # Deklarasi seluruh route + middleware
├── bin/
│   └── create_admin.php        # CLI: buat akun super_admin
├── .env                        # Konfigurasi (DB, JWT, CORS)
├── .htaccess                   # Arahkan docroot Laragon ke public/
├── db_pasien.sql               # Skema database + seed RBAC
└── README.md
```

## Setup

1. Import database:

   ```bash
   mysql -u root < db_pasien.sql
   ```

2. Sesuaikan `.env` (host, user, password, `JWT_SECRET`).

3. Buat akun admin:

   ```bash
   php bin/create_admin.php admin admin123 "Super Admin"
   ```

4. Akses API (Laragon docroot = folder project, atau arahkan virtual host ke `public/`):

   - Apache: `http://localhost/rumahsakit/api/health`
   - Dev server: `php -S 127.0.0.1:8099 -t public`

## Autentikasi

| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/auth/login` | POST | Login petugas (username + password) → JWT |
| `/api/auth/login-pasien` | POST | Login pasien → JWT |
| `/api/auth/register-pasien` | POST | Registrasi pasien + akun login |
| `/api/auth/me` | GET | Profil user dari token |
| `/api/auth/refresh` | POST | Terbitkan token baru |

Kirim token pada header: `Authorization: Bearer <token>`.

## Endpoint Resource (CRUD)

Semua mendukung `GET` (list + `?page=&per_page=&q=&sort=&order=&filter[kolom]=`),
`GET /{id}`, `POST`, `PUT|PATCH /{id}`, `DELETE /{id}`.

| Prefix | View permission | Manage permission |
|---|---|---|
| `/api/poli` | `poli.view` | `poli.manage` |
| `/api/asuransi` | `pasien.view` | `pasien.create/update/delete` |
| `/api/pasien` | `pasien.view` | `pasien.create/update/delete` |
| `/api/dokter` | `dokter.view` | `dokter.manage` |
| `/api/jadwal-dokter` | `dokter.view` | `dokter.manage` |
| `/api/kunjungan` | `kunjungan.view` | `kunjungan.manage` |
| `/api/rekam-medis` | `rekam_medis.view` | `rekam_medis.manage` |
| `/api/tindakan` | `kunjungan.view` | `pembayaran.manage` |
| `/api/pembayaran` | `pembayaran.view` | `pembayaran.manage` |
| `/api/pengguna` | `pengguna.manage` | `pengguna.manage` |
| `/api/roles` | `rbac.manage` | `rbac.manage` |
| `/api/permissions` | `rbac.manage` | `rbac.manage` |
| `/api/audit-log` | `audit.view` | (read-only) |

## Endpoint Khusus

| Endpoint | Method | Keterangan |
|---|---|---|
| `/api/dashboard` | GET | Statistik (kunjungan hari ini, pendapatan, antrean, dsb.) |
| `/api/kunjungan/antrean/harian` | GET | Antrean harian (`?tanggal=&id_poli=`) |
| `/api/kunjungan/{id}/verifikasi` | POST | Verifikasi pendaftaran online (`terverifikasi`/`ditolak`) |
| `/api/kunjungan/{id}/batal` | POST | Batalkan kunjungan |
| `/api/kunjungan/{id}/tindakan` | GET/POST | Rincian / tambah tindakan |
| `/api/kunjungan/{id}/tindakan/{tindakanId}` | DELETE | Hapus tindakan |
| `/api/pembayaran/{id}/bayar` | POST | Catat pembayaran (bertahap → `sebagian`/`lunas`) |
| `/api/health` | GET | Health check (tanpa auth) |

## Aturan Bisnis Utama

- Pendaftaran **online** wajib menyertakan `kode_booking`, `nomor_antrean`, `tanggal_antrean`
  (dicek constraint CHECK di database); status verifikasi awal = `menunggu`.
- Akun **pasien** otomatis di-scoping: hanya melihat data miliknya sendiri
  (kunjungan, pasien, rekam medis) dan `nik` di-inject dari token.
- Membuat rekam medis otomatis mengubah status kunjungan menjadi `selesai`.
- Menambahkan tindakan menyelesaikan `total_tagihan` pembayaran terkait.
- Semua operasi tulis tercatat di `audit_log`.

## Contoh Cepat

```bash
TOKEN=$(curl -s -X POST http://localhost/rumahsakit/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}' | jq -r .data.token)

curl -s http://localhost/rumahsakit/api/poli \
  -H "Authorization: Bearer $TOKEN"
```

Format respons selalu:

```json
{ "success": true, "message": "...", "data": { } }
```

Error: `401` token salah/kedaluwarsa, `403` permission, `404` tidak ada,
`409` duplikat/terkait data lain, `422` validasi (`errors` berisi detail per field).
