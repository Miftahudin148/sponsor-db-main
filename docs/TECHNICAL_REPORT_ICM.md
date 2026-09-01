# Laporan Teknis: Sistem Manajemen Sponsorship ICM

**Versi:** 1.0 | **Tanggal:** 17 September 2026 | **Dibuat untuk:** Direksi ICM  
**Bahasa:** Indonesia | **Format:** 4 halaman Word (.docx)

---

## 1. Ringkasan Eksekutif

Sistem ICM Sponsor adalah *digital archive* modern yang menggantikan pengelolaan data sponsor dan kontak PIC lewat file Excel yang sering bertumpuk dan sulut diperbarui. Sistem ini menyediakan satu pusat data terpusat yang bisa diakses melalui halaman web `/admin` (wajib login), memunginkan tim manajemen sponsor untuk:

*   Mencari kontak PIC perusahaan sponsor dalam hitungan detik
*   Melihat histori perubahan data lengkap siapa yang mengubah apa dan kapan
*   Mengexport laporan ke Excel hanya dengan 1 klik
*   Mengimpor data bulk dari file Excel lama tanpa rusak format

Manfaat utama bagi ICM: waktu pencarian data berkurang drastis, akurasi data ditingkatkan otomatis, dan keputusan strategis didukung oleh laporan real-time.

---

## 2. Gambaran Sistem

Sistem ini dibangun pada platform web modern (seperti aplikasi terkini yang bisa dibuka di komputer atau HP). Halaman utama setelah login adalah **Dashboard**, yang menampakkan 6 kartu angka penting dan 2 grafik batang yang menggambarkan kondisi data sponsor ICM.

Terdapat navigasi kiri dengan menu:
*   **Kontak** — daftar semua kontak PIC dan perusahaan
*   **Perusahaan** — daftar perusahaan sponsor
*   **Kegiatan** — daftar acara/even sponsor pernah diadakan
*   **Kategori** — klasifikasi jenis acara (misal: Gizi Klinik, Pelatihan, dll)
*   **Histori** — catatan siapa yang mengubah data dan kapan
*   **Akun Saya** — profil dan pengaturan pengguna sendiri

Akses terbagi antara **Admin** (bisa semua fitur termasuk kelola akun) dan **Karyawan** (bisa lihat dan edit profil sendiri, tidak bisa kelola akun lain).

---

## 3. Fitur Utama (Cerita, bukan Spesifikasi Teknis)

### 3.1 Dashboard — Ringkasan Sekilas
Di dashboard terdapat **6 kartu angka** yang menunjukkan kondisi data:
*   **Total Perusahaan** — berapa perusahaan sponsor yang terdaftar
*   **Total Kontak** — berapa PIC yang terdaftar
*   **Terverifikasi** — kontak yang sudah divalidasi nomor HP-nya
*   **Perlu Dicek** — kontak yang butifikasi pengecekan nomor
*   **Tidak Aktif** — kontak yang tidak aktif lagi
*   **Total Kegiatan** — berapa acara sponsor pernah diadakan

Di bawah kartu angka, ada **2 grafik batang**:
*   **Grafik Horizontal:** *Distribusi Event per Kategori* — menampilkan berapa event untuk setiap kategori (Gizi Klinik, Pelatihan, dll). Warna-warna di grafik mengikuti label kategori, jadi bisa dengan cepat melihat kategori mana yang paling banyak dijalani.
*   **Grafik Vertikal:** *Top 5 Event Terbesar* — menampilkan 5 acara yang memiliki paling banyak peserta/ kontak. Berguna untuk mengetahui event mana yang paling sukses atau diminati sponsor.

Semua angka dan grafik ini **otomatis berefresh** sesuai filter yang dipilih, tanpa perlu counting manually.

### 3.2 Manajemen Kontak & Perusahaan
Ini adalah "lemari arsip" utama. Tabel menampilkan daftar kontak PIC dengan informasi:
*   **Nama Perusahaan** — nama standar perusahaan (tidak ada nama aneh atau duplicates)
*   **PIC** — nama kontakt person
*   **No. Telepon** — nomor HP, bisa dikopi langsung
*   **Kegiatan & Kategori** — ditandai warna sesuai jenis acara
*   **Status** — warna hijau (terverifikasi), kuning (perlu dicek), merah (tidak aktif)

**Fungsi utama:**
*   **Cari cepat:** Ketik nama perusahaan, nama PIC, atau nomor HP di kolom pencarian — sistem akan menampilkan hasil yang sesuai dalam milidetik.
*   **Filter:** Filter berdasarkan kategori kegiatan, status verifikasi, atau event tertentu.
*   **Tombol WhatsApp:** Di setiap baris ada tautan Kirim WhatsApp (`https://wa.me/...`) untuk menghubungi PIC langsung.
*   **Sorot duplikat:** Jika ada nomor HP yang sama terdaftar di perusahaan yang berbeda, baris tersebut akan ditandai warnanya merah muda sebagai peringatan.

**Ekspor Laporan:**
Tombol **Ekspor CSV** di pojok kanan tabel. Saat diklik, browser akan langsung mendownload file Excel (.csv) yang berisi data yang sedang ditampilkan (termasuk filter yang dipilih). File Excel siap pakai untuk dibuka di Microsoft Excel atau LibreOffice, sudah dilengkapi **BOM UTF-8** agar tampilan nama tidak rusak (tidak jadi tanda tanya).

### 3.3 Import Excel Pintar
Ketika ada data sponsor baru dari file Excel yang sudah lama, tidak perlu memasukkan satu per satu. Caranya:
1.  **Upload file** (format `.xlsx`, `.xls`, `.ods`, `.csv`, `.tsv`, maksimal 10MB).
2.  **Sistem akan membaca otomatis** header file (bahkan kalau nama kolom agak berbeda), baca semua sheet, dan menampilkan *preview* 5 baris pertama beserta **jumlah baris** yang terdeteksi.
3.  **Sistem akan melakukan pengecekan otomatis:**
    *   Apakah nama perusahaan sudah ada? (tandai *cocok* atau *baru*)
    *   Apakah nomor HP sudah format benar `628...`? (tandai *tidak valid* jika belum)
    *   Apakah ada kata kunci *junk* (misal: *daftar, survey, spam*)? (ditinggal)
4.  **Bisa diedit per baris** — sebelum disimpan, bisa mengubah status *cocok* menjadi *baru* atau mengubah kategori event.
5.  **Simpan sekali klik** — sistem akan menyimpan data ke database, buat perusahaan baru jika belum ada, dan tandai status kontak.

Fitur ini efisien untuk mengimpor ribuhan data sekaligus tanpa khawatir data salah atau duplicate.

### 3.4 Histori Aktivitas & Akun
Setiap kali ada data yang ditambah, diubah, atau dihapus, sistem akan mencatatnya otomatis dalam menu **Histori**. Tampilan menunjukkan:
*   **Waktu** — kapan perubahan terjadi
*   **Pelaku** — siapa yang melakukan perubahan (nama pengguna)
*   **Aksi** — apakah *ditambahkan*, *diperbarui*, atau *dihapus*
*   **Data yang diubah** — daftar field yang berubah, *sebelum* dan *sesudah* (dengan warna hijau/merah untuk perbedaan)
*   **Keterangan** — catatan tambahan jika ada

**Privasi:**
*   **Admin** bisa melihat histori **semua** aktivitas di seluruh sistem.
*   **Karyawan** hanya bisa melihat histori perubahan **milik diri sendiri** (tidak bisa melihat siapa lain yang mengubah data).

### 3.5 Akun Saya (Profil & Pengguna)
Setiap pengguna memiliki halaman **Profil Saya** yang bisa diakses dari menu kanan atas (ikon avatar) atau dari sidebar kiri -> *Akun Saya*.

**Fitur profil:**
*   **Foto Profil** — bisa diupload foto baru. Sistem akan memotong foto menjadi bulat (crop 1:1) dan diubah lebar 400px sebelum disimpan. Jika foto tidak bisa ditampilkan (alias broken), akan muncul **inisial huruf dua** dari nama pengguna (misal: "BS") di lingkaran warna biru.
*   **Data Diri** — NIP, Divisi, nomor telepon dapat diubah.
*   **Ganti Sandi** — bisa mengganti password lama dengan password baru (harus memasukkan password lama terlebih dahulu untuk keamanan).

**Keamanan akun:**
*   Setiap pengguna wajib login dengan akun sendiri.
*   Setiap akun memiliki peran: **Admin** (atasan, bisa akses semua menu termasuk Manajemen Akun) atau **Karyawan** (hanya bisa lihat menu Kontak, Kegiatan, Profil Saya).
*   Nomor HP dan data harus dalam format valid (`628...`), sistem akan menolak (tidak disimpan) jika format salah.

---

## 4. Keamanan & Kepercayaan Data

Sistem ini dirancang dengan beberapa lapis keamanan sehingga data sponsor ICM tetap aman:

1.  **Login wajib** — Tidak ada yang bisa melihat data tanpa akun dan password.
2.  **Peran (Role)** — Admin bisa mengelola seluruh data dan akun karyawan. Karyawan hanya bisa lihat dan edit data milik diri sendiri.
3.  **Riwayat lengkap (Audit Trail)** — Setiap perubahan data dicatat siapa, kapan, dan apa yang diubah. Ini penting untuk kepatuhan internally dan jika ada inspeksi.
4.  **Retensi data 365 hari** — Riwayat aktivitas disimpan selama 1 tahun, lalu akan dihapus secara otomatis.
5.  **Validasi data** — Sistem otomatis memastikan nomor HP ada awalan `628` dan jumlah digit benar. Jika tidak, data tidak akan disimpan dan akan ditandai *perlu dicek*.

---

## 5. Alur Kerja Sehari-hari (Skenario Contoh)

**Skenario: Tim Marketing ingin mengirim undangan kegiatan baru ke seluruh perusahaan sponsor.**

1.  Buka halaman `/admin`, login dengan akun karyawan.
2.  Di menu **Kontak**, gunakan kolom **Cari** ketik "Kalbe" atau pilih filter **Kategori** -> *Pelatihan*.
3.  Lihat daftar kontak yang muncul, pasti sesuai kriteria.
4.  Klik tombol **Ekspor CSV** -> browser akan mengunduh file Excel berisi daftar nama PIC, perusahaan, dan nomor HP yang sudah difilter.
5.  File Excel tersebut langsung dikirim ke tim finance atau digunakan untuk pembuatan undangan.
6.  Jika ada kesalahan data, bisa dikembalikan ke menu **Import** dan diperbaiki.

**Skenario: Admin ingin menambah karyawan baru.**

1.  Masuk menu **Manajemen Akun** (hanya admin yang bisa lihat).
2.  Klik **Tambah User**, isi nama, email, peran (admin/karyawan), NIP, divisi.
3.  Karyawan baru akan menerima notifikasi untuk login dan mengatur profil serta password pertamanya.
4.  Semua aktivitas karyawan akan tercatat di menu **Histori** dengan nama pengguna karyawan tersebut.

---

## 6. Hosting & Perawatan (Singkat, untuk IT/ICM)

Sistem ini butuh lingkungan pendukung standar yang umum:

*   **Server/Hosting** — bisa di-VPS, shared hosting dengan support PHP 8.2 dan database SQLite/MySQL.
*   **Database** — file database (SQLite) atau tabel MySQL/SQL Server, disarankan backup harian.
*   **Domain** — nama domain seperti `sponsor.icm.or.id` atau akses melalui `icm-admin.domain.com`.
*   **Update rutin** — sebulan sekali atau sesuai notifikasi dari developer untuk *security patch* dan fitur baru.
*   **Laporan rutin** — Direksi dapat meminta laporan bulanan atau kwartal dari dashboard atau ekspor CSV.

---

## 7. Kesimpulan

Sistem ICM Sponsor menyediakan solusi end-to-end untuk mengelola data sponsor dan kontak PIC dengan efisien, akurat, dan terlindungi. Dengan dashboard yang jelas, fitur import/export Excel yang pintar, dan histori aktivitas yang lengkap, tim manajemen dapat fokus pada strategi sponsorrather than repot-memindai file Excel. Keamanan yang ketat menjamin hanya orang berhak yang bisa mengakses dan mengubah data.

---

## 8. Lampiran

**Glosarium Singkat:**
*   **Kontak** — PIC (Penanggung Jawab) dari perusahaan sponsor.
*   **Perusahaan** — Nama organisasi sponsor.
*   **Kegiatan** — Acara/event sponsorship yang telah dilakukan.
*   **Kategori** — Klasifikasi jenis kegiatan (Gizi Klinik, Pelatihan, dsb).
*   **Verifikasi** — Proses pengecekan validitas nomor HP.
*   **CSV** — Format file Excel sederhana yang bisa dibuka di Microsoft Excel/LibreOffice.

**Sumber data:** Database ICM Sponsor, diuji melalui panel administrator (`/admin`) pada tanggal 17 September 2026.

---
*Laporan ini dibuat untuk kepentingan internal Direksi ICM. Isi dan angka dapat diperbarui sesuai kondisi data terbaru.*