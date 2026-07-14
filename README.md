# UMKM Sistem Informasi Pemesanan Laundry Berbasis Web

## Deskripsi Project

UMKM Sistem Informasi Pemesanan Laundry Berbasis Web adalah aplikasi web yang digunakan untuk membantu proses pemesanan dan pengelolaan layanan laundry pada UMKM. Sistem ini menyediakan fitur pemesanan laundry oleh pelanggan, pengelolaan layanan oleh seller/mitra laundry, pengelolaan petugas, pengelolaan tugas pickup dan delivery, tracking status cucian, pembayaran, notifikasi, serta fitur customer service untuk menangani keluhan pelanggan.

Project ini dikembangkan menggunakan PHP Native dan MySQL. Sistem dibuat dengan pembagian hak akses berdasarkan role pengguna, sehingga setiap pengguna hanya dapat mengakses halaman yang sesuai dengan kebutuhan dan tanggung jawabnya.

---

## Tujuan Project

Tujuan dari project ini adalah:

1. Mempermudah pelanggan dalam melakukan pemesanan laundry secara online.
2. Membantu seller/mitra laundry dalam mengelola layanan dan pesanan.
3. Membantu petugas dalam menangani pickup dan delivery.
4. Membantu admin dalam mengelola data pengguna, seller, petugas, layanan, dan pesanan.
5. Menyediakan fitur keluhan agar pelanggan dapat menyampaikan kendala.
6. Membantu customer service dalam membalas dan menyelesaikan keluhan pelanggan.
7. Membuat proses operasional laundry menjadi lebih rapi, cepat, dan terdokumentasi.

---

## Teknologi yang Digunakan

Project ini dibuat menggunakan:

- PHP Native
- MySQL
- HTML
- CSS
- JavaScript
- Laragon atau XAMPP sebagai local server
- phpMyAdmin untuk pengelolaan database
- Google Chrome sebagai browser pengujian

---

## Role Pengguna

Sistem ini memiliki lima role utama.

### 1. Admin

Admin memiliki akses untuk:

- Melihat dashboard admin.
- Mengelola data pengguna.
- Mengelola seller/mitra laundry.
- Mengelola petugas.
- Melihat seluruh layanan laundry.
- Melihat seluruh pesanan laundry.
- Melihat keluhan pelanggan.

### 2. Seller / Mitra Laundry

Seller atau mitra laundry memiliki akses untuk:

- Melihat dashboard seller.
- Mengelola layanan laundry.
- Melihat dan mengelola pesanan masuk.
- Mengubah berat cucian.
- Mengubah status pesanan.
- Mengubah status pembayaran.
- Mengelola petugas milik seller tersebut.

### 3. Petugas

Petugas memiliki akses untuk:

- Melihat dashboard petugas.
- Melihat pesanan dari seller tempat petugas terhubung.
- Mengambil tugas pickup dan delivery.
- Mengubah status tugas.
- Menyelesaikan tugas pickup dan delivery.

### 4. Buyer / Pelanggan

Buyer atau pelanggan memiliki akses untuk:

- Melihat halaman utama.
- Melakukan reservasi atau membuat pesanan laundry.
- Melihat status pesanan.
- Melihat notifikasi.
- Membuat keluhan.
- Membalas keluhan jika masih diproses.

### 5. Customer Service

Customer service memiliki akses untuk:

- Melihat dashboard keluhan.
- Melihat seluruh keluhan pelanggan.
- Membalas keluhan pelanggan.
- Mengubah status keluhan menjadi pending, process, atau done.

---

## Fitur Utama

Fitur utama pada sistem ini meliputi:

1. Login dan logout berdasarkan role.
2. Register pelanggan.
3. Dashboard admin.
4. Pengelolaan seller/mitra laundry.
5. Pengelolaan petugas.
6. Pengelolaan layanan laundry.
7. Pembuatan pesanan laundry oleh pelanggan.
8. Perhitungan total harga berdasarkan berat cucian dan biaya delivery.
9. Pengelolaan status laundry.
10. Pengelolaan status pembayaran.
11. Tugas pickup dan delivery untuk petugas.
12. Notifikasi perubahan status.
13. Keluhan pelanggan.
14. Balasan keluhan oleh customer service.
15. Halaman system check untuk memeriksa kelengkapan sistem.

---

## Struktur Folder Project

```text
Rayka_Tugas_BesarWeb/
│
├── src/
│   ├── assets/
│   │   ├── css/
│   │   │   ├── modern.css
│   │   │   └── output.css
│   │   │
│   │   ├── js/
│   │   │   └── modern.js
│   │   │
│   │   └── img/
│   │       └── services/
│   │
│   ├── config/
│   │   └── database.php
│   │
│   └── views/
│       ├── admin/
│       ├── buyer/
│       ├── customer_service/
│       ├── layouts/
│       ├── public/
│       └── seller/
│
├── README.md
├── USER_MANUAL.md
├── design.md
├── database_shema.pdf
├── umkm_marketplace_documentation.md
└── umkm_marketplace_ui_development.md