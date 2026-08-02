# AGENTS.md

# AI-Based OMTI KPI Monitoring & Evidence Validation System

## Project Overview

Sistem ini merupakan aplikasi **AI-Assisted KPI Monitoring System** yang dibangun menggunakan **Laravel 12**, **MySQL**, dan **Google Gemini API**.

Tujuan utama sistem adalah **menghilangkan input manual nilai realisasi KPI** dengan cara membaca evidence menggunakan Artificial Intelligence.

User hanya mengunggah evidence, sedangkan Google Gemini akan melakukan analisis terhadap dokumen dan menghasilkan nilai realisasi sesuai business rule masing-masing KPI.

Perhitungan KPI tetap dilakukan sepenuhnya oleh Laravel menggunakan Formula perusahaan sehingga seluruh proses tetap dapat diaudit.

---

# Core Principles

## Laravel Responsibilities

Laravel bertanggung jawab terhadap seluruh business process, yaitu:

- Authentication
- Authorization
- Dashboard
- Master Data
- Upload Evidence
- File Management
- AI Request Management
- AI Result Validation
- Business Rule
- KPI Formula Calculation
- Reporting
- Audit Trail
- Logging

Laravel adalah source of truth.

Google Gemini hanya berfungsi sebagai AI Analyzer.

---

## Google Gemini Responsibilities

Google Gemini hanya bertugas untuk:

- membaca evidence
- memahami isi evidence
- memvalidasi evidence
- melakukan matching initiative
- menentukan nilai realisasi
- membuat analysis
- memberikan recommendation

Google Gemini **TIDAK PERNAH**:

- menghitung nilai KPI
- menghitung Score
- menentukan Formula
- mengubah Target
- mengubah Initiative

---

# Technology Stack

Backend

- Laravel 12
- PHP 8.2+
- MySQL

Frontend

- Blade
- Tailwind CSS v4
- Alpine.js
- Chart.js
- Heroicons
- Vite 7

AI

- Google Gemini API

File Support

- PDF
- DOCX
- XLSX
- JPG
- JPEG
- PNG

---

# Architecture

```
User

↓

Upload Evidence

↓

Laravel

↓

Google Gemini API

↓

JSON Response

↓

Laravel Validation

↓

Store AI Result

↓

Calculate KPI

↓

Dashboard
```

---

# Development Rules

## Business Rules

Realisasi TIDAK boleh diinput manual.

Nilai realisasi hanya berasal dari hasil AI.

Laravel harus memvalidasi seluruh response AI sebelum disimpan.

Formula KPI hanya boleh dijalankan oleh Laravel.

Initiative berasal dari Master Data.

AI hanya melakukan matching.

Semua hasil AI wajib disimpan sebagai audit trail.

Setiap Measurement mempunyai Prompt AI sendiri.

Setiap Measurement mempunyai Business Rule sendiri.

Sistem harus scalable ketika Measurement baru ditambahkan.

---

# Folder Structure

```
app/

    AI/

        GeminiService.php

        PromptManager.php

        ResponseValidator.php

    Services/

        KPIService.php

        ScoreCalculator.php

        EvidenceService.php

        UploadService.php

        DashboardService.php

    Repositories/

    Actions/

    DTO/

    Models/

    Http/

resources/

routes/

database/
```

---

# AI Prompt Strategy

Prompt tidak ditulis langsung di Controller.

Gunakan PromptManager.

Contoh:

```
Measurement

↓

PromptManager

↓

Generate Prompt

↓

Gemini API
```

Setiap Measurement memiliki prompt berbeda.

Misal:

- Implementasi Sistem
- Cybersecurity
- Payment
- Investment
- AI
- Enterprise Architecture

---

# Expected Gemini Output

Seluruh response Gemini HARUS berupa JSON.

Contoh:

```json
{
    "measurement": "",
    "matched_initiative": {
        "name": "",
        "confidence": 98
    },
    "evidence_valid": true,
    "realisasi": 1,
    "analysis": "",
    "recommendations": [
        "",
        "",
        ""
    ]
}
```

Laravel harus melakukan validasi JSON sebelum diproses.

---

# Database

## measurements

- id
- perspective
- objective
- measurement
- definition
- formula
- unit
- weight

## targets

- id
- measurement_id
- year
- quarter
- target

## initiatives

- id
- measurement_id
- initiative

## uploads

- id
- measurement_id
- quarter
- year
- file
- uploaded_by
- uploaded_at

## ai_results

- id
- upload_id
- matched_initiative
- confidence
- evidence_valid
- realisasi
- analysis
- recommendation
- raw_json
- created_at

## realisasi

- id
- measurement_id
- quarter
- value

## scores

- id
- measurement_id
- quarter
- score

---

# Coding Standards

Gunakan:

- Service Layer
- Repository Pattern
- DTO
- Form Request Validation

Controller hanya menangani:

- Request
- Response

Business Logic tidak boleh berada di Controller.

Seluruh logika KPI harus berada di Service.

---

# Error Handling

Seluruh proses AI harus menggunakan try-catch.

Jika AI gagal:

- simpan log
- simpan response
- tampilkan pesan kepada user

Jangan sampai proses upload gagal hanya karena AI error.

---

# Audit Trail

Seluruh aktivitas wajib dicatat.

Minimal:

- upload evidence
- request AI
- response AI
- hasil validasi
- hasil KPI
- user
- waktu

---

# Security

- Validasi MIME Type
- Validasi ukuran file
- Sanitasi seluruh input
- Jangan pernah menyimpan API Key di source code
- Gunakan .env

---

# Performance

Gunakan Queue untuk:

- Upload Processing
- Gemini Analysis
- KPI Calculation

Dashboard tidak boleh menunggu proses AI selesai.

---

# Testing

Project ini **tidak menggunakan Unit Test maupun Feature Test** pada tahap pengembangan awal.

Fokus utama adalah:

- implementasi business process
- integrasi Google Gemini
- validasi AI
- dashboard
- audit trail

---

# UI / UX Guidelines

Dashboard harus menggunakan desain modern bergaya Executive Dashboard.

Gunakan:

- Tailwind CSS v4
- Heroicons
- Chart.js
- Responsive Layout
- Glassmorphism ringan
- Soft Shadow
- Rounded XL
- Smooth Transition
- Dark Mode Ready

Palet warna utama:

- Primary : Indigo (#4F46E5)
- Secondary : Emerald (#10B981)
- Warning : Amber (#F59E0B)
- Danger : Rose (#F43F5E)
- Background : Slate-50
- Card : White

---

# Dashboard Layout

Sidebar (Collapsible)

- Dashboard
- KPI Monitoring
- Measurements
- Targets
- Initiatives
- Upload Evidence
- AI Analysis
- Reports
- Audit Trail
- Settings

Topbar

- Search
- Notification
- AI Status
- User Menu

Dashboard Widgets

- Total KPI
- KPI Achieved
- KPI On Progress
- KPI Below Target
- AI Analyses Today
- Pending Analysis
- Average KPI Score
- Evidence Uploaded

Charts

- KPI Achievement per Quarter
- KPI Trend
- Perspective Performance
- AI Confidence Distribution
- Upload Activity
- Initiative Progress

Recent Activity

- Latest Evidence
- Latest AI Analysis
- Latest Recommendation

Quick Actions

- Upload Evidence
- Analyze Evidence
- View Reports
- Manage KPI

---

# Dashboard Style

Design harus menyerupai dashboard enterprise modern seperti:

- Microsoft Power BI
- Azure Portal
- Notion
- Linear
- Stripe Dashboard
- Vercel Dashboard

Karakteristik:

- Clean
- Minimalis
- Banyak whitespace
- Soft gradient
- Smooth animation
- Card-based layout
- Responsive
- Professional
- Executive-friendly

---

# Final Goal

Membangun AI-Based KPI Monitoring System yang:

✔ Menghilangkan input manual Realisasi

✔ Memanfaatkan Google Gemini untuk membaca evidence

✔ Menghasilkan Realisasi otomatis

✔ Memberikan AI Recommendation

✔ Menghitung KPI menggunakan Formula perusahaan

✔ Menyimpan seluruh audit trail

✔ Mudah dikembangkan

✔ Mudah diaudit

✔ Enterprise Ready

✔ High Performance

✔ Scalable