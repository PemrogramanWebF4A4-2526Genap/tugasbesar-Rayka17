# DESIGN.md

# Design System Sistem Informasi Pemesanan Laundry UMKM

## Identitas Project

Nama project:

```text
UMKM Sistem Informasi Pemesanan Laundry Berbasis Web
```

Jenis aplikasi:

```text
Aplikasi web pemesanan dan pengelolaan layanan laundry UMKM
```

Teknologi utama:

```text
PHP Native
MySQL
HTML5
CSS3
JavaScript
Tailwind CSS
```

Dokumen ini menjadi acuan desain UI/UX, struktur layout, komponen visual, dan standar tampilan sistem laundry UMKM. Dokumen ini menggantikan konsep lama marketplace produk. Semua istilah dan komponen sudah disesuaikan dengan sistem laundry.

---

## 1. Konsep Desain

Sistem menggunakan konsep desain modern, bersih, ringan, dan mudah digunakan. Tema visual menyesuaikan karakter layanan laundry yang identik dengan kebersihan, air, kerapian, dan pelayanan cepat.

Konsep utama UI:

```text
Clean laundry service platform
Modern UMKM service application
Role based dashboard
Responsive interface
Light and fresh visual
Simple order flow
Operational dashboard for laundry
Service focused layout
```

Target tampilan:

- Bersih.
- Rapi.
- Cerah.
- Mudah dipahami.
- Tidak padat.
- Cocok untuk pelanggan umum.
- Cocok untuk admin dan seller.
- Cocok untuk operasional petugas.
- Cocok untuk customer service.

---

## 2. Prinsip Desain

Prinsip desain yang digunakan:

1. Konsisten pada semua halaman.
2. Mudah dibaca.
3. Mudah digunakan.
4. Responsif di desktop dan mobile.
5. Fokus pada alur laundry.
6. Komponen dapat digunakan ulang.
7. Tampilan tidak terlalu penuh.
8. Aksi utama terlihat jelas.
9. Informasi status mudah dipahami.
10. UI mendukung kebutuhan setiap role.

Prioritas desain:

```text
Readability
Consistency
Responsiveness
Usability
Maintainability
Clean visual hierarchy
```

---

## 3. Karakter UI

UI harus terasa:

- Modern.
- Cerah.
- Profesional.
- Ramah pelanggan.
- Bersih.
- Ringan.
- Tidak membingungkan.
- Cocok untuk aplikasi layanan laundry.
- Mudah digunakan oleh admin, seller, petugas, buyer, dan customer service.

Hindari:

- Warna terlalu gelap.
- Layout terlalu padat.
- Terlalu banyak animasi.
- Shadow terlalu berat.
- Button tidak konsisten.
- Form terlalu panjang tanpa spacing.
- Card layanan tanpa gambar.
- Dashboard yang sulit dibaca.
- Istilah marketplace lama seperti produk, cart, checkout produk, courier, shipping, resi, dan stok produk.

---

## 4. Warna Utama

Tema warna sistem menggunakan warna biru muda dan putih. Warna ini dipilih agar tampilan terasa bersih dan relevan dengan layanan laundry.

### Primary Blue

```css
#0284c7
```

Digunakan untuk:

- Tombol utama.
- Heading kecil.
- Link aktif.
- Badge informasi.
- Icon penting.
- Elemen brand.

### Sky Blue

```css
#0ea5e9
```

Digunakan untuk:

- Gradient button.
- Hover state.
- Accent pada card.
- Highlight pada dashboard.

### Light Blue

```css
#e0f2fe
```

Digunakan untuk:

- Background card ringan.
- Section background.
- Badge lembut.
- Area highlight.

### Soft Background

```css
#f8fdff
```

Digunakan untuk:

- Background halaman.
- Dashboard background.
- Area content utama.

### Dark Text

```css
#0f172a
```

Digunakan untuk:

- Judul utama.
- Heading.
- Teks penting.

### Muted Text

```css
#64748b
```

Digunakan untuk:

- Subtitle.
- Deskripsi.
- Teks sekunder.

---

## 5. Warna Status

### Success

```css
#16a34a
```

Digunakan untuk:

- Status selesai.
- Status aktif.
- Status pembayaran lunas.
- Tugas selesai.

### Warning

```css
#f59e0b
```

Digunakan untuk:

- Status menunggu.
- Pending.
- Waiting confirmation.

### Danger

```css
#dc2626
```

Digunakan untuk:

- Error.
- Gagal.
- Cancelled.
- Blocked.
- Inactive.

### Info

```css
#2563eb
```

Digunakan untuk:

- Diproses.
- Dicuci.
- Assigned.
- On process.

---

## 6. Tipografi

Font utama yang direkomendasikan:

```text
Inter
```

Font alternatif:

```text
Poppins
Arial
sans-serif
```

Aturan tipografi:

- Heading harus tebal.
- Body text harus mudah dibaca.
- Subtitle menggunakan warna muted.
- Label form menggunakan warna primary.
- Button menggunakan font tebal.
- Jangan gunakan ukuran font terlalu besar pada dashboard.

Ukuran teks:

```text
Hero title       : 44px sampai 62px
Page title       : 30px sampai 38px
Section title    : 28px sampai 38px
Card title       : 18px sampai 24px
Body text        : 14px sampai 16px
Small text       : 12px sampai 13px
```

---

## 7. Spacing

Spacing dibuat lega agar UI tidak padat.

Standar padding:

```text
Small card       : 16px
Normal card      : 20px sampai 24px
Large section    : 42px sampai 58px
Dashboard page   : 24px sampai 28px
```

Standar gap:

```text
Small gap        : 10px sampai 12px
Normal gap       : 16px sampai 18px
Large gap        : 22px sampai 28px
```

Aturan spacing:

- Form harus memiliki jarak antar input.
- Card layanan tidak boleh terlalu rapat.
- Dashboard statistic card harus mudah dipindai.
- Section home harus memiliki jarak antar blok.
- Mobile layout harus tetap memiliki padding kanan dan kiri.

---

## 8. Border Radius

Sistem menggunakan radius besar agar tampilan lebih modern.

Standar radius:

```text
Input            : 14px sampai 16px
Button           : 16px sampai 999px
Card             : 22px sampai 30px
Hero card        : 28px sampai 34px
Image card       : 18px sampai 24px
```

---

## 9. Shadow

Shadow digunakan secara ringan.

Contoh shadow:

```css
box-shadow: 0 14px 34px rgba(2,132,199,.10);
box-shadow: 0 24px 60px rgba(2,132,199,.15);
```

Hindari:

- Shadow hitam pekat.
- Shadow terlalu tebal.
- Glow berlebihan.
- Efek yang membuat UI terlihat berat.

---

## 10. Layout Utama

Layout utama dibagi menjadi:

1. Public layout.
2. Buyer layout.
3. Admin dashboard layout.
4. Seller dashboard layout.
5. Petugas dashboard layout.
6. Customer service dashboard layout.

### Public dan Buyer Layout

Digunakan pada:

```text
src/views/public/home.php
src/views/buyer/create-order.php
src/views/buyer/orders.php
src/views/buyer/notifications.php
src/views/buyer/complaints.php
```

Komponen utama:

- Navbar.
- Hero section.
- Form reservasi.
- Service card.
- Mitra card.
- CTA section.

### Dashboard Layout

Digunakan pada:

```text
src/views/admin/
src/views/seller/
src/views/customer_service/
```

Komponen utama:

- Sidebar.
- Topbar.
- Main content.
- Statistic card.
- Data card.
- Form card.
- Detail section.

---

## 11. Navbar

Navbar digunakan pada halaman public dan buyer.

File:

```text
src/views/layouts/buyer-navbar.php
```

Menu utama:

```text
Home
Order Laundry
Status Cucian
Notifikasi
Pelanggan Laundry
Logout
```

Aturan navbar:

- Sticky di bagian atas.
- Background putih atau soft blue.
- Logo jelas.
- Menu tidak terlalu padat.
- Mobile friendly.
- Link aktif mudah dikenali.

---

## 12. Sidebar

Sidebar digunakan pada admin, seller, petugas, dan customer service.

File sidebar:

```text
src/views/layouts/admin-sidebar.php
src/views/layouts/seller-sidebar.php
src/views/layouts/customer-service-sidebar.php
```

Aturan sidebar:

- Lebar tidak terlalu besar.
- Warna biru atau gradient biru.
- Menu memakai teks singkat.
- Active state harus terlihat.
- Mobile overlay tersedia.
- Tombol logout mudah ditemukan.

Menu admin:

```text
Dashboard
Pengguna
Seller
Petugas
Layanan
Pesanan
Keluhan
```

Menu seller:

```text
Dashboard
Pesanan
Layanan
Petugas
```

Menu petugas:

```text
Dashboard
Pesanan
Tugas
```

Menu customer service:

```text
Dashboard
Keluhan Pelanggan
```

---

## 13. Topbar

Topbar digunakan pada halaman dashboard.

File topbar:

```text
src/views/layouts/admin-topbar.php
src/views/layouts/seller-topbar.php
src/views/layouts/customer-service-topbar.php
```

Isi topbar:

- Tombol sidebar untuk mobile.
- Judul panel.
- Subtitle panel.
- Jam realtime.
- Badge profil user.

Aturan topbar:

- Tidak terlalu tinggi.
- Teks jelas.
- Jam realtime tidak mengganggu.
- Profil dibuat sederhana.

---

## 14. Button

### Primary Button

Digunakan untuk aksi utama.

Contoh:

```text
Buat Pesanan
Simpan
Pilih Layanan
Login
Kirim Balasan
Ambil Tugas
```

Style:

```css
background: linear-gradient(135deg, #0284c7, #0ea5e9);
color: white;
border-radius: 999px;
font-weight: 900;
```

### Outline Button

Digunakan untuk aksi sekunder.

Contoh:

```text
Lihat Pesanan
Kembali
Detail
Tandai Selesai
```

Style:

```css
border: 1px solid #7dd3fc;
color: #0369a1;
background: white;
border-radius: 999px;
```

### Danger Button

Digunakan untuk aksi berisiko.

Contoh:

```text
Nonaktifkan
Hapus
Blokir
```

Style:

```css
background: #dc2626;
color: white;
border-radius: 16px;
```

---

## 15. Form

Form digunakan pada:

```text
Login
Register
Reservasi cepat
Buat pesanan
Tambah seller
Tambah layanan
Tambah petugas
Update pesanan
Update tugas
Keluhan pelanggan
Balasan customer service
```

Aturan form:

- Label harus jelas.
- Input memiliki radius.
- Spacing antar field konsisten.
- Field wajib diberi required.
- Error message harus terlihat.
- Form besar dibagi menjadi grid 2 kolom pada desktop.
- Form berubah 1 kolom pada mobile.

Style input:

```css
border: 1px solid #bae6fd;
background: #f8fdff;
border-radius: 16px;
padding: 13px 14px;
```

Focus state:

```css
border-color: #0284c7;
box-shadow: 0 0 0 4px rgba(14,165,233,.14);
background: white;
```

---

## 16. Card

Card menjadi komponen utama sistem.

Jenis card:

1. Service card.
2. Mitra card.
3. Dashboard statistic card.
4. Order card.
5. Task card.
6. Complaint card.
7. Form card.
8. Notification card.

Aturan card:

- Background putih.
- Border biru muda.
- Radius besar.
- Shadow ringan.
- Padding cukup.
- Hover ringan untuk card layanan dan mitra.
- Tidak terlalu banyak teks dalam satu card.

---

## 17. Service Card

Service card digunakan pada halaman home untuk menampilkan layanan laundry.

Isi service card:

1. Gambar layanan.
2. Nama layanan.
3. Deskripsi layanan.
4. Harga.
5. Satuan.
6. Estimasi.
7. Tombol pilih layanan.

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

Aturan gambar:

- Gunakan gambar lokal jika memungkinkan.
- Simpan di folder `src/assets/img/services/`.
- Ukuran gambar tidak terlalu besar.
- Object fit cover.
- Sesuaikan gambar dengan nama layanan.

Contoh nama gambar lokal:

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

---

## 18. Mitra Card

Mitra card digunakan untuk menampilkan seller laundry aktif.

Isi mitra card:

1. Gambar laundry.
2. Inisial mitra.
3. Nama mitra.
4. Kota atau alamat.
5. Nomor telepon.

Aturan mitra card:

- Gambar tidak boleh kosong.
- Informasi ringkas.
- Card tidak terlalu tinggi.
- Muncul di bawah section layanan pada halaman home.

---

## 19. Dashboard Statistic Card

Statistic card digunakan pada dashboard admin, seller, petugas, dan customer service.

Isi statistic card:

1. Label data.
2. Total data.
3. Warna status.
4. Icon optional.

Contoh data admin:

```text
Total pengguna
Total seller
Total petugas
Total pesanan
Total keluhan
Pendapatan
```

Contoh data seller:

```text
Pesanan
Layanan
Layanan aktif
Petugas
Pendapatan
```

Contoh data petugas:

```text
Pesanan seller
Tugas menunggu
Tugas saya
Tugas selesai
```

Contoh data customer service:

```text
Total keluhan
Menunggu
Diproses
Selesai
```

---

## 20. Badge Status

Badge digunakan untuk status pesanan, pembayaran, tugas, dan keluhan.

### Status Pesanan

```text
diproses
dicuci
selesai
diambil
```

### Status Pembayaran

```text
unpaid
waiting_confirmation
paid
cancelled
```

### Status Tugas

```text
waiting
assigned
on_process
completed
cancelled
```

### Status Keluhan

```text
pending
process
done
```

Aturan badge:

- Warna harus konsisten.
- Teks singkat.
- Radius penuh.
- Mudah dibaca.

---

## 21. Halaman Home

Susunan halaman home:

1. Hero section.
2. Form reservasi cepat di kanan.
3. Section layanan laundry.
4. Section mitra laundry.
5. CTA biru di bagian bawah.

Aturan home:

- Reservasi cepat harus terlihat di area hero.
- Layanan tampil sebelum mitra.
- Mitra tampil di bawah layanan.
- CTA biru tetap berada paling bawah.
- Layanan memakai gambar sesuai jenis cucian.
- Tampilan desktop dan mobile harus rapi.

---

## 22. Responsive Design

Aturan responsive:

- Desktop menggunakan grid 3 sampai 4 kolom.
- Tablet menggunakan grid 2 sampai 3 kolom.
- Mobile menggunakan 1 kolom.
- Sidebar berubah menjadi overlay pada mobile.
- Form dua kolom berubah menjadi satu kolom.
- Card tidak boleh melebihi lebar layar.
- Tombol harus mudah ditekan pada mobile.

Breakpoint:

```text
Mobile    : < 650px
Tablet    : 650px sampai 960px
Desktop   : > 960px
```

---

## 23. Checklist Final Desain

Checklist final:

- [ ] Home tampil rapi.
- [ ] Reservasi cepat terlihat jelas.
- [ ] Layanan tampil sebelum mitra.
- [ ] Gambar layanan sesuai.
- [ ] Mitra memiliki gambar.
- [ ] CTA biru tetap di bawah.
- [ ] Navbar konsisten.
- [ ] Sidebar konsisten.
- [ ] Topbar konsisten.
- [ ] Button konsisten.
- [ ] Form rapi.
- [ ] Card tidak terlalu padat.
- [ ] Dashboard mudah dibaca.
- [ ] Status badge jelas.
- [ ] Mobile responsive.
- [ ] Tidak ada istilah marketplace lama.
- [ ] Tidak ada istilah cart, produk, checkout produk, courier, shipping, resi, review produk, dan stok produk.

---

## 24. Tujuan Akhir UI

UI akhir harus terasa seperti aplikasi layanan laundry UMKM yang modern, bersih, responsif, dan mudah digunakan.

Target akhir:

```text
Clean Laundry Platform
Modern Service Dashboard
Simple Customer Ordering Flow
Responsive Multi-role System
Professional UMKM Web Application
```