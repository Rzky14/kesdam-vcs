# **Manajemen Penjadwalan dan Surat Menyurat KESDAM III/Siliwangi**

## **Project Overview**

### **Vision Statement**

Menjadi penyelenggara kesehatan yang dipercaya, memastikan kesiapan dan ketahanan personel militer, serta terus berupaya meningkatkan efisiensi kerja melalui pemanfaatan teknologi informasi.

### **Problem Statement**

Proses administrasi dan koordinasi di KESDAM III/Siliwangi saat ini tidak efisien dan masih manual, yang menyebabkan berbagai masalah:

* Pencatatan jadwal (dukkes, jaga, satuan) dan surat menyurat masih menggunakan buku agenda.  
* Proses pencarian data dan arsip memakan waktu lama serta rentan terhadap kesalahan manusia (human error).  
* Sulit untuk melacak riwayat perubahan atau koreksi pada dokumen penting.  
* Alur persetujuan surat tidak terintegrasi, lambat, dan tidak transparan.  
* Kurangnya sistem pencadangan (backup) dan keamanan data yang terstruktur untuk dokumen sensitif (misalnya, surat rahasia).

### **Solution**

Membangun sebuah aplikasi web terpusat untuk mendigitalisasi dan mengotomatisasi proses administrasi KESDAM, yang akan:

* Menyediakan modul manajemen penjadwalan digital.  
* Mengelola siklus hidup surat masuk dan surat keluar secara terpusat.  
* Menerapkan alur persetujuan dokumen (termasuk koreksi dan unggah TTD manual).  
* Menyediakan fitur pelacakan (audit trail) untuk semua perubahan data.  
* Menghasilkan laporan bulanan secara otomatis dan menyediakan arsip digital yang aman dan mudah dicari.

### **Target Audience**

Fokus utama: Pengguna internal di lingkungan KESDAM III/Siliwangi, yang terbagi menjadi:

* **Pimpinan/Pejabat Tinggi:** Pengambil keputusan dan otoritas persetujuan akhir.  
* **Administrator Operasional (Kasi, Kaur):** Pengawas alur kerja dan verifikator tingkat menengah.  
* **Pengelola Data Harian (Batih, Staf Umum):** Pengguna operasional utama yang melakukan input data jadwal dan surat.  
* **Administrator Teknis (Admin Sistem):** Pengelola akun pengguna, peran, dan teknis sistem.

### **Success Metrics**

1. **Peningkatan Efisiensi:**  
   * Waktu input data untuk jadwal atau surat: \< 2 menit per formulir.  
   * Waktu pembuatan laporan bulanan berkurang dari hitungan hari menjadi hitungan menit.  
   * Waktu pencarian arsip digital: \< 30 detik.  
2. **Adopsi Pengguna:**  
   * 90% proses penjadwalan dan surat menyurat beralih dari manual ke sistem dalam 3 bulan setelah peluncuran.  
   * Pengelola Data Harian (Batih, Staf) login dan menggunakan sistem setiap hari.  
3. **Kualitas & Kinerja Produk:**  
   * Ketersediaan sistem (Uptime): Target 24/7.  
   * Keamanan data: Tidak ada kebocoran data (terutama surat rahasia).  
   * Akurasi data: Penurunan kesalahan administrasi akibat proses manual.

### **Project Scope \- Version 1.0**

Rilisan awal akan berfokus pada fitur inti berikut:

1. Manajemen Akun Pengguna dan Hak Akses (RBAC).  
2. Manajemen Penjadwalan (Jadwal Dukkes, Jaga, Kegiatan Satuan).  
3. Manajemen Surat Menyurat (Masuk & Keluar, klasifikasi Biasa/Rahasia/Telegram).  
4. Alur Persetujuan Dokumen (termasuk fitur koreksi dan unggah TTD manual).  
5. Notifikasi dan Pengingat (di dalam aplikasi).  
6. Pelaporan & Arsip Bulanan.  
7. Pencadangan Data Otomatis.

### **Risk Assessment**

1. **Technical Risks:**  
   * Keamanan data sensitif (enkripsi surat rahasia harus kuat).  
   * Kinerja sistem (kecepatan query untuk pencarian dan pelaporan).  
   * Keandalan (proses backup otomatis harus berjalan sempurna).  
2. **User Experience / Adoption Risks:**  
   * Resistensi terhadap perubahan dari staf yang terbiasa dengan proses manual.  
   * Kesulitan adopsi: Antarmuka (UI) harus sangat mudah digunakan (usability) oleh staf non-teknis.  
3. **Operational Risks:**  
   * Data yang dimasukkan tidak akurat atau tidak lengkap.  
   * Alur kerja digital tidak sepenuhnya mencerminkan prosedur operasional militer yang ada.

### **Success Criteria**

Proyek ini akan dianggap berhasil jika:

1. **Implementasi Teknis:**  
   * Seluruh kebutuhan fungsional (Bab III SRS) terimplementasi dan berfungsi.  
   * Sistem aman, andal, dan memenuhi target kinerja (Bab IV SRS).  
2. **Adopsi Pengguna:**  
   * Seluruh segmen pengguna (Pimpinan, Kasi/Kaur, Batih/Staf) secara aktif menggunakan sistem sebagai pengganti penuh proses manual.  
3. **Tujuan Bisnis (Operasional):**  
   * Tercapainya tujuan utama: otomatisasi, efisiensi kerja, transparansi, akurasi data, dan keamanan informasi di lingkungan KESDAM III/Siliwangi.