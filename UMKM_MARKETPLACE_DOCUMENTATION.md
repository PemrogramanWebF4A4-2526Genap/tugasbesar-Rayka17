# UMKM Sistem Informasi Pemesanan Laundry Berbasis Web

## Identitas Project

Nama project:

```text
UMKM Sistem Informasi Pemesanan Laundry Berbasis Web
```

Nama file dokumentasi:

```text
UMKM_MARKETPLACE_DOCUMENTATION.md
```

Catatan:

Nama file masih menggunakan istilah marketplace karena mengikuti struktur file project lama. Isi dokumen sudah disesuaikan sepenuhnya menjadi sistem pemesanan laundry UMKM.

---

## 1. Deskripsi Project

Project ini merupakan sistem informasi pemesanan laundry berbasis web yang dibuat untuk membantu proses layanan laundry pada UMKM. Sistem ini menyediakan fitur pemesanan laundry, pengelolaan layanan, pengelolaan seller atau mitra laundry, pengelolaan petugas, pickup dan delivery, tracking status pesanan, pembayaran, notifikasi, dan customer service untuk menangani keluhan pelanggan.

Sistem ini menggunakan konsep multi-role. Setiap role memiliki halaman dan hak akses yang berbeda sesuai dengan kebutuhan operasional laundry.

Role yang digunakan:

```text
admin
mitra
petugas
buyer
customer_service
```

---

## 2. Latar Belakang

UMKM laundry membutuhkan sistem pencatatan yang rapi karena proses operasional melibatkan pelanggan, layanan, petugas, status cucian, pembayaran, dan pengantaran. Proses manual sering menimbulkan masalah seperti data pesanan tercecer, pelanggan sulit mengetahui status cucian, seller kesulitan mengatur petugas, dan keluhan pelanggan tidak terdokumentasi.

Sistem ini dibuat untuk mengatasi masalah tersebut. Aplikasi membantu pelanggan membuat pesanan secara online. Seller dapat mengelola layanan dan pesanan. Petugas dapat menangani pickup dan delivery. Admin dapat memantau sistem secara keseluruhan. Customer service dapat menangani keluhan pelanggan.

---

## 3. Tujuan Project

Tujuan project:

1. Mempermudah pelanggan dalam membuat pesanan laundry.
2. Membantu seller mengelola layanan laundry.
3. Membantu seller mengelola pesanan masuk.
4. Membantu seller dan admin mengelola petugas.
5. Membantu petugas menangani tugas pickup dan delivery.
6. Membantu pelanggan memantau status cucian.
7. Membantu customer service menangani keluhan.
8. Membuat proses operasional laundry lebih rapi dan terdokumentasi.
9. Menyediakan dashboard untuk setiap role.
10. Menyediakan alur sistem yang mudah diuji untuk kebutuhan tugas besar.

---

## 4. Teknologi yang Digunakan

Teknologi yang digunakan:

```text
PHP Native
MySQL
HTML5
CSS3
JavaScript
Tailwind CSS
Laragon atau XAMPP
phpMyAdmin
```

Keterangan:

- PHP Native digunakan sebagai backend.
- MySQL digunakan untuk database.
- HTML, CSS, dan JavaScript digunakan untuk frontend.
- Tailwind CSS dan CSS custom digunakan untuk styling.
- Laragon atau XAMPP digunakan sebagai local server.
- phpMyAdmin digunakan untuk mengelola database.

---

## 5. Struktur Role

### 5.1 Admin

Admin adalah pengelola utama sistem.

Fitur admin:

1. Login admin.
2. Melihat dashboard admin.
3. Mengelola pengguna.
4. Mengelola seller atau mitra laundry.
5. Mengelola petugas.
6. Melihat seluruh layanan.
7. Melihat seluruh pesanan.
8. Melihat data keluhan pelanggan.

Halaman admin:

```text
src/views/admin/dashboard.php
src/views/admin/users.php
src/views/admin/partners.php
src/views/admin/staff.php
src/views/admin/orders.php
src/views/admin/services.php
```

---

### 5.2 Seller / Mitra Laundry

Seller atau mitra adalah pihak laundry yang menyediakan layanan.

Fitur seller:

1. Login seller.
2. Melihat dashboard seller.
3. Mengelola layanan laundry.
4. Mengelola pesanan masuk.
5. Mengubah berat cucian.
6. Mengubah status laundry.
7. Mengubah status pembayaran.
8. Mengelola petugas milik seller.

Halaman seller:

```text
src/views/seller/dashboard.php
src/views/seller/orders.php
src/views/seller/services.php
src/views/seller/staff.php
```

---

### 5.3 Petugas

Petugas adalah staff operasional yang menangani pickup dan delivery.

Fitur petugas:

1. Login petugas.
2. Melihat dashboard petugas.
3. Melihat pesanan seller.
4. Mengambil tugas pickup dan delivery.
5. Mengubah status tugas.
6. Menyelesaikan tugas pickup dan delivery.

Halaman petugas:

```text
src/views/seller/petugas-dashboard.php
src/views/seller/petugas-orders.php
src/views/seller/petugas-tasks.php
src/views/seller/petugas-task.php
```

Catatan:

File `petugas-task.php` digunakan sebagai redirect ke `petugas-tasks.php`.

---

### 5.4 Buyer / Pelanggan

Buyer adalah pelanggan laundry.

Fitur buyer:

1. Register.
2. Login.
3. Melihat halaman home.
4. Membuat pesanan laundry.
5. Melihat status pesanan.
6. Melihat notifikasi.
7. Membuat keluhan.
8. Membalas keluhan.

Halaman buyer:

```text
src/views/buyer/create-order.php
src/views/buyer/orders.php
src/views/buyer/notifications.php
src/views/buyer/complaints.php
```

---

### 5.5 Customer Service

Customer service adalah role yang menangani keluhan pelanggan.

Fitur customer service:

1. Login customer service.
2. Melihat dashboard keluhan.
3. Melihat semua keluhan.
4. Membalas keluhan pelanggan.
5. Mengubah status keluhan.
6. Menandai keluhan selesai.

Halaman customer service:

```text
src/views/customer_service/dashboard.php
src/views/customer_service/complaints.php
```

---

## 6. Struktur Folder Project

Struktur folder utama:

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
├── DESIGN.md
├── database_shema.pdf
├── UMKM_MARKETPLACE_DOCUMENTATION.md
└── UMKM_MARKETPLACE_UI_DEVELOPMENT.md
```

---

## 7. Struktur Layout

File layout:

```text
src/views/layouts/admin-sidebar.php
src/views/layouts/admin-topbar.php
src/views/layouts/seller-sidebar.php
src/views/layouts/seller-topbar.php
src/views/layouts/buyer-navbar.php
src/views/layouts/customer-service-sidebar.php
src/views/layouts/customer-service-topbar.php
```

Fungsi layout:

- Menjaga konsistensi tampilan.
- Mengurangi duplikasi kode.
- Memudahkan navigasi role.
- Memudahkan maintenance UI.

---

## 8. Database Utama

Tabel utama:

```text
users
laundry_mitras
laundry_services
laundry_orders
laundry_payments
laundry_staff_tasks
laundry_order_status_logs
staff
notifications
complaints
complaint_replies
```

---

## 9. Deskripsi Tabel

### 9.1 users

Tabel `users` menyimpan akun seluruh pengguna.

Role user:

```text
admin
mitra
petugas
buyer
customer_service
```

Kolom penting:

```text
id
name
email
password
role
mitra_id
status
phone
address
created_at
```

Fungsi tabel:

- Menyimpan akun login.
- Menentukan role pengguna.
- Menyimpan relasi petugas dengan mitra melalui `mitra_id`.
- Menyimpan status akun.

---

### 9.2 laundry_mitras

Tabel `laundry_mitras` menyimpan data seller atau mitra laundry.

Kolom penting:

```text
id
user_id
mitra_name
owner_name
phone
city
address
description
pickup_fee
delivery_fee
status
created_at
updated_at
```

Fungsi tabel:

- Menyimpan identitas seller laundry.
- Menyimpan biaya pickup dan delivery.
- Menyimpan status seller.
- Menghubungkan akun mitra dengan user pemilik.

---

### 9.3 laundry_services

Tabel `laundry_services` menyimpan layanan laundry milik seller.

Kolom penting:

```text
id
mitra_id
service_name
service_category
price_per_kg
unit
estimated_time
description
status
```

Contoh layanan:

```text
Sarung Bantal
Laundry Sprei
Laundry Bed Cover
Laundry Boneka
Laundry Gorden
Laundry Karpet
Laundry Tas
Laundry Sepatu
```

Fungsi tabel:

- Menyimpan daftar layanan.
- Menyimpan harga layanan.
- Menyimpan estimasi pengerjaan.
- Menentukan layanan aktif atau tidak aktif.

---

### 9.4 laundry_orders

Tabel `laundry_orders` menyimpan pesanan laundry pelanggan.

Kolom penting:

```text
id
user_id
service_id
mitra_id
staff_id
customer_name
phone
address
weight
price_per_kg
total_price
status
notes
delivery_option
pickup_fee
delivery_fee
delivery_total
payment_method
payment_status
payment_proof
pickup_address
delivery_address
picked_up_at
delivered_at
created_at
updated_at
```

Fungsi tabel:

- Menyimpan data order laundry.
- Menyimpan relasi pelanggan, layanan, mitra, dan petugas.
- Menyimpan status laundry.
- Menyimpan status pembayaran.
- Menyimpan opsi pickup dan delivery.
- Menyimpan total harga.

---

### 9.5 laundry_payments

Tabel `laundry_payments` menyimpan data pembayaran.

Kolom penting:

```text
id
order_id
user_id
payment_method
amount
payment_status
payment_proof
paid_at
created_at
updated_at
```

Fungsi tabel:

- Menyimpan metode pembayaran.
- Menyimpan jumlah pembayaran.
- Menyimpan bukti pembayaran jika tersedia.
- Menyimpan status pembayaran.

---

### 9.6 laundry_staff_tasks

Tabel `laundry_staff_tasks` menyimpan tugas pickup dan delivery.

Kolom penting:

```text
id
order_id
staff_id
task_type
task_status
address
fee
note
completed_at
created_at
updated_at
```

Fungsi tabel:

- Menyimpan tugas pickup.
- Menyimpan tugas delivery.
- Menyimpan status tugas petugas.
- Menyimpan alamat tugas.
- Menyimpan waktu tugas selesai.

---

### 9.7 laundry_order_status_logs

Tabel `laundry_order_status_logs` menyimpan riwayat perubahan status pesanan.

Kolom penting:

```text
id
order_id
user_id
old_status
new_status
note
created_at
```

Fungsi tabel:

- Mencatat perubahan status laundry.
- Menyimpan user yang mengubah status.
- Menyimpan catatan perubahan.

---

### 9.8 staff

Tabel `staff` menyimpan data petugas yang terhubung ke seller.

Kolom penting:

```text
id
mitra_id
user_id
fullname
phone
address
status
created_at
```

Fungsi tabel:

- Menyimpan profil petugas.
- Menghubungkan petugas dengan mitra.
- Menyimpan status aktif atau nonaktif.

---

### 9.9 notifications

Tabel `notifications` menyimpan notifikasi user.

Kolom penting:

```text
id
user_id
title
message
is_read
created_at
```

Fungsi tabel:

- Menyimpan notifikasi buyer.
- Menyimpan notifikasi seller.
- Menyimpan informasi perubahan status.

---

### 9.10 complaints

Tabel `complaints` menyimpan keluhan pelanggan.

Kolom penting:

```text
id
buyer_id
order_id
title
message
status
reply_by
closed_at
created_at
```

Fungsi tabel:

- Menyimpan keluhan pelanggan.
- Menghubungkan keluhan dengan order jika ada.
- Menyimpan status keluhan.
- Menyimpan customer service yang menangani.

---

### 9.11 complaint_replies

Tabel `complaint_replies` menyimpan balasan keluhan.

Kolom penting:

```text
id
complaint_id
replier_id
reply
created_at
```

Fungsi tabel:

- Menyimpan balasan customer service.
- Menyimpan balasan buyer.
- Menyimpan riwayat komunikasi keluhan.

---

## 10. Status Sistem

### 10.1 Status Pesanan

```text
diproses
dicuci
selesai
diambil
```

Keterangan:

- `diproses` berarti pesanan masuk dan sedang dicek.
- `dicuci` berarti cucian sedang dikerjakan.
- `selesai` berarti cucian selesai diproses.
- `diambil` berarti cucian sudah diambil pelanggan atau selesai delivery.

---

### 10.2 Status Pembayaran

```text
unpaid
waiting_confirmation
paid
cancelled
```

Keterangan:

- `unpaid` berarti belum dibayar.
- `waiting_confirmation` berarti menunggu konfirmasi pembayaran.
- `paid` berarti pembayaran lunas.
- `cancelled` berarti pembayaran atau pesanan dibatalkan.

---

### 10.3 Metode Pembayaran

```text
cod
transfer
```

---

### 10.4 Opsi Delivery

```text
self_service
pickup_only
delivery_only
pickup_delivery
```

Keterangan:

- `self_service` berarti pelanggan antar dan ambil sendiri.
- `pickup_only` berarti petugas hanya menjemput cucian.
- `delivery_only` berarti petugas hanya mengantar cucian.
- `pickup_delivery` berarti petugas menjemput dan mengantar cucian.

---

### 10.5 Status Tugas

```text
waiting
assigned
on_process
completed
cancelled
```

Keterangan:

- `waiting` berarti tugas belum diambil.
- `assigned` berarti tugas sudah diambil petugas.
- `on_process` berarti tugas sedang dikerjakan.
- `completed` berarti tugas selesai.
- `cancelled` berarti tugas dibatalkan.

---

### 10.6 Status Keluhan

```text
pending
process
done
```

Keterangan:

- `pending` berarti keluhan baru masuk.
- `process` berarti keluhan sedang ditangani.
- `done` berarti keluhan selesai.

---

## 11. Alur Sistem

### 11.1 Alur Setup Admin dan Seller

1. Admin login.
2. Admin membuat seller.
3. Seller login.
4. Seller membuat layanan laundry.
5. Seller membuat petugas.
6. Layanan aktif tampil di halaman home.

---

### 11.2 Alur Pemesanan Buyer

1. Buyer login.
2. Buyer membuka halaman home atau order laundry.
3. Buyer memilih seller laundry.
4. Buyer memilih layanan.
5. Buyer mengisi data pemesanan.
6. Buyer memilih opsi delivery.
7. Buyer memilih metode pembayaran.
8. Buyer membuat pesanan.
9. Sistem menyimpan pesanan.
10. Sistem membuat data pembayaran.
11. Sistem membuat tugas pickup atau delivery jika diperlukan.
12. Seller menerima pesanan.
13. Seller menimbang cucian.
14. Seller mengisi berat.
15. Sistem menghitung total harga.
16. Seller memperbarui status laundry.
17. Buyer memantau status pesanan.

---

### 11.3 Alur Pickup dan Delivery

1. Buyer memilih opsi pickup atau delivery.
2. Sistem membuat tugas pada tabel `laundry_staff_tasks`.
3. Petugas login.
4. Petugas membuka halaman tugas.
5. Petugas mengambil tugas waiting.
6. Status tugas berubah menjadi assigned.
7. Petugas memproses tugas.
8. Petugas menyelesaikan tugas.
9. Sistem mengisi waktu pickup atau delivery.
10. Buyer menerima notifikasi.

---

### 11.4 Alur Keluhan

1. Buyer membuat keluhan.
2. Customer service melihat keluhan.
3. Customer service membalas keluhan.
4. Status keluhan berubah menjadi process.
5. Buyer dapat membalas kembali.
6. Customer service menandai keluhan selesai.
7. Status keluhan berubah menjadi done.

---

## 12. Halaman Utama Sistem

### 12.1 Public

```text
src/views/public/home.php
src/views/public/login.php
src/views/public/register.php
src/views/public/logout.php
src/views/public/system-check.php
```

### 12.2 Admin

```text
src/views/admin/dashboard.php
src/views/admin/users.php
src/views/admin/partners.php
src/views/admin/staff.php
src/views/admin/orders.php
src/views/admin/services.php
```

### 12.3 Seller

```text
src/views/seller/dashboard.php
src/views/seller/orders.php
src/views/seller/services.php
src/views/seller/staff.php
```

### 12.4 Petugas

```text
src/views/seller/petugas-dashboard.php
src/views/seller/petugas-orders.php
src/views/seller/petugas-tasks.php
src/views/seller/petugas-task.php
```

### 12.5 Buyer

```text
src/views/buyer/create-order.php
src/views/buyer/orders.php
src/views/buyer/notifications.php
src/views/buyer/complaints.php
```

### 12.6 Customer Service

```text
src/views/customer_service/dashboard.php
src/views/customer_service/complaints.php
```

---

## 13. Keamanan Sistem

Keamanan dasar yang digunakan:

1. Session login.
2. Validasi role pada halaman.
3. Password menggunakan `password_hash()`.
4. Verifikasi password menggunakan `password_verify()`.
5. Escape input menggunakan `mysqli_real_escape_string()`.
6. Output HTML menggunakan `htmlspecialchars()`.
7. Pembatasan akses berdasarkan role.
8. Query data berdasarkan user atau mitra yang sesuai.

Catatan:

Untuk pengembangan lanjutan, prepared statement dapat digunakan agar keamanan query lebih kuat.

---

## 14. System Check

Halaman system check berada pada:

```text
src/views/public/system-check.php
```

Fungsi system check:

1. Mengecek koneksi database.
2. Mengecek tabel utama.
3. Mengecek kolom penting.
4. Mengecek role pengguna.
5. Mengecek file penting.

Halaman ini membantu memastikan aplikasi siap digunakan sebelum demo atau presentasi.

---

## 15. File Lama yang Tidak Dipakai

Karena sistem sudah berubah menjadi laundry, istilah marketplace lama tidak digunakan lagi.

File atau folder lama yang dapat dihapus jika masih ada:

```text
src/views/courier/
src/views/admin/couriers.php
src/views/seller/add-service.php
src/views/seller/edit-service.php
src/views/seller/delete-service.php
```

Tabel lama yang dapat dihapus jika masih ada:

```text
laundry_partners
laundry_delivery_tasks
couriers
delivery_tasks
```

Tabel utama yang tidak boleh dihapus:

```text
users
laundry_mitras
laundry_services
laundry_orders
laundry_payments
laundry_staff_tasks
laundry_order_status_logs
staff
notifications
complaints
complaint_replies
```

---

## 16. Pengujian Sistem

Urutan pengujian:

1. Login admin.
2. Tambah seller.
3. Tambah petugas.
4. Login seller.
5. Tambah layanan.
6. Login buyer.
7. Buat pesanan.
8. Login seller.
9. Update berat dan status pesanan.
10. Login petugas.
11. Ambil tugas pickup atau delivery.
12. Selesaikan tugas.
13. Login buyer.
14. Cek status pesanan.
15. Buat keluhan.
16. Login customer service.
17. Balas keluhan.
18. Tandai keluhan selesai.
19. Buka system check.

---

## 17. Kesimpulan

Project ini adalah sistem informasi pemesanan laundry UMKM berbasis web. Sistem memiliki alur lengkap dari pelanggan membuat pesanan, seller mengelola layanan dan pesanan, petugas menangani pickup dan delivery, admin memantau sistem, sampai customer service menangani keluhan pelanggan.

Sistem ini sudah tidak menggunakan konsep marketplace produk. Alur utama sudah disesuaikan menjadi layanan laundry dengan role admin, mitra, petugas, buyer, dan customer service.