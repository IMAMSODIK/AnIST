# Panduan Penggunaan Aplikasi — KPI Advisor

> **AI-Based OMTI KPI Monitoring & Evidence Validation System**
> Dokumentasi penggunaan aplikasi (end-user & admin)

---

## Daftar Isi

1. [Pengantar](#1-pengantar)
2. [Tentang Sistem](#2-tentang-sistem)
3. [Persyaratan Sistem](#3-persyaratan-sistem)
4. [Instalasi & Konfigurasi Awal](#4-instalasi--konfigurasi-awal)
5. [Menjalankan Aplikasi](#5-menjalankan-aplikasi)
6. [Akun Default & Login](#6-akun-default--login)
7. [Tampilan Antarmuka (Sidebar & Topbar)](#7-tampilan-antarmuka-sidebar--topbar)
8. [Dashboard](#8-dashboard)
9. [KPI Monitoring](#9-kpi-monitoring)
10. [Measurements (Master Data KPI)](#10-measurements-master-data-kpi)
11. [Targets (Target KPI per Quarter)](#11-targets-target-kpi-per-quarter)
12. [Initiatives (Master Inisiatif Strategis)](#12-initiatives-master-inisiatif-strategis)
13. [Upload Evidence](#13-upload-evidence)
14. [AI Analysis (Hasil Gemini)](#14-ai-analysis-hasil-gemini)
15. [Reports (Laporan KPI)](#15-reports-laporan-kpi)
16. [Audit Trail (Jejak Aktivitas)](#16-audit-trail-jejak-aktivitas)
17. [Settings (Pengaturan)](#17-settings-pengaturan)
18. [Alur Kerja Utama (End-to-End)](#18-alur-kerja-utama-end-to-end)
19. [Cara Kerja Integrasi Google Gemini](#19-cara-kerja-integrasi-google-gemini)
20. [Mekanisme Perhitungan KPI & Score](#20-mekanisme-perhitungan-kpi--score)
21. [FAQ & Troubleshooting](#21-faq--troubleshooting)

---

## 1. Pengantar

**KPI Advisor** adalah aplikasi *AI-Assisted KPI Monitoring System* yang memanfaatkan **Google Gemini API** untuk membaca dokumen bukti (evidence) dan menentukan **nilai realisasi** KPI secara otomatis.

Tujuan utama sistem:

- **Menghilangkan input manual** nilai realisasi KPI.
- User cukup mengunggah *evidence*, AI (Gemini) yang membaca & memvalidasinya.
- Perhitungan Score & Formula KPI tetap dilakukan sepenuhnya oleh **Laravel** sehingga seluruh proses dapat diaudit.

> **Prinsip inti:** *Nilai realisasi hanya berasal dari hasil AI. Laravel adalah source of truth. Google Gemini hanya berfungsi sebagai AI Analyzer.*

---

## 2. Tentang Sistem

### Peran masing-masing komponen

| Komponen | Tanggung Jawab |
|---|---|
| **Laravel** | Authentication, Authorization, Master Data, Upload Evidence, Validasi hasil AI, Formula & Perhitungan KPI, Reporting, Audit Trail, Logging. **(Source of truth)** |
| **Google Gemini** | Membaca evidence, memahami isi, memvalidasi evidence, *matching* initiative, menentukan nilai realisasi, membuat analisis & rekomendasi. |

### Gemini **TIDAK PERNAH**:

- Menghitung nilai/score KPI
- Menentukan Formula
- Mengubah Target / Initiative

### Teknologi

- **Backend:** Laravel 12, PHP 8.2+, MySQL
- **Frontend:** Blade, Tailwind CSS v4, Alpine.js, Chart.js, Heroicons, Vite 7
- **AI:** Google Gemini API (model default `gemini-2.0-flash`)
- **Queue:** berbasis database (`database` driver)
- **File yang didukung:** PDF, DOCX, XLSX, JPG, JPEG, PNG (maks. 10 MB)

---

## 3. Persyaratan Sistem

Pastikan environment berikut sudah terpasang sebelum instalasi:

- **PHP** 8.2 atau lebih baru (dengan ekstensi: pdo_mysql, mbstring, openssl, tokenizer, xml, ctype, json, fileinfo, gd/curl untuk HTTP ke Gemini)
- **Composer** (dependency manager PHP)
- **MySQL** / MariaDB
- **Node.js** 18+ dan **npm**
- **Google Gemini API Key** — dapatkan di [Google AI Studio](https://aistudio.google.com/app/apikey)
- Web server opsional: Apache/Nginx **atau** cukup gunakan `php artisan serve` (Laravel bawaan)
- (Direkomendasikan) **Laragon** untuk Windows

---

## 4. Instalasi & Konfigurasi Awal

### Langkah 1 — Salin & siapkan file environment

Salin berkas konfigurasi environment:

```bash
cp .env.example .env
```

Lalu edit `.env` dan sesuaikan bagian berikut:

```dotenv
APP_NAME="KPI Advisor"
APP_URL=http://localhost       # sesuaikan bila pakai port/vhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=kpi_advisor        # buat database ini di MySQL terlebih dahulu
DB_USERNAME=root
DB_PASSWORD=

# Driver (disarankan tetap "database" — sudah didukung tabel-nya)
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
FILESYSTEM_DISK=local

# KUNCI INTEGRASI AI
GEMINI_API_KEY=                # isi dengan API Key dari Google AI Studio
GEMINI_MODEL=gemini-2.0-flash  # bisa diganti, mis. gemini-1.5-pro
```

> **Penting:** `GEMINI_API_KEY` wajib diisi. Tanpa kunci ini, seluruh analisis AI akan gagal.
> Kunci juga dapat diatur / diganti kapan saja melalui menu **Settings** (lihat §17).

### Langkah 2 — Instal dependensi

```bash
composer install
npm install
```

### Langkah 3 — Generate application key

```bash
php artisan key:generate
```

### Langkah 4 — Jalankan migrasi & seeder (master data)

```bash
php artisan migrate --seed
```

Seeder akan membuat:

- 2 akun default (admin & demo) — lihat §6.
- **11 Measurement** lintas 4 perspektif Balanced Scorecard.
- Target KPI per-quarter (Q1–Q4) untuk tahun berjalan.
- 33 Initiative strategis (3–4 per measurement).

### Langkah 5 — Build asset frontend

```bash
npm run build      # untuk produksi
# atau
npm run dev        # untuk pengembangan (hot-reload)
```

---

## 5. Menjalankan Aplikasi

Aplikasi **butuh 3 proses yang berjalan bersamaan** agar semua fitur (terutama analisis AI) bekerja:

```bash
php artisan serve      # (1) Web server
z # (2) Worker antrian — WAJIB agar upload diproses AI
npm run dev            # (3) Asset Vite (saat development)
```

> **Sangat penting:** Worker queue **wajib berjalan**. Saat Anda mengunggah evidence, pekerjaan analisis AI dimasukkan ke antrean `evidence` dan baru benar-benar dijalankan oleh worker. Jika worker tidak aktif, file akan tertahan berstatus **pending**.
>
> Alternatif praktis: jalankan `composer dev` yang sudah menjalankan `serve`, `queue:listen`, log viewer (pail), dan `vite` sekaligus.

Akses aplikasi pada alamat yang ditampilkan (biasanya `http://127.0.0.1:8000` atau sesuai virtual host Laragon Anda, mis. `http://kpi_advisor.test`).

---

## 6. Akun Default & Login

Setelah `--seed`, tersedia dua akun siap pakai:

| Email | Nama | Password |
|---|---|---|
| `admin@kpiadvisor.com` | Administrator | `password` |
| `demo@kpiadvisor.com` | Demo User | `password` |

> 🔒 **Keamanan:** Segera ganti password setelah login pertama melalui menu **Settings → Password**. Pada tahap pengembangan ini **tidak tersedia** alur *lupa password / reset password via email*, jaga baik-baik akun Anda.

### Login

1. Buka aplikasi, Anda akan diarahkan ke halaman **Login**.
2. Masukkan email & password, centang **Remember me** bila ingin tetap login.
3. Klik **Sign In** → diarahkan ke Dashboard.

### Registrasi (akun baru)

Halaman **Register** tersedia untuk membuat akun baru (validasi: nama, email unik, password min. 8 karakter + konfirmasi). Setelah daftar, akun otomatis login. Setiap pendaftaran tercatat di Audit Trail.

### Logout

Klik avatar di pojok kanan atas → **Sign Out**.

---

## 7. Tampilan Antarmuka (Sidebar & Topbar)

Antarmuka menggunakan desain *Executive Dashboard* modern: card-based, soft shadow, rounded-xl, palet Indigo (#4F46E5) sebagai warna utama, dan mendukung **Dark Mode**.

### Sidebar (dapat di-collapse)

Menu utama (urutan dari atas ke bawah):

1. **Dashboard**
2. **KPI Monitoring**
3. **Measurements**
4. **Targets**
5. **Initiatives**
6. **Upload Evidence**
7. **AI Analysis**
8. **Reports**
9. **Audit Trail**
10. **Settings**

Tombol di bawah sidebar untuk **melebarkan/menyempitkan** (64px ↔ 256px).

### Topbar (atas)

Dari kiri ke kanan:

- **Judul halaman** sesuai konteks.
- **Global Search** — ketik min. 2 huruf untuk mencari Measurement, Initiative, dan Evidence.
- **Status AI** — indikator Gemini *Online / Offline / Error* (titik hijau berdenyut = online).
- **Notifikasi (lonceng)** — ringkasan aktivitas 24 jam terakhir (upload, hasil AI, perhitungan KPI).
- **Toggle Dark Mode.**
- **User Menu** — profil & tombol Sign Out.

---

## 8. Dashboard

Halaman utama setelah login. Menampilkan ringkasan eksekutif performa KPI dan aktivitas AI.

### Widget ringkasan

- **Total KPI**
- **KPI Achieved**
- **KPI On Progress**
- **KPI Below Target**
- **AI Analyses Today**
- **Pending Analysis**
- **Average KPI Score**
- **Evidence Uploaded**

### Chart

- KPI Achievement per Quarter
- KPI Trend
- Perspective Performance
- AI Confidence Distribution
- Upload Activity
- Initiative Progress

### Recent Activity & Quick Actions

- Evidence & AI Analysis terbaru, serta rekomendasi terbaru.
- Tombol pintas: **Upload Evidence**, **Analyze Evidence**, **View Reports**, **Manage KPI**.

Dashboard menerima filter `?year=` dan `?quarter=` untuk melihat periode tertentu.

---

## 9. KPI Monitoring

Tempat memantau capaian KPI per periode (year & quarter).

**Tabel KPI** menampilkan untuk setiap measurement:

- Perspective, Objective, Measurement
- Weight, Unit
- Target
- **Realisasi** (berasal dari hasil AI)
- Achievement (%)
- Score
- **Status** (berwarna): Achieved / On Track / Needs Improvement / Below Target

**Detail per Measurement** (`/kpi-monitoring/{measurement}`): menampilkan tren capaian.

**Recalculate:** tombol **Recalculate** memicu perhitungan ulang Score via queue (job `CalculateKPIJob`). Tambahkan `?sync=1` bila ingin memaksa perhitungan sinkron.

---

## 10. Measurements (Master Data KPI)

Kelola definisi KPI (sumber data bagi seluruh sistem). Setiap measurement mewakili satu KPI yang dipantau.

**Field:**

| Field | Keterangan |
|---|---|
| Perspective | Salah satu: Financial, Customer, Internal Process, Learning & Growth |
| Objective | Tujuan strategis |
| Measurement | Nama KPI |
| Definition | Penjelasan/definisi KPI (digunakan AI memahami konteks) |
| Formula | Tipe perhitungan: **Higher is Better** / **Lower is Better** / **Exact Target** |
| Unit | Satuan (%, index, dll.) |
| Weight | Bobot KPI (memengaruhi skor keseluruhan) |

**Operasi:** Create / Read / Update / Delete, dengan filter berdasarkan **perspective** dan **pencarian** nama.

> ⚠️ Definition & Formula sangat penting karena **ikut membentuk prompt AI** dan menentukan cara skor dihitung.

Master data awal (11 measurement) dibuat oleh seeder dan dapat Anda ubah/tambah sesuai kebutuhan perusahaan.

---

## 11. Targets (Target KPI per Quarter)

Setiap measurement memiliki target per **tahun** dan **quarter** (Q1–Q4).

**Field:** `measurement`, `year`, `quarter`, `target` (angka).

**Aturan unik:** satu kombinasi *measurement + year + quarter* hanya boleh ada satu target.

**Filter:** measurement, year, quarter.
**Operasi:** Create / Edit / Delete (tanpa halaman *show* terpisah).

---

## 12. Initiatives (Master Inisiatif Strategis)

Daftar inisiatif yang menjadi *rujukan matching* oleh AI terhadap evidence yang diunggah.

**Field:** `measurement`, `initiative` (teks deskripsi inisiatif).

**Tujuan:** saat AI menganalisis evidence, AI akan mencocokkan dengan salah satu initiative yang tersedia di sini dan menampilkan nama initiative beserta tingkat *confidence*-nya.

**Filter:** measurement, pencarian.
**Operasi:** Create / Edit / Delete.

> Inisiatif hanya boleh ditambahkan melalui master data ini. AI **tidak** menciptakan inisiatif baru, hanya melakukan *matching*.

---

## 13. Upload Evidence

Inti dari sistem: **mengunggah dokumen bukti** yang akan dianalisis AI.

### Cara mengunggah

1. Buka **Upload Evidence → Upload Evidence (Create)**.
2. Pilih **Measurement**, **Year**, dan **Quarter**.
3. Pilih file bukti (format: PDF, DOCX, XLSX, JPG, JPEG, PNG — **maks. 10 MB**).
4. Klik **Upload**.

### Penyimpanan & keamanan

- File disimpan pada disk `local` di `evidence/{year}/{quarter}/{uuid}.{ext}`.
- Nama file asli tetap dicatat pada kolom `file_name`.
- MIME type, ukuran, dan input tervalidasi server-side.

### Status pemrosesan

| Status | Arti |
|---|---|
| `pending` | Upload berhasil, menunggu dianalisis (worker queue) |
| `processing` | Sedang dianalisis oleh Gemini |
| `completed` | Analisis selesai, realisasi & skor sudah dihitung |
| `failed` | Analisis gagal (lihat pesan error pada detail AI Analysis) |

### Aksi tambahan

- **Retry** — menjadwalkan ulang analisis bila sebelumnya gagal.
- **Batch Process** — memproses banyak evidence yang tertahan.
- **Delete** — menghapus upload (tercatat di Audit Trail).

> 🔁 Jika status tidak berubah dari `pending`, kemungkinan **worker queue tidak berjalan** (lihat §5 & §21).

---

## 14. AI Analysis (Hasil Gemini)

Halaman read-only untuk meninjau seluruh hasil analisis AI.

**Filter:** `evidence_valid` (valid/tidak valid) dan `confidence_min` (ambang batas keyakinan).

**Konten tiap hasil:**

- **Matched Initiative** + **Confidence (%)**
- **Evidence Valid** (true/false) — apakah bukti memenuhi syarat KPI
- **Realisasi** — nilai yang ditetapkan AI (0–1 skala pencapaian)
- **Analysis** — narasi temuan AI
- **Recommendations** — daftar rekomendasi
- **Raw JSON** — respons mentah Gemini (audit trail)
- **Error message / Processing time** — bila ada error & durasi pemrosesan

**Catatan keamanan:** Setiap respons Gemini **divalidasi** oleh Laravel (`ResponseValidator`) sebelum disimpan. Field wajib: `measurement`, `evidence_valid`, `realisasi`, `analysis`. Bila respons tidak valid/bermasalah, sistem menyimpan catatan error **tanpa** menggagalkan proses upload.

---

## 15. Reports (Laporan KPI)

Laporan ringkas performa KPI per periode (year & quarter), **bersifat hanya-lihat**.

**Isi tabel:** perspective, objective, measurement, weight, unit, target, realisasi (dari AI), achievement %, weighted score, dan status.

**Ringkasan tambahan:**

- **Overall KPI Score** (rata-rata pencapaian tertimbang bobot).
- **Perspective Performance** (performa per perspektif BSC).

> 📌 Catatan: saat ini **belum ada fitur ekspor** (PDF/Excel/CSV) maupun cetak/print. Laporan ditampilkan di layar saja. Hitung ulang score dilakukan terpisah dari menu **KPI Monitoring → Recalculate**.

---

## 16. Audit Trail (Jejak Aktivitas)

Pencatatan menyeluruh untuk kebutuhan audit & kepatuhan. Seluruh aktivitas penting dicatat: user, waktu, aksi, IP, dan user-agent.

**Tipe aksi yang tercatat**, antara lain:

- `register` — pendaftaran akun baru
- CRUD Measurement / Target / Initiative
- `upload_evidence`, `upload_delete`
- `request_ai`, `response_ai`, `validate_result`
- `calculate_kpi`
- Perubahan profil / password / Gemini API key

**Filter:** action, user, rentang tanggal (date_from / date_to). Paginasi 20 per halaman.

**Detail:** menampilkan nilai **lama vs baru** (old_values / new_values) untuk perubahan data.

---

## 17. Settings (Pengaturan)

Pengaturan akun & sistem. Terbagi menjadi beberapa bagian:

### a. Profil

Ubah **nama** dan **email**.

### b. Password

Ubah password — **wajib** memasukkan password saat ini (`current_password`) untuk verifikasi.

### c. Gemini API Key

- Menampilkan kunci saat ini dalam bentuk **disamarkan (masked)**.
- Bisa **mengganti kunci** kapan saja tanpa edit file `.env` manual — sistem akan menulis ulang `.env` dan memperbarui konfigurasi runtime.

> 💡 Kunci yang benar & valid memastikan indikator **Status AI** di topbar menyala *Online*.

### d. System Info

Informasi sistem: versi Laravel/PHP, status database, status queue, model Gemini aktif, dan keterangan *storage writable*.

---

## 18. Alur Kerja Utama (End-to-End)

```text
[1] Siapkan Master Data
    Measurements  →  Targets  →  Initiatives
            │
[2] Pastikan Gemini API Key terisi (Settings / .env)
            │
[3] Pastikan QUEUE WORKER berjalan (php artisan queue:work)
            │
[4] Upload Evidence  ───────────────┐
    (pilih measurement/quarter/year + file)
            │                        │
            ▼                        ▼
[5] Laravel menyimpan file, status = pending
            │
            ▼  (queue worker memproses)
[6] PromptManager membuat prompt khusus measurement
            │
            ▼
[7] GeminiService memanggil Google Gemini API
    └─> mengirim file (base64) + prompt
            │
            ▼
[8] Response JSON dari Gemini
            │
            ▼
[9] ResponseValidator memvalidasi & menormalkan
            │
            ▼
[10] Simpan AiResult (raw_json untuk audit)
            │
            ▼
[11] Bila evidence_valid:
     └─> update/insert Realisasi (source = ai)
     └─> KPIService menghitung Score (Formula + Weight)
            │
            ▼
[12] Dashboard / KPI Monitoring / Reports ter-update
```

**Inti:** user **hanya upload**, sisanya dikerjakan sistem. Skor selalu bisa dilacak kembali ke evidence + respons AI yang tersimpan.

---

## 19. Cara Kerja Integrasi Google Gemini

### Konfigurasi

Dari `config/services.php` → key `gemini`:

| Key | Env | Default |
|---|---|---|
| `api_key` | `GEMINI_API_KEY` | — (wajib) |
| `model` | `GEMINI_MODEL` | `gemini-2.0-flash` |
| `base_url` | — | `https://generativelanguage.googleapis.com/v1beta` |
| `timeout` | — | 120 detik |
| `max_retries` | — | 3 |

### Mekanisme

- **Endpoint:** `POST {base_url}/models/{model}:generateContent`
- **Autentikasi:** header `x-goog-api-key` (tidak diletakkan di URL).
- **Payload:** inline data file (base64 + MIME) + teks prompt; `generationConfig` dengan `temperature 0.1` agar output konsisten dan `responseMimeType: application/json`.
- **Retry otomatis:** maks. 3 kali dengan *exponential backoff* bila menerima HTTP 429 atau error 5xx.
- **Fallback parse JSON:** bila respons terbungkus *code fence* ```json ... ```, sistem mengekstraknya otomatis.

### Strategi Prompt

Prompt **tidak ditulis di Controller**, melainkan dirakit oleh `PromptManager`. Setiap measurement memiliki prompt sendiri sesuai kategori:

- Implementasi Sistem
- Cybersecurity
- Payment
- Investment
- AI / Machine Learning
- Enterprise Architecture
- Default (untuk kategori lain)

AI diinstruksikan eksplisit: **hanya menentukan realisasi** (1 = selesai penuh, desimal proporsional untuk parsial, 0 jika tidak valid), **tidak** menghitung skor KPI.

### Format Output Gemini yang Diharapkan

```json
{
  "measurement": "",
  "matched_initiative": { "name": "", "confidence": 95 },
  "evidence_valid": true,
  "realisasi": 1,
  "analysis": "",
  "recommendations": ["", "", ""]
}
```

---

## 20. Mekanisme Perhitungan KPI & Score

Perhitungan dilakukan oleh `KPIService` & `ScoreCalculator` di Laravel.

### a. Achievement (%) — berdasarkan `measurements.formula`

| Formula | Rumus |
|---|---|
| **Higher is Better** | `(realisasi / target) × 100` |
| **Lower is Better** | `100` (+ bonus bila ≤ target); bila melebihi target, dikurangi proporsi selisih (minimal 0) |
| **Exact Target** | `100` bila sama persis; bila tidak `(1 − |selisih|/target) × 100` |

> Achievement **dibatasi maksimal 120%**.

### b. Score (terbobot)

`Score = (band achievement) × weight / 100`, dengan **banding** pencapaian:

| Achievement | Band |
|---|---|
| ≥ 120% | 120 |
| 110–119% | 110 |
| 100–109% | 100 |
| 90–99% | 90 |
| 80–89% | 80 |
| 70–79% | 70 |
| 60–69% | 60 |
| < 60% | 50 |

### c. Status KPI

| Achievement | Status | Warna |
|---|---|---|
| ≥ 100% | **Achieved** | Emerald |
| ≥ 80% | **On Track** | Amber |
| ≥ 60% | **Needs Improvement** | Oranye |
| < 60% | **Below Target** | Rose |

### d. Overall Score

Rata-rata pencapaian **tertimbang bobot** seluruh measurement pada periode terpilih.

---

## 21. FAQ & Troubleshooting

**Q: Setelah upload, kenapa status bukti tetap `pending`?**
A: Worker queue belum berjalan. Jalankan `php artisan queue:work` (atau `composer dev`) di terminal terpisah. Upload diproses via antrean `evidence`, bukan langsung.

**Q: Indikator Status AI di topbar menunjukkan *Offline / Error*.**
A: Periksa `GEMINI_API_KEY` (Settings → Gemini API Key atau `.env`). Pastikan kunci valid dan model (`GEMINI_MODEL`) tersedia di akun Anda. Jika baru saja mengganti `.env`, restart worker & server.

**Q: Analisis gagal (`failed`).**
A: Buka **AI Analysis** → detail untuk membaca `error_message`. Penyebab umum: API key tidak valid/kuota habis, format file tidak didukung, file korup, atau respons tidak valid. Gunakan tombol **Retry** setelah masalah diperbaiki. Upload itu sendiri **tidak ikut gagal** — sistem tetap menyimpan catatan error.

**Q: Realisasi bisa diinput manual?**
A: **Tidak.** Realisasi hanya berasal dari AI (`source = ai`). Tidak ada antarmuka untuk input manual demi menjaga integritas & auditabilitas.

**Q: Bagaimana mengganti password atau lupa password?**
A: Ganti password via **Settings → Password**. *Catatan: alur lupa/reset password via email belum tersedia* pada tahap ini, jadi pastikan password tercatat dengan aman.

**Q: Bisakah export laporan ke Excel/PDF?**
A: Belum. Menu **Reports** bersifat *view-only* (hanya-lihat) pada saat ini.

**Q: Bagaimana menambahkan KPI baru?**
A: Tambahkan via **Measurements** → isi Definition & Formula → buat **Targets** per quarter → tambahkan **Initiatives**. Sistem otomatis akan menggunakannya untuk prompt AI dan perhitungan (sistem bersifat *scalable*).

**Q: Amanakah data & API key?**
A: API key disimpan di `.env` (bukan di source code). Setiap aktivitas penting tercatat di Audit Trail beserta user, waktu, dan IP. Validasi file (MIME & ukuran) serta sanitasi input diterapkan di sisi server.

---

*Dokumentasi ini mencerminkan implementasi aktual aplikasi KPI Advisor (Laravel 12 + Google Gemini). Bila ada perubahan fitur, perbarui dokumen ini agar selalu sinkron.*


php artisan queue:work --queue=evidence,default