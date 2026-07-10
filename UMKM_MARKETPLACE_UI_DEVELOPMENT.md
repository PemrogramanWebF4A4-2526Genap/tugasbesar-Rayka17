# UMKM Laundry UI Development Guide

## Identitas Dokumen

Nama file:

```text
UMKM_MARKETPLACE_UI_DEVELOPMENT.md
```

Catatan:

Nama file masih mengikuti struktur lama project. Isi dokumen ini sudah disesuaikan menjadi panduan pengembangan UI untuk Sistem Informasi Pemesanan Laundry UMKM.

---

## 1. Tujuan Dokumen

Dokumen ini digunakan sebagai panduan pengembangan UI sistem laundry UMKM.

Dokumen ini menjadi acuan untuk:

1. Menjaga konsistensi tampilan.
2. Menentukan urutan pengembangan halaman.
3. Menjelaskan layout setiap role.
4. Menentukan komponen UI utama.
5. Menjaga responsive design.
6. Menghindari penggunaan istilah marketplace lama.
7. Memastikan UI sesuai dengan flow laundry.

---

## 2. Konsep UI

Konsep UI yang digunakan:

```text
Clean Laundry Service
Modern UMKM Platform
Fresh Blue Interface
Responsive Dashboard
Simple Customer Ordering
Role Based Management
```

Karakter UI:

- Bersih.
- Modern.
- Cerah.
- Mudah dipahami.
- Tidak padat.
- Responsif.
- Cocok untuk layanan laundry.
- Cocok untuk dashboard operasional.

Tema utama:

```text
Biru muda
Putih
Sky blue
Soft background
```

---

## 3. Teknologi UI

Teknologi yang digunakan:

```text
HTML5
CSS3
Tailwind CSS
JavaScript
PHP Native
MySQL
```

File CSS utama:

```text
src/assets/css/output.css
src/assets/css/modern.css
```

File JavaScript utama:

```text
src/assets/js/modern.js
```

Folder gambar layanan:

```text
src/assets/img/services/
```

---

## 4. Struktur UI Project

Struktur tampilan:

```text
src/views/
│
├── admin/
│   ├── dashboard.php
│   ├── users.php
│   ├── partners.php
│   ├── staff.php
│   ├── orders.php
│   └── services.php
│
├── buyer/
│   ├── create-order.php
│   ├── orders.php
│   ├── notifications.php
│   └── complaints.php
│
├── customer_service/
│   ├── dashboard.php
│   └── complaints.php
│
├── layouts/
│   ├── admin-sidebar.php
│   ├── admin-topbar.php
│   ├── seller-sidebar.php
│   ├── seller-topbar.php
│   ├── buyer-navbar.php
│   ├── customer-service-sidebar.php
│   └── customer-service-topbar.php
│
├── public/
│   ├── home.php
│   ├── login.php
│   ├── register.php
│   ├── logout.php
│   └── system-check.php
│
└── seller/
    ├── dashboard.php
    ├── orders.php
    ├── services.php
    ├── staff.php
    ├── petugas-dashboard.php
    ├── petugas-orders.php
    ├── petugas-tasks.php
    └── petugas-task.php
```

---

## 5. Design System Ringkas

### 5.1 Warna

Primary:

```css
#0284c7
```

Secondary:

```css
#0ea5e9
```

Light background:

```css
#f8fdff
```

Soft blue:

```css
#e0f2fe
```

Dark text:

```css
#0f172a
```

Muted text:

```css
#64748b
```

Status:

```text
success  : #16a34a
warning  : #f59e0b
danger   : #dc2626
info     : #2563eb
```

---

### 5.2 Font

Gunakan font:

```text
Inter
Poppins
Arial
sans-serif
```

Aturan:

- Heading menggunakan font tebal.
- Subtitle menggunakan warna muted.
- Label form menggunakan warna primary.
- Button menggunakan font tebal.
- Teks dashboard harus ringkas.

---

### 5.3 Komponen Utama

Komponen UI yang digunakan:

```text
navbar
sidebar
topbar
button
input
card
badge
table
details accordion
notification box
status pill
```

---

## 6. Phase Development

## Phase 1: Setup UI Dasar

### Step 1: Setup CSS

File yang digunakan:

```text
src/assets/css/output.css
src/assets/css/modern.css
```

Checklist:

- [ ] CSS dapat dipanggil di semua halaman.
- [ ] Class `modern-btn` tersedia.
- [ ] Class `modern-btn-outline` tersedia.
- [ ] Class `modern-card` tersedia.
- [ ] Class `modern-input` tersedia.
- [ ] Class `status-pill` tersedia.
- [ ] Background soft tersedia.

Done jika:

- Semua halaman dapat membaca style.
- Button, card, dan input tampil konsisten.

---

### Step 2: Setup JavaScript

File yang digunakan:

```text
src/assets/js/modern.js
```

Fungsi JavaScript:

- Toggle sidebar.
- Close sidebar.
- Realtime clock.
- Interaksi ringan pada UI.

Checklist:

- [ ] Sidebar mobile dapat dibuka.
- [ ] Sidebar mobile dapat ditutup.
- [ ] Realtime clock tampil.
- [ ] Tidak ada error console.

---

### Step 3: Setup Layout Reusable

Layout yang digunakan:

```text
src/views/layouts/buyer-navbar.php
src/views/layouts/admin-sidebar.php
src/views/layouts/admin-topbar.php
src/views/layouts/seller-sidebar.php
src/views/layouts/seller-topbar.php
src/views/layouts/customer-service-sidebar.php
src/views/layouts/customer-service-topbar.php
```

Checklist:

- [ ] Navbar buyer tampil.
- [ ] Sidebar admin tampil.
- [ ] Sidebar seller tampil.
- [ ] Sidebar petugas tampil melalui seller-sidebar.
- [ ] Sidebar customer service tampil.
- [ ] Topbar dashboard tampil.

---

## Phase 2: Public UI

### Step 4: Homepage

File:

```text
src/views/public/home.php
```

Section yang harus ada:

1. Hero section.
2. Form reservasi cepat.
3. Layanan laundry.
4. Mitra laundry.
5. CTA biru.

Ketentuan terbaru:

- Reservasi laundry berada di kanan area hero.
- Layanan laundry berada di atas mitra.
- Mitra laundry berada di bawah layanan.
- CTA biru berada paling bawah.
- Gambar layanan harus sesuai jenis cucian.
- Card layanan dibuat lebih kecil dan rapi.

Checklist:

- [ ] Hero tampil rapi.
- [ ] Reservasi cepat tampil di kanan.
- [ ] Layanan tampil sebelum mitra.
- [ ] Mitra memiliki gambar.
- [ ] CTA biru tetap tampil di bawah.
- [ ] Responsive di mobile.

---

### Step 5: Gambar Layanan

Folder:

```text
src/assets/img/services/
```

Nama gambar yang disarankan:

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

Function pada `home.php`:

```text
serviceImage($serviceName)
```

Tugas function:

- Membaca nama layanan.
- Menentukan gambar yang sesuai.
- Mengembalikan path gambar lokal.
- Menggunakan default image jika layanan tidak dikenali.

Checklist:

- [ ] Sarung bantal memakai gambar sarung bantal.
- [ ] Sprei memakai gambar sprei.
- [ ] Bed cover memakai gambar bed cover.
- [ ] Boneka memakai gambar boneka.
- [ ] Gorden memakai gambar gorden.
- [ ] Karpet memakai gambar karpet.
- [ ] Tas memakai gambar tas.
- [ ] Sepatu memakai gambar sepatu.
- [ ] Default image tersedia.

---

### Step 6: Login Page

File:

```text
src/views/public/login.php
```

Ketentuan:

- Form login sederhana.
- Tidak terlalu banyak teks.
- Email dan password jelas.
- Tombol login terlihat.
- Jika login berhasil, redirect sesuai role.

Checklist:

- [ ] Form login tampil rapi.
- [ ] Error message terlihat.
- [ ] Redirect admin benar.
- [ ] Redirect mitra benar.
- [ ] Redirect petugas benar.
- [ ] Redirect buyer benar.
- [ ] Redirect customer service benar.

---

### Step 7: Register Page

File:

```text
src/views/public/register.php
```

Ketentuan:

- Register digunakan untuk buyer.
- Orang umum hanya dapat membuat akun pelanggan.
- Seller dibuat atau disetujui admin.
- Petugas dibuat admin atau seller.

Checklist:

- [ ] Buyer dapat register.
- [ ] Role buyer tersimpan.
- [ ] Form responsive.
- [ ] Data masuk ke tabel users.

---

## Phase 3: Buyer UI

### Step 8: Create Order Page

File:

```text
src/views/buyer/create-order.php
```

Ketentuan:

- Buyer memilih seller.
- Buyer memilih layanan.
- Buyer tidak mengisi berat.
- Buyer tidak mengisi harga.
- Berat dan harga diisi seller setelah cucian dicek.
- Buyer memilih delivery option.
- Buyer memilih payment method.
- Sistem membuat tugas petugas jika pickup atau delivery dipilih.

Field utama:

```text
customer_name
phone
address
mitra_id
service_id
delivery_option
payment_method
pickup_address
delivery_address
notes
```

Checklist:

- [ ] Seller dapat dipilih.
- [ ] Layanan terfilter sesuai seller.
- [ ] Delivery fee tampil.
- [ ] Payment method hanya cod dan transfer.
- [ ] Order tersimpan.
- [ ] Payment tersimpan.
- [ ] Task petugas dibuat jika perlu.
- [ ] Notifikasi dibuat.

---

### Step 9: Buyer Orders Page

File:

```text
src/views/buyer/orders.php
```

Ketentuan:

- Buyer melihat pesanan miliknya.
- Buyer dapat memfilter status.
- Buyer melihat total harga.
- Buyer melihat status laundry.
- Buyer melihat status pembayaran.

Status filter:

```text
all
diproses
dicuci
selesai
diambil
```

Checklist:

- [ ] Data hanya milik buyer.
- [ ] Status badge tampil.
- [ ] Payment badge tampil.
- [ ] Detail alamat tampil.
- [ ] Responsive.

---

### Step 10: Buyer Notifications Page

File:

```text
src/views/buyer/notifications.php
```

Ketentuan:

- Menampilkan notifikasi buyer.
- Notifikasi dapat berkaitan dengan order, task, atau keluhan.
- Status read dan unread terlihat.

Checklist:

- [ ] Notifikasi tampil.
- [ ] Pesan notifikasi jelas.
- [ ] Tanggal tampil.
- [ ] Responsive.

---

### Step 11: Buyer Complaints Page

File:

```text
src/views/buyer/complaints.php
```

Ketentuan:

- Buyer dapat membuat keluhan.
- Buyer dapat memilih order.
- Buyer dapat membuat keluhan umum.
- Buyer dapat melihat balasan customer service.
- Buyer dapat membalas jika keluhan belum selesai.

Checklist:

- [ ] Form keluhan tampil.
- [ ] Order buyer dapat dipilih.
- [ ] Keluhan tersimpan.
- [ ] Riwayat balasan tampil.
- [ ] Buyer dapat membalas.
- [ ] Notifikasi ke customer service dibuat.

---

## Phase 4: Seller UI

### Step 12: Seller Dashboard

File:

```text
src/views/seller/dashboard.php
```

Data yang tampil:

```text
Total pesanan
Total layanan
Layanan aktif
Total petugas
Pendapatan
Pesanan terbaru
```

Checklist:

- [ ] Data berdasarkan mitra login.
- [ ] Statistic card tampil.
- [ ] Pesanan terbaru tampil.
- [ ] Tombol kelola pesanan tampil.
- [ ] Tombol kelola layanan tampil.

---

### Step 13: Seller Services

File:

```text
src/views/seller/services.php
```

Fitur:

- Tambah layanan.
- Edit layanan.
- Nonaktifkan layanan.
- Ubah harga.
- Ubah estimasi.
- Ubah deskripsi.

Checklist:

- [ ] Tambah layanan berjalan.
- [ ] Edit layanan berjalan.
- [ ] Nonaktifkan layanan berjalan.
- [ ] Status layanan tampil.
- [ ] Data hanya milik seller.

---

### Step 14: Seller Orders

File:

```text
src/views/seller/orders.php
```

Fitur:

- Melihat pesanan seller.
- Mengisi berat.
- Mengubah status laundry.
- Mengubah status pembayaran.
- Mengubah catatan.
- Sistem menghitung total harga.

Checklist:

- [ ] Data hanya milik seller.
- [ ] Berat bisa diisi.
- [ ] Total harga otomatis.
- [ ] Status laundry bisa diubah.
- [ ] Payment status bisa diubah.
- [ ] Payment table ikut update.
- [ ] Notifikasi buyer dibuat.

---

### Step 15: Seller Staff

File:

```text
src/views/seller/staff.php
```

Fitur:

- Tambah petugas.
- Edit petugas.
- Nonaktifkan petugas.
- Reset password.

Checklist:

- [ ] Petugas dibuat di users.
- [ ] Petugas dibuat di staff.
- [ ] Petugas terhubung ke mitra.
- [ ] Edit petugas berjalan.
- [ ] Reset password berjalan.

---

## Phase 5: Petugas UI

### Step 16: Petugas Dashboard

File:

```text
src/views/seller/petugas-dashboard.php
```

Data yang tampil:

```text
Pesanan seller
Tugas menunggu
Tugas saya
Tugas selesai
Tugas terbaru
```

Checklist:

- [ ] Data berdasarkan mitra petugas.
- [ ] Statistik tampil.
- [ ] Tugas terbaru tampil.
- [ ] Jika staff belum terhubung, pesan error tampil.

---

### Step 17: Petugas Orders

File:

```text
src/views/seller/petugas-orders.php
```

Fitur:

- Melihat pesanan seller.
- Mengubah status pesanan.
- Mengubah catatan.

Checklist:

- [ ] Pesanan tampil.
- [ ] Data sesuai mitra petugas.
- [ ] Status dapat diubah.
- [ ] Notifikasi buyer dibuat.

---

### Step 18: Petugas Tasks

File:

```text
src/views/seller/petugas-tasks.php
```

Fitur:

- Melihat tugas waiting.
- Mengambil tugas.
- Mengubah status tugas.
- Menyelesaikan tugas.
- Mengisi waktu pickup atau delivery.

Checklist:

- [ ] Tugas waiting tampil.
- [ ] Ambil tugas berjalan.
- [ ] Staff ID tersimpan.
- [ ] Status assigned berjalan.
- [ ] Status on_process berjalan.
- [ ] Status completed berjalan.
- [ ] picked_up_at terisi untuk pickup.
- [ ] delivered_at terisi untuk delivery.
- [ ] Notifikasi buyer dibuat.

---

## Phase 6: Admin UI

### Step 19: Admin Dashboard

File:

```text
src/views/admin/dashboard.php
```

Data yang tampil:

```text
Total user
Total seller
Total petugas
Total pesanan
Total keluhan
Pendapatan
Pesanan terbaru
```

Checklist:

- [ ] Statistik tampil.
- [ ] Pesanan terbaru tampil.
- [ ] Layout responsive.

---

### Step 20: Admin Partners

File:

```text
src/views/admin/partners.php
```

Fitur:

- Tambah seller.
- Edit seller.
- Ubah status seller.
- Reset password seller.

Checklist:

- [ ] Seller dibuat di users.
- [ ] Seller dibuat di laundry_mitras.
- [ ] Status seller tampil.
- [ ] Biaya pickup tersimpan.
- [ ] Biaya delivery tersimpan.

---

### Step 21: Admin Staff

File:

```text
src/views/admin/staff.php
```

Fitur:

- Tambah petugas.
- Edit petugas.
- Ubah status.
- Reset password.

Checklist:

- [ ] Petugas dapat dipilih berdasarkan seller.
- [ ] Petugas tersimpan di users dan staff.
- [ ] Status petugas sinkron.
- [ ] Reset password berjalan.

---

### Step 22: Admin Orders

File:

```text
src/views/admin/orders.php
```

Fitur:

- Melihat seluruh pesanan.
- Filter status.
- Melihat detail pesanan.
- Melihat seller.
- Melihat petugas.
- Melihat pembayaran.

Checklist:

- [ ] Semua order tampil.
- [ ] Filter berjalan.
- [ ] Detail tampil.
- [ ] Responsive.

---

### Step 23: Admin Services

File:

```text
src/views/admin/services.php
```

Fitur:

- Melihat seluruh layanan.
- Filter layanan berdasarkan seller.
- Melihat detail layanan.

Checklist:

- [ ] Semua layanan tampil.
- [ ] Filter seller berjalan.
- [ ] Detail layanan tampil.

---

## Phase 7: Customer Service UI

### Step 24: Customer Service Dashboard

File:

```text
src/views/customer_service/dashboard.php
```

Data yang tampil:

```text
Total keluhan
Menunggu
Diproses
Selesai
Keluhan terbaru
```

Checklist:

- [ ] Statistik keluhan tampil.
- [ ] Keluhan terbaru tampil.
- [ ] Layout responsive.

---

### Step 25: Customer Service Complaints

File:

```text
src/views/customer_service/complaints.php
```

Fitur:

- Melihat semua keluhan.
- Filter status.
- Melihat isi keluhan.
- Melihat riwayat balasan.
- Membalas keluhan.
- Menandai selesai.

Checklist:

- [ ] Keluhan tampil.
- [ ] Filter berjalan.
- [ ] Balasan tersimpan.
- [ ] Status update.
- [ ] closed_at terisi jika done.
- [ ] Notifikasi buyer dibuat.

---

## Phase 8: Responsive Testing

Halaman yang wajib dites:

```text
home.php
create-order.php
orders.php
admin/dashboard.php
seller/dashboard.php
seller/orders.php
seller/petugas-tasks.php
customer_service/complaints.php
```

Ukuran layar:

```text
Mobile
Tablet
Laptop
Desktop
```

Checklist:

- [ ] Tidak ada horizontal scroll.
- [ ] Card tidak terlalu sempit.
- [ ] Sidebar mobile berjalan.
- [ ] Button mudah diklik.
- [ ] Form tetap rapi.
- [ ] Gambar layanan tidak pecah.
- [ ] Grid berubah menjadi 1 kolom pada mobile.

---

## Phase 9: UI Consistency Check

Cek konsistensi:

- [ ] Warna button sama.
- [ ] Border radius sama.
- [ ] Card style sama.
- [ ] Input style sama.
- [ ] Badge status sama.
- [ ] Spacing sama.
- [ ] Navbar konsisten.
- [ ] Sidebar konsisten.
- [ ] Topbar konsisten.
- [ ] Tidak ada istilah marketplace lama.

Istilah yang tidak digunakan lagi:

```text
product
cart
checkout produk
courier
shipping
resi
review produk
kategori produk
stok produk
```

Istilah yang digunakan sekarang:

```text
laundry
layanan
seller
mitra
petugas
pesanan
pickup
delivery
status cucian
keluhan
customer service
```

---

## Phase 10: Final Testing Flow

Urutan final testing:

1. Login admin.
2. Tambah seller.
3. Tambah petugas.
4. Login seller.
5. Tambah layanan.
6. Login buyer.
7. Buat pesanan.
8. Cek pesanan buyer.
9. Login seller.
10. Isi berat dan update status.
11. Login petugas.
12. Ambil tugas.
13. Selesaikan tugas.
14. Login buyer.
15. Buat keluhan.
16. Login customer service.
17. Balas keluhan.
18. Tandai keluhan selesai.
19. Buka system check.

Halaman system check:

```text
src/views/public/system-check.php
```

---

## Final Checklist UI

Checklist akhir:

```text
[ ] Home sudah sesuai flow laundry.
[ ] Reservasi cepat berada di area hero.
[ ] Layanan laundry tampil dengan gambar.
[ ] Mitra laundry tampil di bawah layanan.
[ ] CTA biru tetap di bawah.
[ ] Buyer dapat membuat pesanan.
[ ] Seller dapat mengelola layanan.
[ ] Seller dapat mengelola pesanan.
[ ] Seller dapat mengelola petugas.
[ ] Petugas dapat mengambil tugas.
[ ] Customer service dapat membalas keluhan.
[ ] Admin dapat memantau sistem.
[ ] Semua sidebar sesuai role.
[ ] Semua halaman responsive.
[ ] Tidak ada error not found.
[ ] Tidak ada istilah marketplace lama pada UI utama.
```

---

## Target Akhir UI

Target akhir UI:

```text
Modern Laundry UMKM Website
Clean Customer Order Flow
Responsive Multi-role Dashboard
Operational Laundry Management System
Simple Customer Service Experience
```

UI harus mendukung alur laundry dari awal sampai akhir. Pelanggan memesan. Seller mengelola layanan dan pesanan. Petugas mengambil tugas. Customer service menangani keluhan. Admin memantau seluruh sistem.