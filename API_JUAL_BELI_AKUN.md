# Dokumentasi API Jual Beli Akun

Dokumentasi ini menjelaskan API katalog akun game dan CRUD admin yang dipakai bersama oleh:

- https://johengaming.id
- https://marketplace.johengaming.id
- http://localhost:8000

## Base URL

Produksi:

~~~text
https://johengaming.id/api
~~~

Lokal:

~~~text
http://localhost:8000/api
~~~

Semua respons menggunakan JSON. Request JSON memakai header:

~~~http
Accept: application/json
Content-Type: application/json
~~~

Request yang mengirim foto, video, atau screenshot harus memakai multipart/form-data.

## CORS

Origin yang diizinkan:

~~~text
https://johengaming.id
https://marketplace.johengaming.id
http://localhost:8000
~~~

Method yang diizinkan: GET, POST, PUT, DELETE, dan OPTIONS.

Header yang diizinkan: Accept, Authorization, Content-Type, dan X-Requested-With.

## Alur autentikasi admin

Admin domain dapat memakai API key unik tanpa memakai akun user yang sama. Key disimpan di server API pada `.env` dan dikirim sebagai Bearer token:

~~~env
JBA_API_KEY_JOHEN=kode-unik-johen
JBA_API_KEY_MARKETPLACE=kode-unik-marketplace
JBA_API_NAME_JOHEN="Johen API"
JBA_API_NAME_MARKETPLACE="Marketplace API"
~~~

API key Johen dan API key Marketplace memiliki akses CRUD penuh. API key mewakili aplikasi/domain, bukan user perorangan. Jika browser memanggil API secara langsung, key dapat terlihat oleh pengguna browser; simpan key di backend/proxy jika ingin merahasiakannya.

### Akses memakai API key

Tidak perlu memanggil endpoint login. Kirim key pada setiap request admin:

~~~http
GET /admin/jual-beli-akun
Authorization: Bearer kode-unik-marketplace
Accept: application/json
~~~

API key salah atau belum diatur di `.env` mendapat respons `401`. Endpoint `/admin/me` mengembalikan `auth_type: api_key`.

Untuk logout API key, aplikasi cukup menghapus key dari konfigurasi/session-nya. Endpoint logout tidak mencabut key yang tersimpan di `.env`; untuk mencabut akses, ubah nilai key lalu reload konfigurasi aplikasi.

1. Kirim email dan password ke endpoint login.
2. Simpan nilai token dari respons.
3. Kirim token pada setiap endpoint admin:

~~~http
Authorization: Bearer TOKEN_DARI_LOGIN
~~~

Token berlaku selama 8 jam dan otomatis tidak berlaku jika password akun admin berubah.

### Login admin

~~~http
POST /admin/login
~~~

Body JSON:

~~~json
{
  "email": "admin@johengaming.com",
  "password": "password-admin"
}
~~~

Respons 200:

~~~json
{
  "token": "TOKEN_RAHASIA",
  "token_type": "Bearer",
  "expires_in": 28800,
  "user": {
    "id": 1,
    "name": "Admin Record",
    "role": "record",
    "can_manage": true,
    "scope": null
  }
}
~~~

Untuk admin game, scope berisi pembatasan game dan toko. Contoh scope:

~~~json
{
  "game": "pubg",
  "store": "monkey"
}
~~~

Login gagal mengembalikan status 401:

~~~json
{
  "message": "Email atau password salah."
}
~~~

Role yang dapat login:

- superadmin
- record
- nomor
- admin_johen_pubg
- admin_johen_mlbb
- admin_johen_free_fire
- admin_johen_roblox
- admin_johen_fc_mobile
- admin_johen_efootball
- admin_johen_valorant
- admin_monkey_pubg

Role hr, sosmed, host_live, admin_cs, dan guest tidak memiliki akses API ini.

### Cek user login

~~~http
GET /admin/me
Authorization: Bearer TOKEN
~~~

Respons 200:

~~~json
{
  "data": {
    "id": 1,
    "name": "Admin Record",
    "role": "record",
    "can_manage": true,
    "scope": null
  }
}
~~~

### Logout

~~~http
POST /admin/logout
Authorization: Bearer TOKEN
~~~

Respons 200:

~~~json
{
  "success": true
}
~~~

Setelah logout, token tersebut tidak dapat digunakan lagi.

## Endpoint publik

Endpoint publik tidak membutuhkan token. Data berasal dari tabel jba_akun, sehingga kedua frontend menerima stok yang sama.

### Konfigurasi tampilan kartu game

~~~http
GET /jual-beli-akun/tampilan
~~~

Endpoint ini mengembalikan konfigurasi dari menu **Kelola Jual Beli Akun → Tampilan** pada company profile. Marketplace memakai `urutan`, `thumbnail_url`, dan `banner_url` agar kartu serta banner detail mengikuti pengaturan yang sama.

Respons 200:

~~~json
{
  "data": [
    {
      "game": "ml",
      "name": "Mobile Legends",
      "description": "Akun Mobile Legends rank tinggi, hero meta, dan skin lengkap, bergaransi.",
      "logo_url": "https://johengaming.id/img/logo/mobile-legend.png",
      "thumbnail": "jba/ml-card.webp",
      "thumbnail_url": "https://johengaming.id/storage/jba/ml-card.webp",
      "banner": "jba/ml-banner.webp",
      "banner_url": "https://johengaming.id/storage/jba/ml-banner.webp",
      "urutan": 1
    }
  ]
}
~~~

`thumbnail_url` dan `banner_url` akan bernilai `null` bila belum diatur. Frontend dapat memakai aset fallback pada kondisi tersebut.

### Daftar akun

~~~http
GET /jual-beli-akun
~~~

Parameter query:

| Parameter | Tipe | Wajib | Keterangan |
|---|---|---:|---|
| game | string | Tidak | ml, pubg, fc_mobile, ff, valorant, roblox, efootball |
| status | string | Tidak | tersedia atau habis |
| store | string | Tidak | johen atau monkey |
| cari | string | Tidak | Mencari record, id_game, penjual, dan deskripsi; maksimal 160 karakter |

Contoh:

~~~http
GET /jual-beli-akun?game=ml&status=tersedia
~~~

Respons 200:

~~~json
{
  "data": [
    {
      "id": 12,
      "game": "ml",
      "game_label": "Mobile Legends",
      "id_game": "ML-0012",
      "record": "Mythic 80 Star",
      "penjual": "Johen Gaming",
      "store": "johen",
      "foto": "jba/akun-12.jpg",
      "foto_url": "https://johengaming.id/storage/jba/akun-12.jpg",
      "harga": 1500000,
      "harga_final": 1200000,
      "diskon": 20,
      "diskon_aktif": true,
      "diskon_mulai": "2026-10-01T00:00:00+07:00",
      "diskon_selesai": "2026-10-31T23:59:59+07:00",
      "diskon_nonaktif": false,
      "label": "ML High Budget",
      "kolektor": "Kolektor Sultan",
      "deskripsi": "Akun siap dipakai.",
      "video": "https://youtu.be/xxxxxxxxxxx",
      "video_upload": "jba-video/demo.mp4",
      "video_upload_url": "https://johengaming.id/storage/jba-video/demo.mp4",
      "screenshots": ["jba/screenshot-1.jpg"],
      "screenshots_urls": ["https://johengaming.id/storage/jba/screenshot-1.jpg"],
      "status": "tersedia",
      "sold_at": null,
      "deal_type": "normal",
      "bandel_items": [],
      "urutan": 1,
      "wa_beli": "628123456789",
      "created_at": "2026-10-15T08:00:00+07:00",
      "updated_at": "2026-10-15T08:00:00+07:00"
    }
  ],
  "total": 1,
  "games": {
    "ml": "Mobile Legends",
    "pubg": "PUBG",
    "fc_mobile": "FC Mobile",
    "ff": "Free Fire",
    "valorant": "Valorant",
    "roblox": "Roblox",
    "efootball": "eFootball"
  }
}
~~~

Catatan:

- Endpoint ini tidak menggunakan pagination; seluruh hasil ada di data.
- harga adalah harga asli sebelum diskon.
- harga_final menghitung diskon yang sedang aktif.
- Jika diskon tidak aktif, harga_final sama dengan harga.
- URL file dibentuk dari domain backend yang sedang digunakan.

### Detail akun

~~~http
GET /jual-beli-akun/{id}
~~~

Contoh:

~~~http
GET /jual-beli-akun/12
~~~

Respons 200:

~~~json
{
  "data": {
    "id": 12,
    "game": "ml",
    "game_label": "Mobile Legends",
    "id_game": "ML-0012",
    "record": "Mythic 80 Star",
    "harga": 1500000,
    "harga_final": 1200000,
    "diskon": 20,
    "diskon_aktif": true,
    "status": "tersedia",
    "foto_url": "https://johengaming.id/storage/jba/akun-12.jpg",
    "screenshots_urls": [],
    "bandel_items": []
  }
}
~~~

Jika ID tidak ditemukan, API mengembalikan status 404 dalam format JSON.

## Endpoint admin akun

Semua endpoint admin membutuhkan:

~~~http
Authorization: Bearer TOKEN
Accept: application/json
~~~

Admin game hanya dapat melihat akun sesuai scope game dan toko. superadmin, record, dan nomor dapat melihat semua akun.

### Daftar akun admin

~~~http
GET /admin/jual-beli-akun
~~~

Filter query sama dengan endpoint publik: game, status, store, dan cari.

### Detail akun admin

~~~http
GET /admin/jual-beli-akun/{id}
~~~

Admin game yang membuka akun di luar scope mendapat status 403.

### Baca dan ubah tampilan kartu game

~~~http
GET /admin/jual-beli-akun/tampilan
Authorization: Bearer TOKEN
~~~

Endpoint baca mengembalikan format yang sama dengan endpoint tampilan publik. Untuk mengubah tampilan dari company profile atau marketplace:

~~~http
POST /admin/jual-beli-akun/tampilan
Authorization: Bearer API_KEY_MARKETPLACE
Content-Type: multipart/form-data
~~~

Field untuk setiap game (`ml`, `pubg`, `fc_mobile`, `ff`, `valorant`, `roblox`, `efootball`):

| Field | Keterangan |
|---|---|
| `jba_{game}_urutan` | Urutan kartu, mulai dari 1 |
| `jba_{game}_thumbnail` | Gambar kartu; maksimal 2 MB |
| `jba_{game}_hapus_thumbnail` | Boolean untuk menghapus gambar kartu |
| `jba_{game}_banner` | Banner hero detail; maksimal 4 MB |
| `jba_{game}_hapus_banner` | Boolean untuk menghapus banner |

Contoh:

~~~bash
curl -X POST "https://johengaming.id/api/admin/jual-beli-akun/tampilan" \
  -H "Authorization: Bearer API_KEY_MARKETPLACE" \
  -H "Accept: application/json" \
  -F "jba_ml_urutan=1" \
  -F "jba_ml_thumbnail=@ml-card.webp" \
  -F "jba_ml_banner=@ml-banner.webp"
~~~

Respons 200:

~~~json
{
  "success": true,
  "message": "Tampilan kartu game berhasil disimpan."
}
~~~

## Tambah akun

~~~http
POST /admin/jual-beli-akun
Authorization: Bearer TOKEN
Content-Type: multipart/form-data
~~~

Hanya superadmin, record, dan nomor yang dapat menambah akun.

### Field tambah dan edit

| Field | Tipe | Wajib | Aturan |
|---|---|---:|---|
| game | string | Ya | ml, pubg, fc_mobile, ff, valorant, roblox, efootball |
| id_game | string | Ya | Maksimal 255 karakter |
| foto | file gambar | Tambah: ya; edit: tidak | Maksimal 2048 KB |
| hapus_foto | boolean | Tidak | Saat edit, hapus foto lama |
| record | string | Normal: ya; bandel: tidak | Maksimal 160 karakter |
| penjual | string | Ya | Maksimal 80 karakter; awalan Monkey menentukan store monkey |
| harga | integer/string | Ya | 0 sampai 99999999999; format 1.500.000 juga diterima |
| diskon | numeric | Ya | 0 sampai 99 |
| diskon_mulai | datetime | Tidak | Waktu mulai diskon |
| diskon_selesai | datetime | Tidak | Tidak boleh sebelum diskon_mulai |
| diskon_nonaktif | boolean | Tidak | Menonaktifkan diskon tanpa menghapus nilainya |
| label | string | Normal: tidak; bandel: ya | Bandel harus memakai label budget yang tersedia |
| kolektor | string | Tidak | Dipakai untuk ML normal |
| deskripsi | string | Normal: ya; bandel: tidak | Maksimal 1000 karakter untuk akun normal |
| video | string | Tidak | URL atau ID video |
| video_upload | file MP4 | Tidak | Maksimal 204800 KB |
| hapus_video_upload | boolean | Tidak | Saat edit, hapus video upload lama |
| screenshots[] | file gambar | Tidak | Masing-masing maksimal 2048 KB |
| screenshots_hapus[] | string | Tidak | Path screenshot lama yang ingin dihapus |
| status | string | Ya | tersedia atau habis |
| deal_type | string | ML: ya; game lain: tidak | normal atau bandel |
| urutan | integer | Ya | Minimal 0 |

Label budget bandel:

~~~text
ML Low Budget
Budget Pelajar
Budget Mahasiswa
ML High Budget
Bandel Pangeran Sultan Terhormat
~~~

Label kolektor:

~~~text
Kolektor Ternama
Kolektor Terhormat
Kolektor Juragan
Kolektor Sultan
~~~

Contoh tambah akun normal:

~~~bash
curl -X POST "https://johengaming.id/api/admin/jual-beli-akun" \
  -H "Authorization: Bearer TOKEN" \
  -H "Accept: application/json" \
  -F "game=ml" \
  -F "id_game=ML-0012" \
  -F "foto=@akun.jpg" \
  -F "record=Mythic 80 Star" \
  -F "penjual=Johen Gaming" \
  -F "harga=1500000" \
  -F "diskon=20" \
  -F "label=ML High Budget" \
  -F "deskripsi=Akun siap dipakai" \
  -F "status=tersedia" \
  -F "deal_type=normal" \
  -F "urutan=1"
~~~

Respons 201:

~~~json
{
  "success": true,
  "id": 12
}
~~~

Ambil data lengkap setelah tambah:

~~~http
GET /admin/jual-beli-akun/12
~~~

## Edit akun

Edit dapat memakai PUT:

~~~http
PUT /admin/jual-beli-akun/{id}
Authorization: Bearer TOKEN
~~~

Untuk multipart, frontend dapat memakai POST pada URL yang sama:

~~~http
POST /admin/jual-beli-akun/{id}
Authorization: Bearer TOKEN
Content-Type: multipart/form-data
~~~

Respons 200:

~~~json
{
  "success": true,
  "id": 12
}
~~~

Foto lama tetap dipakai jika foto baru tidak dikirim. Untuk menghapus foto lama, kirim hapus_foto=true.

Screenshot lama tetap dipakai jika tidak dicantumkan dalam screenshots_hapus[]. Screenshot baru dikirim menggunakan screenshots[].

## Akun bandel Mobile Legends

Gunakan:

~~~text
game=ml
deal_type=bandel
~~~

Contoh field multipart:

~~~text
bandel_items[kode_akun][0]=ML-BANDEL-01
bandel_items[jumlah_hero][0]=120
bandel_items[jumlah_skin][0]=250
bandel_items[deskripsi][0]=Item akun pertama
bandel_items[foto][0]=@item-1.jpg
~~~

Setiap item bandel wajib memiliki kode_akun, jumlah_hero, jumlah_skin, deskripsi, dan foto. Jumlah item minimal 1 dan maksimal 5. record otomatis memakai id_game jika tidak dikirim.

## Hapus akun

~~~http
DELETE /admin/jual-beli-akun/{id}
Authorization: Bearer TOKEN
Content-Type: application/json
~~~

Hanya superadmin, record, dan nomor yang dapat menghapus. Jika memakai login user, nama user harus terdaftar sebagai penanggung jawab penghapusan pada pengaturan admin. Jika memakai API key, nama aplikasi dari `.env` dipakai otomatis.

Body JSON untuk login user:

~~~json
{
  "nama": "Admin Record"
}
~~~

Untuk API key, body boleh kosong karena nama aplikasi diambil dari konfigurasi key.

Respons 200:

~~~json
{
  "success": true,
  "message": "Stock akun berhasil dihapus.",
  "id": 12
}
~~~

Status yang mungkin:

| Status | Kondisi |
|---:|---|
| 401 | Token tidak ada, tidak valid, atau kedaluwarsa |
| 403 | Role tidak boleh menghapus atau nama belum terdaftar |
| 422 | nama kosong atau tidak cocok dengan user login; tidak berlaku untuk API key |
| 200 | Akun berhasil dihapus |

## Format error umum

Token tidak valid:

~~~json
{
  "message": "Sesi admin tidak valid atau sudah berakhir."
}
~~~

Role tidak memiliki akses:

~~~json
{
  "message": "Anda tidak memiliki akses ke akun jual beli."
}
~~~

Validasi gagal:

~~~json
{
  "message": "The given data was invalid.",
  "errors": {
    "game": ["The selected game is invalid."],
    "foto": ["The foto field is required."]
  }
}
~~~

Frontend sebaiknya menampilkan nilai pada object errors berdasarkan nama field.

## Contoh JavaScript

### Daftar publik

~~~javascript
const response = await fetch('https://johengaming.id/api/jual-beli-akun?game=ml&status=tersedia');
const result = await response.json();

if (!response.ok) {
  throw new Error(result.message || 'Gagal mengambil data akun');
}

const akun = result.data;
~~~

### Login admin

~~~javascript
const response = await fetch('https://johengaming.id/api/admin/login', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    email: 'admin@johengaming.com',
    password: 'password-admin'
  })
});

const result = await response.json();
localStorage.setItem('jba_api_token', result.token);
~~~

### Daftar akun admin

~~~javascript
const token = localStorage.getItem('jba_api_token');

const response = await fetch('https://johengaming.id/api/admin/jual-beli-akun', {
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer ' + token
  }
});

const result = await response.json();
~~~

### Tambah akun dengan foto

~~~javascript
const form = new FormData();
form.append('game', 'ml');
form.append('id_game', 'ML-0012');
form.append('foto', fileInput.files[0]);
form.append('record', 'Mythic 80 Star');
form.append('penjual', 'Johen Gaming');
form.append('harga', '1500000');
form.append('diskon', '20');
form.append('label', 'ML High Budget');
form.append('deskripsi', 'Akun siap dipakai');
form.append('status', 'tersedia');
form.append('deal_type', 'normal');
form.append('urutan', '1');

const response = await fetch('https://johengaming.id/api/admin/jual-beli-akun', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer ' + token
  },
  body: form
});

const result = await response.json();
~~~

Jangan mengisi Content-Type secara manual saat memakai FormData; browser menambahkan boundary multipart secara otomatis.

## Ringkasan endpoint

| Method | Endpoint | Auth | Fungsi |
|---|---|---|---|
| GET | /jual-beli-akun | Publik | Daftar akun |
| GET | /jual-beli-akun/tampilan | Publik | Konfigurasi urutan, kartu, dan banner game |
| GET | /jual-beli-akun/{id} | Publik | Detail akun |
| POST | /admin/login | Publik | Login admin dan mendapatkan token |
| GET | /admin/me | Bearer | Data user login |
| POST | /admin/logout | Bearer | Membatalkan token |
| GET | /admin/jual-beli-akun | Bearer | Daftar akun sesuai role/scope |
| GET | /admin/jual-beli-akun/tampilan | Bearer | Baca konfigurasi tampilan |
| PUT/POST | /admin/jual-beli-akun/tampilan | Bearer write | Ubah urutan, kartu, dan banner |
| GET | /admin/jual-beli-akun/{id} | Bearer | Detail akun sesuai role/scope |
| POST | /admin/jual-beli-akun | Bearer write | Tambah akun |
| PUT | /admin/jual-beli-akun/{id} | Bearer write | Edit akun |
| POST | /admin/jual-beli-akun/{id} | Bearer write | Edit akun multipart |
| DELETE | /admin/jual-beli-akun/{id} | Bearer write | Hapus akun |

Role write adalah superadmin, record, dan nomor.

