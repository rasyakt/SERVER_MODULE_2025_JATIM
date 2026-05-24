# Walkthrough - Penyelesaian File Pendukung Gaming Portal (JATIM 2025)

Saya telah berhasil menyiapkan seluruh file pendukung yang diminta oleh soal `MODULE_SERVER_SIDE.pdf` untuk modul **Gaming Portal (Jatim 2025)** dengan spesifik dan akurat sesuai ketentuan.

---

## File yang Berhasil Dibuat

Berikut adalah daftar file baru yang ditambahkan baik di **root project** maupun di folder **backend (laravel)** untuk mempermudah pengerjaan dan pengujian:

1. **Database SQL Dumps**:
   - `lks-server.sql` (Root & Laravel)
   - `data_dump.sql` (Root & Laravel)
   - *Isi*: Berisi skema database lengkap (`users`, `games`, `game_versions`, `scores`) beserta data dummy dengan enkripsi password menggunakan Bcrypt yang valid.
2. **Postman API Collections**:
   - `postman_collection.json` (Root & Laravel)
   - *Isi*: Koleksi request API lengkap untuk seluruh modul (Authentication, Users, Games, Scores) lengkap dengan visualisasi path variables, query parameters, dan automatic token capture scripts.
3. **Postman Environments**:
   - `postman_environment.json` (Root & Laravel)
   - *Isi*: Variabel environment `baseUrl` dan `token` yang siap langsung diimpor ke Postman.

---

## Detail Database Dump (`lks-server.sql` / `data_dump.sql`)

### Skema Tabel
- **`users`**: Menyimpan data akun Administrator, Developer, dan Players beserta flags blokir (`is_blocked` & `block_reason`) dan timestamp login terakhir (`last_login_at`).
- **`games`**: Menyimpan judul, deskripsi, slug unik, dan referensi pembuat (`author_id`).
- **`game_versions`**: Menyimpan relasi game, nomor versi incrementing, thumbnail (opsional), dan path publik game.
- **`scores`**: Menyimpan relasi skor pemain (`user_id`, `game_id`, `game_version_id`) beserta timestamp.

### Data Akun Default yang Disediakan (Password terenkripsi Bcrypt yang valid)
- **Administrators**:
  - `admin1` / `hellouniverse1!`
  - `admin2` / `hellouniverse2!`
- **Developers**:
  - `dev1` / `hellobyte1!`
  - `dev2` / `hellobyte2!`
- **Players**:
  - `player1` / `helloworld1!`
  - `player2` / `helloworld2!`
- **Blocked Player (Untuk test handling error)**:
  - `blocked_player` / `helloworld1!` (Status blocked dengan reason sesuai dokumen)

---

## Detail Postman API Collection

Koleksi Postman dirancang terstruktur sesuai modul pengerjaan:
1. **Authentication**:
   - `Sign Up` & `Sign In`: Dilengkapi dengan **Test Script** otomatis untuk menyimpan token Sanctum langsung ke environment variable `token` saat login berhasil.
   - `Sign Out`
2. **Users**:
   - `Get All Admin Data`
   - `Create a User`
   - `Get User Details List`
   - `Update a User`
   - `Delete a User`
   - `Get User Profile`
3. **Games**:
   - `Get Paginated Games List` (Dengan default parameter query: page, size, sortBy, sortDir)
   - `Create a Game`
   - `Get Game Details`
   - `Upload Game File` (Menggunakan format `formdata` dengan file input `zipfile` dan field `token`)
   - `Serve Game Files`
   - `Update Game Details`
   - `Delete a Game`
4. **Scores**:
   - `Get Game Scores` (Menampilkan daftar highscore)
   - `Post Game Score`

---

## Cara Penggunaan / Import

1. **Import Database**:
   - Anda dapat membuat database baru bernama `lks_jatim2025` melalui phpMyAdmin / DB Client Anda, lalu import file `lks-server.sql` atau `data_dump.sql` yang sudah tersedia di root project Anda.
2. **Import Postman**:
   - Buka Postman, klik **Import**, lalu pilih file `postman_collection.json` dan `postman_environment.json`.
   - Pilih environment **LKS Gaming Portal - Server Side JATIM 2025** di pojok kanan atas Postman untuk memulai pengujian API Anda secara interaktif.
