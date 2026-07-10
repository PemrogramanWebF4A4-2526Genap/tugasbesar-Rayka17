# User Manual Sistem Informasi Pemesanan Laundry UMKM

## Pendahuluan

Dokumen ini menjelaskan cara menggunakan aplikasi **UMKM Sistem Informasi Pemesanan Laundry Berbasis Web**. Aplikasi ini digunakan untuk membantu proses layanan laundry pada UMKM, mulai dari pemesanan pelanggan, pengelolaan layanan oleh seller atau mitra laundry, pengelolaan petugas, pickup dan delivery, tracking status cucian, pembayaran, notifikasi, sampai penanganan keluhan pelanggan oleh customer service.

Sistem ini memiliki beberapa role pengguna. Setiap role memiliki hak akses dan halaman yang berbeda sesuai dengan kebutuhan masing-masing.

Role yang digunakan dalam sistem:

```text
admin
mitra
petugas
buyer
customer_service
```

Keterangan role:

- `admin` adalah pengelola utama sistem.
- `mitra` adalah seller atau pemilik laundry.
- `petugas` adalah staff operasional laundry.
- `buyer` adalah pelanggan laundry.
- `customer_service` adalah petugas yang menangani keluhan pelanggan.

---

## 1. Cara Mengakses Aplikasi

Jalankan server lokal menggunakan Laragon atau XAMPP. Pastikan folder project berada di dalam folder server lokal.

Contoh lokasi project:

```text
C:\laragon\www\Rayka_Tugas_BesarWeb
```

Setelah server aktif, buka aplikasi melalui browser:

```text
http://localhost/Rayka_Tugas_BesarWeb/src/views/public/home.php
```

Halaman utama akan menampilkan:

1. Navbar Laundry UMKM.
2. Hero section.
3. Form reservasi laundry cepat.
4. Daftar layanan laundry.
5. Daftar mitra laundry.
6. Tombol untuk membuat pesanan laundry.

---

## 2. Halaman Utama

Halaman utama berada pada file:

```text
src/views/public/home.php
```

Halaman ini digunakan sebagai tampilan awal aplikasi. Pelanggan dapat melihat layanan laundry yang tersedia dan memulai proses pemesanan.

Fitur pada halaman utama:

1. Melihat informasi Laundry UMKM.
2. Melihat form reservasi cepat.
3. Melihat daftar layanan laundry.
4. Melihat daftar mitra laundry.
5. Mengakses halaman order laundry.
6. Mengakses status cucian.
7. Mengakses notifikasi.
8. Login atau logout sesuai status pengguna.

Daftar layanan laundry dapat menampilkan gambar layanan seperti:

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

Gambar layanan dapat disimpan pada folder:

```text
src/assets/img/services/
```

---

## 3. Login Pengguna

Halaman login berada pada file:

```text
src/views/public/login.php
```

### Langkah Login

1. Buka halaman login.
2. Masukkan email.
3. Masukkan password.
4. Klik tombol **Masuk**.
5. Sistem akan memeriksa email, password, status akun, dan role pengguna.
6. Jika login berhasil, pengguna diarahkan ke halaman sesuai role.

### Arah Halaman Setelah Login

```text
admin              -> src/views/admin/dashboard.php
mitra              -> src/views/seller/dashboard.php
petugas            -> src/views/seller/petugas-dashboard.php
buyer              -> src/views/buyer/orders.php
customer_service   -> src/views/customer_service/dashboard.php
```

### Penyebab Login Gagal

Login dapat gagal jika:

1. Email tidak ditemukan.
2. Password salah.
3. Akun belum aktif.
4. Akun sedang diblokir.
5. Role pengguna tidak sesuai.

---

## 4. Logout Pengguna

Logout digunakan untuk keluar dari sistem dan menghapus session pengguna.

File logout berada pada:

```text
src/views/public/logout.php
```

Langkah logout:

1. Klik tombol **Logout**.
2. Sistem menghapus session.
3. Pengguna keluar dari sistem.
4. Pengguna diarahkan kembali ke halaman login atau halaman utama.

---

## 5. Panduan Admin

Admin adalah pengguna yang memiliki akses utama dalam sistem. Admin dapat mengelola pengguna, seller, petugas, layanan, pesanan, dan keluhan.

---

### 5.1 Dashboard Admin

Halaman dashboard admin berada pada:

```text
src/views/admin/dashboard.php
```

Dashboard admin menampilkan ringkasan data sistem, yaitu:

1. Total pengguna.
2. Total seller atau mitra laundry.
3. Total petugas.
4. Total pesanan.
5. Total keluhan.
6. Total pendapatan dari pembayaran lunas.
7. Pesanan terbaru.

Dashboard digunakan admin untuk memantau kondisi sistem secara umum.

---

### 5.2 Mengelola Pengguna

Halaman pengguna admin berada pada:

```text
src/views/admin/users.php
```

Admin dapat melihat data pengguna yang terdaftar dalam sistem.

Role pengguna yang tersedia:

```text
admin
mitra
petugas
buyer
customer_service
```

Admin dapat memeriksa:

1. Nama pengguna.
2. Email pengguna.
3. Role pengguna.
4. Status akun pengguna.
5. Data kontak pengguna.

---

### 5.3 Mengelola Seller / Mitra Laundry

Halaman seller atau mitra laundry admin berada pada:

```text
src/views/admin/partners.php
```

Seller atau mitra laundry adalah pihak UMKM laundry yang menyediakan layanan kepada pelanggan.

Admin dapat menambahkan seller baru dengan mengisi:

1. Nama akun.
2. Email.
3. Password.
4. Nama seller laundry.
5. Nama pemilik.
6. Nomor telepon.
7. Kota.
8. Alamat.
9. Deskripsi.
10. Biaya pickup.
11. Biaya delivery.
12. Status seller.

Status seller yang digunakan:

```text
pending
active
blocked
```

Keterangan status:

- `pending` berarti seller belum aktif sepenuhnya.
- `active` berarti seller aktif dan layanan dapat digunakan pelanggan.
- `blocked` berarti seller diblokir.

Admin juga dapat:

1. Mengedit data seller.
2. Mengubah status seller.
3. Reset password seller menjadi `123456`.

---

### 5.4 Mengelola Petugas

Halaman petugas admin berada pada:

```text
src/views/admin/staff.php
```

Petugas adalah user yang membantu proses operasional laundry, terutama pickup dan delivery.

Admin dapat menambahkan petugas dan menghubungkannya ke seller tertentu.

Data yang diisi saat menambah petugas:

1. Seller laundry.
2. Nama petugas.
3. Email.
4. Password.
5. Nomor telepon.
6. Alamat.

Status petugas:

```text
active
inactive
```

Keterangan status:

- `active` berarti petugas aktif dan dapat login.
- `inactive` berarti petugas nonaktif.

Admin dapat:

1. Menambah petugas.
2. Mengedit data petugas.
3. Mengubah status petugas.
4. Reset password petugas menjadi `123456`.

---

### 5.5 Melihat Semua Pesanan

Halaman semua pesanan berada pada:

```text
src/views/admin/orders.php
```

Admin dapat melihat seluruh pesanan dari semua seller.

Informasi pesanan yang ditampilkan:

1. Nomor order.
2. Nama pelanggan.
3. Nomor telepon pelanggan.
4. Nama layanan.
5. Nama seller.
6. Berat cucian.
7. Harga satuan.
8. Total biaya delivery.
9. Total harga.
10. Status laundry.
11. Status pembayaran.
12. Petugas yang menangani.
13. Alamat pelanggan.
14. Alamat pickup.
15. Alamat pengantaran.
16. Catatan pesanan.

Admin dapat memfilter pesanan berdasarkan status:

```text
all
diproses
dicuci
selesai
diambil
```

---

### 5.6 Melihat Semua Layanan

Halaman layanan admin berada pada:

```text
src/views/admin/services.php
```

Admin dapat melihat semua layanan laundry dari seluruh seller.

Informasi layanan yang ditampilkan:

1. Nama layanan.
2. Nama seller.
3. Kategori layanan.
4. Harga layanan.
5. Satuan.
6. Estimasi pengerjaan.
7. Deskripsi layanan.
8. Status layanan.

Admin juga dapat memfilter layanan berdasarkan seller laundry.

---

## 6. Panduan Seller / Mitra Laundry

Seller atau mitra laundry adalah pengguna yang menyediakan layanan laundry kepada pelanggan.

---

### 6.1 Dashboard Seller

Halaman dashboard seller berada pada:

```text
src/views/seller/dashboard.php
```

Dashboard seller menampilkan:

1. Total pesanan.
2. Total layanan.
3. Total layanan aktif.
4. Total petugas.
5. Total pendapatan.
6. Pesanan terbaru.

Dashboard ini digunakan seller untuk melihat ringkasan operasional laundry.

---

### 6.2 Mengelola Layanan Laundry

Halaman layanan seller berada pada:

```text
src/views/seller/services.php
```

Seller dapat menambahkan layanan laundry yang akan ditampilkan pada halaman home.

Data layanan yang diisi:

1. Nama layanan.
2. Kategori.
3. Harga.
4. Satuan.
5. Estimasi pengerjaan.
6. Deskripsi.
7. Status layanan.

Contoh layanan laundry:

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

Status layanan:

```text
active
inactive
```

Keterangan status:

- `active` berarti layanan tampil dan dapat dipilih pelanggan.
- `inactive` berarti layanan tidak aktif.

Langkah menambah layanan:

1. Login sebagai seller.
2. Buka menu **Layanan**.
3. Isi form tambah layanan.
4. Klik tombol **Simpan Layanan**.
5. Layanan masuk ke daftar layanan seller.
6. Jika status layanan aktif, layanan akan tampil di halaman home.

---

### 6.3 Mengelola Pesanan Seller

Halaman pesanan seller berada pada:

```text
src/views/seller/orders.php
```

Seller dapat melihat pesanan yang masuk sesuai seller miliknya.

Seller dapat memperbarui:

1. Berat atau jumlah cucian.
2. Status laundry.
3. Status pembayaran.
4. Catatan pesanan.

Status laundry:

```text
diproses
dicuci
selesai
diambil
```

Status pembayaran:

```text
unpaid
waiting_confirmation
paid
cancelled
```

Sistem menghitung total harga dengan rumus:

```text
total harga = berat x harga satuan + biaya delivery
```

Contoh:

```text
Berat cucian       : 3 kg
Harga satuan       : Rp 8.000
Biaya delivery     : Rp 5.000

Total harga        : 3 x 8.000 + 5.000
                   : Rp 29.000
```

Jika seller mengubah status pembayaran menjadi `paid`, data pembayaran akan ikut diperbarui.

---

### 6.4 Mengelola Petugas Seller

Halaman petugas seller berada pada:

```text
src/views/seller/staff.php
```

Seller dapat mengelola petugas miliknya sendiri.

Data yang diisi saat menambah petugas:

1. Nama petugas.
2. Email.
3. Nomor telepon.
4. Password.
5. Alamat.

Seller dapat:

1. Menambah petugas.
2. Mengedit data petugas.
3. Mengubah status petugas.
4. Reset password petugas menjadi `123456`.

Petugas yang dibuat seller otomatis terhubung ke seller tersebut.

---

## 7. Panduan Petugas

Petugas adalah pengguna yang membantu seller dalam proses operasional laundry.

Halaman petugas disimpan dalam folder seller karena petugas merupakan bagian dari seller atau mitra laundry.

---

### 7.1 Dashboard Petugas

Halaman dashboard petugas berada pada:

```text
src/views/seller/petugas-dashboard.php
```

Dashboard petugas menampilkan:

1. Total pesanan seller.
2. Jumlah tugas menunggu.
3. Jumlah tugas saya.
4. Jumlah tugas selesai.
5. Daftar tugas terbaru.

Jika akun petugas belum terhubung ke data staff, sistem akan menampilkan pesan bahwa akun petugas belum terhubung ke seller.

---

### 7.2 Melihat Pesanan Seller

Halaman pesanan petugas berada pada:

```text
src/views/seller/petugas-orders.php
```

Petugas dapat melihat pesanan laundry dari seller tempat petugas terhubung.

Petugas dapat memperbarui:

1. Status laundry.
2. Catatan pesanan.

Status pesanan yang tersedia:

```text
diproses
dicuci
selesai
diambil
```

Halaman ini membantu petugas memantau status cucian yang sedang diproses.

---

### 7.3 Mengambil Tugas Pickup dan Delivery

Halaman tugas petugas berada pada:

```text
src/views/seller/petugas-tasks.php
```

Tugas pickup dan delivery dibuat otomatis saat buyer membuat pesanan dengan opsi:

```text
pickup_only
delivery_only
pickup_delivery
```

Jenis tugas:

```text
pickup
delivery
```

Status tugas:

```text
waiting
assigned
on_process
completed
cancelled
```

Langkah mengambil tugas:

1. Login sebagai petugas.
2. Buka menu **Tugas**.
3. Cari tugas dengan status `waiting`.
4. Klik tombol **Ambil Tugas**.
5. Sistem mengubah status tugas menjadi `assigned`.
6. Tugas terhubung ke petugas yang mengambilnya.

---

### 7.4 Mengubah Status Tugas

Petugas dapat mengubah status tugas menjadi:

```text
assigned
on_process
completed
cancelled
```

Keterangan:

- `assigned` berarti tugas sudah diambil.
- `on_process` berarti tugas sedang dikerjakan.
- `completed` berarti tugas sudah selesai.
- `cancelled` berarti tugas dibatalkan.

Jika tugas pickup selesai, sistem akan mengisi waktu pada kolom:

```text
picked_up_at
```

Jika tugas delivery selesai, sistem akan mengisi waktu pada kolom:

```text
delivered_at
```

Pada tugas delivery yang selesai, status pesanan dapat berubah menjadi:

```text
diambil
```

---

### 7.5 File Redirect Petugas

Sistem memiliki file:

```text
src/views/seller/petugas-task.php
```

File ini digunakan sebagai redirect ke:

```text
src/views/seller/petugas-tasks.php
```

File redirect ini berguna untuk mencegah error not found jika terdapat link lama yang mengarah ke `petugas-task.php`.

---

## 8. Panduan Buyer / Pelanggan

Buyer adalah pelanggan yang menggunakan sistem untuk melakukan pemesanan laundry.

---

### 8.1 Register Buyer

Halaman register berada pada:

```text
src/views/public/register.php
```

Langkah register:

1. Buka halaman register.
2. Isi nama.
3. Isi email.
4. Isi password.
5. Isi nomor telepon jika tersedia.
6. Isi alamat jika tersedia.
7. Klik tombol daftar.
8. Setelah berhasil, login menggunakan akun yang dibuat.

Akun pelanggan menggunakan role:

```text
buyer
```

---

### 8.2 Melakukan Reservasi Cepat dari Home

Pada halaman home, pelanggan dapat menggunakan form reservasi cepat.

Langkah reservasi cepat:

1. Login sebagai buyer.
2. Buka halaman home.
3. Pilih layanan laundry pada form reservasi.
4. Isi catatan singkat jika diperlukan.
5. Klik tombol **Lanjut Buat Pesanan**.
6. Sistem membuka halaman pembuatan pesanan.

---

### 8.3 Membuat Pesanan Laundry

Halaman membuat pesanan berada pada:

```text
src/views/buyer/create-order.php
```

Langkah membuat pesanan:

1. Login sebagai buyer.
2. Buka menu **Order Laundry**.
3. Pilih seller laundry.
4. Pilih layanan laundry.
5. Isi nama pelanggan.
6. Isi nomor telepon.
7. Isi alamat utama.
8. Pilih opsi delivery.
9. Pilih metode pembayaran.
10. Isi alamat pickup jika diperlukan.
11. Isi alamat pengantaran jika diperlukan.
12. Isi catatan jika ada.
13. Klik tombol **Buat Pesanan**.

Opsi delivery:

```text
self_service
pickup_only
delivery_only
pickup_delivery
```

Keterangan opsi delivery:

- `self_service` berarti pelanggan antar dan ambil sendiri.
- `pickup_only` berarti petugas hanya menjemput cucian.
- `delivery_only` berarti petugas hanya mengantar cucian.
- `pickup_delivery` berarti petugas menjemput dan mengantar cucian.

Metode pembayaran:

```text
cod
transfer
```

Saat pesanan dibuat, sistem akan:

1. Menyimpan data pesanan.
2. Menyimpan data pembayaran.
3. Membuat log status pesanan.
4. Membuat tugas pickup atau delivery jika dibutuhkan.
5. Mengirim notifikasi ke buyer.
6. Mengirim notifikasi ke seller.

---

### 8.4 Melihat Pesanan

Halaman pesanan buyer berada pada:

```text
src/views/buyer/orders.php
```

Buyer dapat melihat daftar pesanan yang pernah dibuat.

Informasi yang ditampilkan:

1. Nomor order.
2. Nama layanan.
3. Nama seller.
4. Nomor telepon seller.
5. Berat cucian.
6. Harga satuan.
7. Biaya pickup.
8. Biaya delivery.
9. Total pembayaran.
10. Status laundry.
11. Status pembayaran.
12. Alamat utama.
13. Alamat pickup.
14. Alamat pengantaran.
15. Catatan pesanan.

Buyer dapat memfilter pesanan berdasarkan status:

```text
all
diproses
dicuci
selesai
diambil
```

---

### 8.5 Melihat Notifikasi

Halaman notifikasi buyer berada pada:

```text
src/views/buyer/notifications.php
```

Notifikasi digunakan untuk memberikan informasi kepada buyer, seperti:

1. Pesanan berhasil dibuat.
2. Pesanan diperbarui seller.
3. Tugas diambil petugas.
4. Tugas selesai.
5. Keluhan ditanggapi customer service.

---

### 8.6 Membuat Keluhan

Halaman keluhan buyer berada pada:

```text
src/views/buyer/complaints.php
```

Buyer dapat membuat keluhan jika terdapat kendala pada layanan laundry.

Langkah membuat keluhan:

1. Login sebagai buyer.
2. Buka menu **Keluhan**.
3. Pilih pesanan yang ingin dikeluhkan.
4. Isi judul keluhan.
5. Isi pesan keluhan.
6. Klik tombol **Kirim Keluhan**.

Buyer juga dapat membuat keluhan umum tanpa memilih pesanan tertentu.

Status keluhan:

```text
pending
process
done
```

---

### 8.7 Membalas Keluhan

Jika keluhan belum selesai, buyer dapat menambahkan balasan.

Langkah membalas keluhan:

1. Buka halaman keluhan.
2. Pilih keluhan.
3. Buka bagian balasan.
4. Isi balasan lanjutan.
5. Klik tombol **Kirim Balasan**.

Balasan akan masuk ke riwayat keluhan dan dapat dilihat oleh customer service.

---

## 9. Panduan Customer Service

Customer service adalah pengguna yang bertugas menangani keluhan pelanggan.

---

### 9.1 Dashboard Customer Service

Halaman dashboard customer service berada pada:

```text
src/views/customer_service/dashboard.php
```

Dashboard customer service menampilkan:

1. Total keluhan.
2. Keluhan menunggu.
3. Keluhan diproses.
4. Keluhan selesai.
5. Keluhan terbaru.

---

### 9.2 Mengelola Keluhan Pelanggan

Halaman keluhan customer service berada pada:

```text
src/views/customer_service/complaints.php
```

Customer service dapat melihat semua keluhan pelanggan.

Informasi yang ditampilkan:

1. Nomor keluhan.
2. Nomor order jika ada.
3. Judul keluhan.
4. Nama pelanggan.
5. Email pelanggan.
6. Isi keluhan.
7. Riwayat balasan.
8. Status keluhan.

Customer service dapat memfilter keluhan berdasarkan status:

```text
all
pending
process
done
```

---

### 9.3 Membalas Keluhan

Langkah membalas keluhan:

1. Login sebagai customer service.
2. Buka menu **Keluhan Pelanggan**.
3. Pilih keluhan.
4. Pilih status keluhan.
5. Isi balasan.
6. Klik tombol **Kirim Balasan**.

Jika status dipilih `done`, sistem akan mengisi waktu selesai pada kolom:

```text
closed_at
```

---

### 9.4 Menandai Keluhan Selesai

Customer service dapat menandai keluhan sebagai selesai.

Langkah menandai selesai:

1. Buka halaman keluhan pelanggan.
2. Pilih keluhan yang ingin diselesaikan.
3. Klik tombol **Tandai Selesai**.
4. Sistem mengubah status keluhan menjadi `done`.

---

## 10. Status dalam Sistem

### 10.1 Status Pesanan Laundry

```text
diproses
dicuci
selesai
diambil
```

Keterangan:

- `diproses` berarti pesanan baru masuk atau sedang dicek.
- `dicuci` berarti cucian sedang dikerjakan.
- `selesai` berarti cucian selesai dikerjakan.
- `diambil` berarti cucian sudah diambil pelanggan atau selesai dikirim.

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
- `paid` berarti sudah lunas.
- `cancelled` berarti pembayaran atau pesanan dibatalkan.

---

### 10.3 Status Tugas Petugas

```text
waiting
assigned
on_process
completed
cancelled
```

Keterangan:

- `waiting` berarti tugas belum diambil petugas.
- `assigned` berarti tugas sudah diambil petugas.
- `on_process` berarti tugas sedang dikerjakan.
- `completed` berarti tugas selesai.
- `cancelled` berarti tugas dibatalkan.

---

### 10.4 Status Keluhan

```text
pending
process
done
```

Keterangan:

- `pending` berarti keluhan baru masuk.
- `process` berarti keluhan sedang diproses.
- `done` berarti keluhan selesai.

---

## 11. Alur Lengkap Sistem

### 11.1 Alur Admin dan Seller

1. Admin login.
2. Admin membuat atau mengaktifkan seller.
3. Seller login.
4. Seller membuat layanan laundry.
5. Seller membuat petugas.
6. Layanan aktif muncul pada halaman home.

---

### 11.2 Alur Pemesanan Buyer

1. Buyer login.
2. Buyer membuka halaman home atau order laundry.
3. Buyer memilih seller.
4. Buyer memilih layanan.
5. Buyer mengisi data pemesanan.
6. Buyer memilih opsi delivery.
7. Buyer memilih metode pembayaran.
8. Buyer membuat pesanan.
9. Sistem menyimpan pesanan.
10. Seller menerima pesanan.
11. Seller menimbang cucian.
12. Seller mengisi berat.
13. Sistem menghitung total harga.
14. Seller memperbarui status laundry.
15. Buyer memantau status pesanan.

---

### 11.3 Alur Pickup dan Delivery

1. Buyer memilih opsi pickup atau delivery.
2. Sistem membuat tugas pada tabel tugas petugas.
3. Petugas login.
4. Petugas membuka menu tugas.
5. Petugas mengambil tugas.
6. Status tugas berubah menjadi assigned.
7. Petugas mengubah status menjadi on_process.
8. Petugas menyelesaikan tugas.
9. Sistem mengisi waktu pickup atau delivery.
10. Buyer menerima notifikasi.

---

### 11.4 Alur Keluhan

1. Buyer membuat keluhan.
2. Customer service melihat keluhan.
3. Customer service membalas keluhan.
4. Status keluhan berubah menjadi process.
5. Buyer dapat membalas kembali jika masih ada kendala.
6. Customer service menandai keluhan selesai.
7. Status keluhan berubah menjadi done.

---

## 12. System Check

Sistem memiliki halaman pengecekan:

```text
src/views/public/system-check.php
```

Halaman ini digunakan untuk memeriksa kelengkapan sistem.

Hal yang diperiksa:

1. Koneksi database.
2. Tabel utama.
3. Kolom penting.
4. Role pengguna.
5. File penting.

Halaman ini berguna untuk memastikan aplikasi siap digunakan sebelum presentasi atau pengujian.

---

## 13. Gambar Layanan

Gambar layanan dapat disimpan pada folder:

```text
src/assets/img/services/
```

Contoh nama gambar:

```text
sarung-bantal.jpg
sprei.jpg
bed-cover.jpg
boneka.jpg
gorden.jpg
karpet.jpg
tas.jpg
sepatu.jpg
laundry-default.jpg
```

Gambar layanan digunakan pada halaman home agar pelanggan lebih mudah mengenali jenis layanan laundry.

Jika gambar tidak tampil, cek:

1. Nama file gambar.
2. Ekstensi file gambar.
3. Folder penyimpanan gambar.
4. Path gambar pada function `serviceImage()` di `home.php`.

---

## 14. Catatan Error Umum

### 14.1 Error Not Found pada Petugas

Jika halaman tugas petugas tidak ditemukan, pastikan file berikut tersedia:

```text
src/views/seller/petugas-tasks.php
src/views/seller/petugas-task.php
```

File `petugas-task.php` digunakan sebagai redirect ke `petugas-tasks.php`.

---

### 14.2 Error Tabel Tidak Ditemukan

Jika muncul error tabel tidak ditemukan, cek apakah database sudah menggunakan tabel terbaru.

Tabel utama yang harus ada:

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

### 14.3 Error Role Login

Jika login tidak mengarah ke halaman yang sesuai, cek kolom `role` pada tabel `users`.

Role yang digunakan:

```text
admin
mitra
petugas
buyer
customer_service
```

---

### 14.4 Error Gambar Tidak Tampil

Jika gambar layanan tidak tampil:

1. Pastikan gambar sudah disimpan di folder `src/assets/img/services/`.
2. Pastikan nama file sesuai.
3. Pastikan path gambar benar.
4. Tekan `CTRL + F5` untuk menghapus cache browser.

---

## 15. Contoh Akun Demo

Contoh akun demo yang dapat digunakan:

```text
Admin:
Email    : admin@gmail.com
Password : 123456

Seller/Mitra:
Email    : mitra@gmail.com
Password : 123456

Petugas:
Email    : petugas@gmail.com
Password : 123456

Buyer:
Email    : buyer@gmail.com
Password : 123456

Customer Service:
Email    : cs@gmail.com
Password : 123456
```

Catatan: akun demo dapat berbeda sesuai data pada database masing-masing.

---

## 16. Penutup

User manual ini dibuat untuk membantu pengguna memahami cara menggunakan Sistem Informasi Pemesanan Laundry UMKM. Sistem ini mendukung proses pemesanan laundry secara online, pengelolaan seller, pengelolaan layanan, pengelolaan petugas, pickup dan delivery, tracking status cucian, pembayaran, notifikasi, dan penanganan keluhan pelanggan.

Dengan adanya sistem ini, proses operasional laundry dapat menjadi lebih rapi, cepat, dan terdokumentasi.