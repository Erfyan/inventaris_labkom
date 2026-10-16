# Web Inventaris Lab SMKN 3 Soppeng

Aplikasi inventaris laboratorium berbasis **PHP Native + MySQL/MariaDB**. Cocok dijalankan di XAMPP, Laragon, atau server LAMP.

## Fitur utama

- Login multi-role: **Admin**, **Petugas Lab**, **Kepala Lab**
- Dashboard ringkasan inventaris
- Manajemen pengguna
- Master data kategori barang, ruangan/lab, supplier
- Data barang inventaris lengkap: kode, merk, spesifikasi, kondisi, ruangan, tahun pengadaan, sumber dana, harga, stok minimum
- Transaksi stok masuk, stok keluar, dan penyesuaian stok
- Peminjaman dan pengembalian barang dengan validasi stok
- Mutasi/pemindahan barang antar ruangan/lab
- Perawatan/perbaikan barang
- Laporan barang, stok, peminjaman, dan export CSV
- Proteksi dasar: prepared statement PDO, password hash, session auth, CSRF token, escape output

## Kebutuhan sistem

- PHP 8.0 atau lebih baru
- MySQL/MariaDB
- Extension PHP PDO MySQL aktif
- Browser modern

## Cara instalasi di XAMPP/Laragon

1. Ekstrak folder ini ke:
   - XAMPP: `htdocs/lab_inventaris_smkn3_soppeng`
   - Laragon: `www/lab_inventaris_smkn3_soppeng`
2. Buka phpMyAdmin.
3. Import file:
   - `database/schema.sql`
4. Sesuaikan koneksi database di:
   - `config/database.php`
5. Buka browser:
   - `http://localhost/lab_inventaris_smkn3_soppeng/login.php`

## Akun awal

- Admin: `admin` / `admin123`
- Petugas Lab: `petugas` / `admin123`
- Kepala Lab: `kepalalab` / `admin123`

Segera ganti password admin setelah login pertama.

## Pengaturan BASE_URL

Default aplikasi menggunakan link relatif sehingga biasanya langsung berjalan di subfolder. Jika ingin memakai URL absolut, ubah `BASE_URL` di `config/config.php`, contoh:

```php
define('BASE_URL', '/lab_inventaris_smkn3_soppeng');
```

## Struktur folder

```text
config/       konfigurasi aplikasi dan database
includes/     file helper, auth, layout
assets/       CSS dan JavaScript sederhana
database/     SQL schema + data awal
*.php         halaman aplikasi
```

## Catatan produksi

Untuk dipakai di server publik, aktifkan HTTPS, ganti password database, batasi akses folder `database/`, dan gunakan backup database berkala.
