# Dokumen Referensi Strategis (DUMMY)

Folder ini berisi dokumen dummy **RJPP** dan **MPTI** untuk keperluan
development & testing fitur Strategic Recommendation AI.

## Status Dokumen

- **DUMMY / SINTETIS** — bukan dokumen perusahaan nyata.
- Disusun berdasarkan **struktur publik** dokumen RJPP/MPTI BUMN
  (mengacu pada pedoman Kemenkeu / Permen BUMN / praktik umum BUMN).
- Profil perusahaan fiktif: **PT PUM (Persero)** — Perusahaan Umum
  Percetakan & Pengelolaan Logam. Setiap kemiripan dengan perusahaan
  nyata bersifat kebetulan.
- Angka, tanggal, dan nama orang adalah data sintetis.

## Tujuan

1. Sebagai input **DocumentExtractor** untuk uji ekstraksi struktur
   TOC, heading, tabel, dan section relevan.
2. Sebagai konteks **StrategicRecommendationPrompt** (fitur ke-2
   aplikasi KPI Advisor) — Gemini akan membaca ringkasan ekstraksi
   dan merekomendasikan inisiatif / measurement KPI baru berdasarkan
   tren digital terkini.

## File

| File | Deskripsi |
| --- | --- |
| `RJPP_PT_PUM_2025-2029.md` | Dummy RJPP 5 tahun |
| `MPTI_PT_PUM_2025-2029.md` | Dummy MPTI 5 tahun (turunan RJPP) |

## Catatan Keamanan

Karena dokumen ini **dummy**, aman digunakan untuk:
- Development prompt
- Sharing ke pihak eksternal (vendor, konsultan)
- Testing integrasi Gemini

Dokumen **asli** RJPP/MPTI hanya boleh diupload pada environment
production dan diproses melalui `DocumentExtractor` yang menghasilkan
ringkasan **non-sensitif** sebelum dikirim ke AI.