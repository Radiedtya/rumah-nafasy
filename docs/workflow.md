# 📄 Dokumentasi Workflow — Rumah Natasy

```markdown
# Rumah Natasy — E-Konsultasi Psikologi Online
## Dokumentasi Workflow & Alur Sistem

> **Versi:** 1.0  
> **Tanggal:** September 2026  
> **Tech Stack:** Laravel 13 (API) + Vue 3.5+ (SPA) + MySQL + Jitsi Meet + Midtrans + Fonnte

---

## 1. Tentang Rumah Natasy

Rumah Natasy adalah platform **E-Konsultasi Psikologi** yang menghubungkan pasien dengan psikolog berlisensi secara online. Platform menyediakan konsultasi via video call atau chat dengan psikolog yang telah terverifikasi (SIP & HIMPsi).

### Nilai Utama
- Psikolog terverifikasi & berlisensi resmi
- Privasi terjamin (UU PDP No. 27/2022)
- Harga transparan sesuai kategori klien
- Konsultasi online (video/chat)
- Notifikasi otomatis via WhatsApp
- Fleksibel (pilih jadwal sendiri, reschedule mudah)

---

## 2. User Roles

| Role | Deskripsi | Akses |
|------|-----------|-------|
| **Pasien** | Klien yang mencari konsultasi | Register, browse psikolog, order, bayar, pilih jadwal, konsultasi, review |
| **Psikolog** | Tenaga ahli berlisensi | Kelola jadwal, terima booking, jalankan konsultasi, catat notes, lihat income |
| **Admin** | Pengelola platform | Verifikasi psikolog, kelola kategori/harga, kelola transaksi, approve refund, lihat reports |

---

## 3. Database Schema

### Core Tables

```
users
├── id, name, email, password, phone, avatar
├── is_active, email_verified_at, phone_verified_at
├── soft_deletes, timestamps
│
├── psikolog_profiles (1:1)
│   ├── user_id, specialization_id, slug
│   ├── bio, experience_years, license_no
│   ├── education, workplace, custom_rate
│   ├── status (pending/verified/suspended/rejected)
│   ├── verified_at, verified_by, rejection_reason
│   ├── is_available, rating_avg, total_reviews
│   └── total_consultations
│
├── orders (1:N as pasien)
│   ├── order_number, pasien_id, psikolog_id
│   ├── category_id, duration_id
│   ├── calculated_price, consultation_type
│   ├── status, expires_at, scheduled_at
│   │   ├── payments (1:1)
│   │   │   └── amount, payment_channel, transaction_id
│   │   │   └── status, payment_url, paid_at
│   │   ├── bookings (1:1)
│   │   │   ├── booking_date, start_time, end_time
│   │   │   ├── room_id, status, locked_until
│   │   │   ├── consultations (1:1)
│   │   │   │   ├── started_at, ended_at, status
│   │   │   │   └── consultation_notes (1:N) [ENCRYPTED]
│   │   │   ├── reviews (1:N)
│   │   │   │   └── rating, comment, is_published
│   │   │   └── reschedule_logs (1:N)
│   │   │       └── old/new date & time, reason, rescheduled_by
│   │   └── refunds (1:1)
│   │       └── amount, reason, status, processed_by
│ │
├── patient_verifications (1:N)
│   └── id_type, id_number, id_file_path, status
│
└── schedules (1:N as psikolog)
    └── day_of_week, start_time, end_time, is_available

### Reference Tables
├── specializations (name, slug, description, icon)
├── client_categories (name, slug, base_price)
└── duration_options (name, minutes, multiplier)

### Package Tables
├── roles, permissions (Spatie Permission)
├── activity_log (Spatie ActivityLog)
├── media (Spatie MediaLibrary)
├── personal_access_tokens (Sanctum)
└── telescope_entries (Telescope)
```

---

## 4. Pricing System

### Faktor Penentu Harga

Harga konsultasi ditentukan oleh **3 faktor**:

1. **Kategori Klien** → base price per 60 menit
2. **Durasi Konsultasi** → multiplier
3. **Custom Rate Psikolog** (opsional) → override base price

### Tabel Kategori & Base Price

| Kategori | Deskripsi | Base Price (per 60 menit) |
|----------|-----------|--------------------------|
| Siswa | SMP - SMA | Rp 50.000 |
| Mahasiswa | D3/S1 | Rp 75.000 |
| Umum | Dewasa umum | Rp 150.000 |
| Pasangan | Couple therapy | Rp 250.000 |
| Keluarga | 3+ orang | Rp 300.000 |

### Tabel Durasi & Multiplier

| Durasi | Menit | Multiplier |
|--------|-------|------------|
| 30 Menit | 30 | 0.50 |
| 60 Menit | 60 | 1.00 |
| 90 Menit | 90 | 1.50 |

### Formula Harga

```
IF psikolog.custom_rate IS NOT NULL:
    harga = custom_rate × duration.multiplier
ELSE:
    harga = category.base_price × duration.multiplier
```

### Contoh

| Klien | Kategori | Durasi | Perhitungan | Harga |
|-------|----------|--------|-------------|-------|
| Mahasiswa | Mahasiswa (75rb) | 60 min (1.0x) | 75.000 × 1.0 | Rp 75.000 |
| Siswa | Siswa (50rb) | 30 min (0.5x) | 50.000 × 0.5 | Rp 25.000 |
| Umum | Umum (150rb) | 90 min (1.5x) | 150.000 × 1.5 | Rp 225.000 |

---

## 5. Order & Payment Flow (E-Commerce Style)

### Flow Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    FASE 1: BROWSE & SELECT                   │
│                                                              │
│  1. User browse daftar psikolog (filter: spesialisasi,      │
│     rating, harga)                                           │
│                                                              │
│  2. Buka detail psikolog (profil, review, jadwal praktek)    │
│                                                              │
│  3. Pilih kategori konsultasi                                │
│     (siswa / mahasiswa / umum / pasangan / keluarga)        │
│                                                              │
│  4. Pilih durasi (30 / 60 / 90 menit)                       │
│                                                              │
│  5. Sistem calculate harga otomatis                         │
│     (kategori × durasi multiplier)                           │
│                                                              │
│  6. Review order summary                                     │
│     ┌────────────────────────────────┐                       │
│     │ Psikolog: dr. Andi Pratama     │                       │
│     │ Kategori: Mahasiswa            │                       │
│     │ Durasi: 60 menit               │                       │
│     │ Harga: Rp 75.000               │                       │
│     │ [Bayar Sekarang]               │                       │
│     └────────────────────────────────┘                       │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│                    FASE 2: PAYMENT                           │
│                                                              │
│  7. Order dibuat → status: pending_payment                   │
│     → expires_at = now + 24 jam                              │
│                                                              │
│  8. Redirect ke Midtrans Snap                                │
│     (VA, e-wallet, QRIS, card, retail)                       │
│                                                              │
│  9. Payment berhasil → Midtrans webhook ke backend          │
│     → payment status: success                                │
│     → order status: paid                                     │
│     → expires_at = now + 7 hari (pilih jadwal)              │
│                                                              │
│  10. WA notifikasi ke psikolog + pasien                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              FASE 3: PILIH JADWAL (Setelah Bayar)           │
│                                                              │
│  11. User diarahkan ke halaman pilih jadwal                  │
│                                                              │
│  12. Tampil available slots berdasarkan:                     │
│      - Schedule psikolog (day_of_week, start_time, end_time)│
│      - Existing bookings (filter conflict)                  │
│      - Duration dari order (untuk generate slots)            │
│                                                              │
│  13. User pilih tanggal & jam                               │
│      → Cek konflik real-time                                 │
│                                                              │
│  14. Booking dibuat → status: confirmed                      │
│      → room_id (Jitsi) di-generate                           │
│      → order status: scheduled                              │
│                                                              │
│  15. WA notifikasi: detail jadwal ke psikolog + pasien      │
└──────────────────────────┬──────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────┐
│              FASE 4: KONSULTASI                               │
│                                                              │
│  16. H-1 → WA reminder ke psikolog + pasien                 │
│  17. H-1 jam → WA reminder urgent                           │
│                                                              │
│  18. Jam konsultasi → tombol "Join" muncul                  │
│      → Redirect ke Jitsi Meet room                          │
│                                                              │
│  19. Psikolog klik "Start Consultation"                     │
│      → consultation record dibuat                           │
│      → booking status: in_progress                          │
│                                                              │
│  20. Selama konsultasi, psikolog dapat:                     │
│      - Add notes (encrypted)                                │
│      - Chat (jika tipe chat)                                │
│                                                              │
│  21. Psikolog klik "End Consultation"                       │
│      → consultation status: completed                       │
│      → booking status: completed                            │
│      → order status: completed                              │
│      → psikolog.total_consultations++                        │
│                                                              │
│  22. Pasien diminta review psikolog                         │
│      → rating (1-5) + comment                               │
│      → psikolog rating_avg & total_reviews di-update        │
└─────────────────────────────────────────────────────────────┘
```

### Order Status Flow

```
                    ┌─────────────┐
                    │ Create Order│
                    └──────┬──────┘
                           │
                           ▼
                    ┌──────────────────┐
                    │ pending_payment  │
                    │ (24 jam bayar)   │
                    └────┬────────┬────┘
                         │        │
              ┌──────────┘        └──────────┐
              ▼                               ▼
     ┌──────────────┐               ┌──────────────┐
     │ Payment      │               │ Payment      │
     │ success      │               │ expired/failed│
     └──────┬───────┘               └──────┬───────┘
            │                              │
            ▼                              ▼
     ┌──────────────┐             ┌──────────────┐
     │    paid       │             │  cancelled   │
     │ (7 hari pilih │             │  (auto)      │
     │  jadwal)      │             └──────────────┘
     └────┬─────────┘
          │
          ▼
     ┌──────────────┐
     │ User pilih   │
     │ jadwal       │
     └──────┬───────┘
            │
            ▼
     ┌──────────────┐
     │  scheduled   │
     └──────┬───────┘
            │
            ▼
     ┌──────────────┐
     │ in_progress  │
     │ (konsultasi  │
     │  berjalan)   │
     └──────┬───────┘
            │
            ▼
     ┌──────────────┐
     │  completed   │
     └──────────────┘

     (Di tahap manapun bisa cancel → refund flow)
```

---

## 6. Booking & Schedule Management

### Available Slots Algorithm

```
Input: psikolog_id, date, duration_minutes

1. Cari day_of_week dari date (0=Ahad, 6=Sabtu)

2. Get psikolog schedule untuk hari itu:
   SELECT * FROM schedules
   WHERE psikolog_id = ?
   AND day_of_week = ?
   AND is_available = true

3. Get existing bookings untuk tanggal itu:
   SELECT * FROM bookings
   WHERE psikolog_id = ?
   AND booking_date = ?
   AND status IN ('confirmed', 'in_progress')

4. Generate slots:
   FOR EACH schedule:
     start = schedule.start_time
     WHILE start + duration <= schedule.end_time:
       slot_end = start + duration
       
       is_available = true
       FOR EACH existing_booking:
         IF (start < booking.end_time AND slot_end > booking.start_time):
           is_available = false
       
       IF is_available:
         ADD slot {start, slot_end}
       
       start += duration

5. Return available slots
```

### Slot Locking (10 menit)

```
User pilih slot →
  booking.locked_until = now + 10 minutes →
  
  IF user konfirmasi dalam 10 menit:
    booking.status = confirmed (permanent)
  ELSE:
    Cron job release slot (locked_until < now)
```

---

## 7. Reschedule Policy

### Aturan Reschedule

| Aturan | Detail |
|--------|--------|
| **Maksimal reschedule** | 2x per order (pasien) |
| **Minimum waktu** | H-1 (24 jam sebelum jadwal) |
| **Reschedule by psikolog** | Tidak hitung kuota pasien |
| **Setelah 2x** | Tidak bisa reschedule, hanya bisa cancel |

### Reschedule Flow

```
Pasien klik "Reschedule"
    │
    ▼
Cek: sudah berapa kali reschedule?
    ├── < 2x → lanjut
    └── ≥ 2x → "Maaf, kuota reschedule habis"
    │
    ▼
Cek: jadwal > 24 jam dari sekarang?
    ├── Ya → tampilkan calendar available slots
    └── Tidak → "Tidak bisa reschedule, kurang dari 24 jam"
    │
    ▼
Pilih jadwal baru → cek konflik
    ├── Available → create reschedule_log, update booking
    └── Conflict → "Slot tidak tersedia"
    │
    ▼
WA notifikasi ke psikolog + pasien
```

### Reschedule Log Record

```
reschedule_logs:
  - booking_id
  - old_date, old_start_time, old_end_time
  - new_date, new_start_time, new_end_time
  - reason
  - rescheduled_by (pasien / psikolog / admin)
```

---

## 8. Refund Policy

### Tiered Refund Berdasarkan Waktu Cancel

| Waktu Cancel | Refund | Alasan |
|-------------|--------|--------|
| **H-3 atau lebih** (72h+) | 100% full refund | Cukup waktu cari pengganti |
| **H-2** (48-72h) | 75% refund | Sedikit penalty |
| **H-1** (24-48h) | 50% refund | Susah cari pengganti |
| **H-0** (<24h) | 0% (no refund) | Psikolog udah blocking waktu |
| **Psikolog cancel** | 100% + reschedule gratis | Bukan salah pasien |
| **Force majeure** | 100% | Dengan bukti (surat dokter) |
| **Payment expired** | N/A | Auto-cancel, dana belum masuk |
| **No schedule in 7 days** | 100% auto-refund | Sistem otomatis |

### Refund Flow

```
Cancel request dibuat
    │
    ▼
Sistem hitung refund:
  hours_until_consultation = now → consultation_time
    │
    ├── ≥ 72h → 100% refund
    ├── ≥ 48h → 75% refund
    ├── ≥ 24h → 50% refund
    └── < 24h → 0% refund
    │
    ▼
Create refund record:
  ├── amount > 0 → status: pending (admin approve)
  └── amount = 0 → status: rejected
    │
    ▼
Update booking → cancelled
Update order → cancelled
    │
    ▼
WA notifikasi: "Refund diproses, dana kembali dalam X hari kerja"
    │
    ▼
Admin review refund:
  ├── Approve → process refund via Midtrans
  └── Reject → inform pasien
```

---

## 9. Consultation Flow

### Konsultasi Lifecycle

```
┌─────────────────────────────────────────────────┐
│  Booking Confirmed (status: confirmed)          │
│  → room_id di-generate (Jitsi UUID)            │
│  → consultation belum dibuat                    │
└──────────────────────┬──────────────────────────┘
                       │
                       │ Jam konsultasi tiba
                       ▼
┌─────────────────────────────────────────────────┐
│  Psikolog Start Consultation                    │
│  → Consultation record dibuat                   │
│  → started_at = now                             │
│  → status: in_progress                          │
│  → booking.status = in_progress                │
└──────────────────────┬──────────────────────────┘
                       │
                       │ Selama konsultasi
                       ▼
┌─────────────────────────────────────────────────┐
│  Psikolog Add Notes                             │
│  → content ENCRYPTED (at-rest, UU PDP)         │
│  → multiple notes allowed                      │
│  → hanya psikolog terkait + pasien yg bisa akses│
└──────────────────────┬──────────────────────────┘
                       │
                       │ Konsultasi selesai
                       ▼
┌─────────────────────────────────────────────────┐
│  Psikolog End Consultation                      │
│  → ended_at = now                               │
│  → status: completed                            │
│  → booking.status = completed                  │
│  → order.status = completed                     │
│  → psikolog.total_consultations++              │
└──────────────────────┬──────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────┐
│  Pasien diminta Review                         │
│  → rating (1-5) + comment                      │
│  → psikolog.rating_avg di-update (avg semua)   │
│  → psikolog.total_reviews++                    │
└─────────────────────────────────────────────────┘
```

### Consultation Notes & Privacy

| Aspek | Implementasi |
|-------|-------------|
| **Encryption** | Laravel `encrypted` cast → AES-256-CBC |
| **Access control** | Hanya psikolog terkait yang bisa input |
| **Audit log** | Spatie ActivityLog record setiap akses |
| **Data retention** | Auto-delete setelah X bulan (configurable) |
| **Right to erasure** | Endpoint buat pasien request hapus data |

---

## 10. Psikolog Verification Flow

### Tata Cara Verifikasi Psikolog

```
1. Psikolog daftar (register sebagai psikolog)
   → admin buatkan akun, assign role: psikolog
   → profile status: pending

2. Psikolog lengkapi profil & upload dokumen:
   ├── KTP
   ├── Surat Izin Praktek (SIP)
   ├── Sertifikat profesi (HIMPsi)
   ├── Ijazah
   └── Foto formal

3. Admin verifikasi (manual review):
   ├── Cek dokumen
   ├── Interview online (opsional)
   ├── Approve → status: verified, verified_at = now
   └── Reject → status: rejected, rejection_reason

4. Psikolog terverifikasi:
   → is_available = true
   → Muncul di listing publik
   → Bisa terima booking
   → Bisa set jadwal praktek

5. Suspensi (jika ada masalah):
   → Admin set status: suspended
   → is_available = false
   → Tidak muncul di listing
   → Booking yang sudah ada tetap jalan
```

---

## 11. Patient Verification Flow

### Verifikasi Identitas Pasien

```
1. Pasien register (role: pasien)

2. Untuk kategori siswa/mahasiswa:
   → Wajib upload verifikasi identitas:
     ├── Siswa: Kartu Pelajar
     ├── Mahasiswa: KTM (Kartu Mahasiswa)
     └── Umum: KTP/SIM/Paspor

3. Upload dokumen:
   → patient_verifications record dibuat
   → status: pending

4. Admin verifikasi:
   ├── Approve → status: verified
   └── Reject → status: rejected + reason

5. Setelah verified:
   → Bisa order dengan kategori yang sesuai
```

---

## 12. WhatsApp Notification Flow

### Trigger Events

| Event | Trigger | Notifikasi Ke | Isi Pesan |
|-------|---------|-------------|-----------|
| **Order dibuat** | POST /pasien/orders | Pasien | "Pesanan {order_number} dibuat. Bayar dalam 24 jam." |
| **Payment berhasil** | Midtrans webhook | Pasien + Psikolog | "Pembayaran diterima. Silakan pilih jadwal." / "Ada booking baru." |
| **Jadwal dipilih** | POST /pasien/orders/{id}/schedule | Pasien + Psikolog | "Konsultasi {tgl, jam} dengan {psikolog/pasien}" |
| **H-1 reminder** | Scheduler (daily) | Pasien + Psikolog | "Reminder: besok ada konsultasi jam XX:XX" |
| **H-1 jam reminder** | Scheduler (hourly) | Pasien + Psikolog | "Konsultasi dimulai dalam 1 jam" |
| **Konsultasi selesai** | POST /consultations/{id}/end | Pasien | "Terima kasih. Mohon beri review." |
| **Reschedule** | PUT /bookings/{id}/reschedule | Pasien + Psikolog | "Jadwal diubah ke {tgl, jam}" |
| **Cancel booking** | POST /bookings/{id}/cancel | Pasien + Psikolog | "Booking dibatalkan. Refund: {amount}" |
| **Psikolog verified** | Admin approve | Psikolog | "Akun Anda terverifikasi!" |
| **No schedule 3 hari** | Scheduler (daily) | Pasien | "Anda belum memilih jadwal. Segera pilih." |
| **No schedule 6 hari** | Scheduler (daily) | Pasien | "Urgent! Batas pilih jadwal 1 hari lagi." |
| **Order expired (7 hari)** | Scheduler (daily) | Pasien | "Order expired. Auto-refund diproses." |

### Implementasi

```
Backend (Laravel):
├── FonnteService.php → HTTP POST ke Fonnte API
├── Jobs/SendWhatsAppNotification.php → Queue job (async)
├── Notifications/ → Laravel notification classes
└── Console/Kernel.php → Scheduler commands

Flow:
1. Event terjadi (misal: payment berhasil)
2. Dispatch SendWhatsAppNotification job ke queue
3. Queue worker execute job
4. FonnteService kirim HTTP POST ke Fonnte API
5. Fonnte kirim WA ke nomor tujuan
6. Fonnte webhook → status delivery (sent/failed)
```

---

## 13. Scheduler Jobs (Cron)

| Schedule | Job | Fungsi |
|----------|-----|--------|
| `everyMinute` | `slots:release-locked` | Release slot yang lock-nya expired (>10 menit) |
| `everyFiveMinutes` | `payments:sync-status` | Sync payment status pending ke Midtrans |
| `hourly` | `reminders:tomorrow` | Kirim WA reminder untuk konsultasi besok |
| `hourly` | `reminders:one-hour` | Kirim WA reminder untuk konsultasi dalam 1 jam |
| `hourly` | `orders:no-schedule-reminder` | Reminder ke pasien yang belum pilih jadwal (H+3, H+6) |
| `daily` | `orders:auto-expire` | Expire order yang >7 hari belum pilih jadwal → auto-refund |
| `daily` | `reports:daily-admin` | Kirim daily stats ke admin |
| `daily` | `cleanup:consultation-notes` | Hapus notes lama sesuai retention policy |
| `daily` | `cleanup:recordings` | Hapus Jitsi recording lama (privacy) |

### Cron Setup

```bash
# Server (production)
* * * * * cd /path/to/backend && php artisan schedule:run >> /dev/null 2>&1
```

---

## 14. Video Call Flow (Jitsi Meet)

### Setup

```
Jitsi Meet server (self-hosted, Docker):
├── VPS terpisah (Hetzner/DO)
├── Domain: meet.rumahnatasy.id
├── Docker container
└── Min 2GB RAM (recommended 4GB)

Backend:
├── JitsiService.php → generate room_id (UUID)
├── Room dibuat saat booking confirmed
└── Token/URL dikirim ke frontend

Frontend (Vue):
├── @jitsi/vue-jitsi-meet atau iframe
├── Load room dengan room_id dari booking
├── Psikolog & pasien join room
└── Konsultasi berjalan
```

### Konsultasi Video Flow

```
1. Booking confirmed → room_id di-generate (UUID)
2. Jam konsultasi → frontend load Jitsi dengan room_id
3. Psikolog & pasien join room
4. Video/audio/chat berjalan via Jitsi server
5. Psikolog start consultation (backend record)
6. Konsultasi berjalan...
7. Psikolog end consultation (backend record)
8. Jitsi room otomatis close
```

---

## 15. Security & Privacy

### UU PDP (Pelindungan Data Pribadi) Compliance

| Aspek | Implementasi |
|-------|-------------|
| **Encryption at rest** | Consultation notes di-encrypt (Laravel encrypted cast) |
| **Access control** | Role-based (pasien, psikolog, admin) + ownership check |
| **Audit log** | Spatie ActivityLog — record siapa akses data kapan |
| **Data retention** | Auto-delete consultation notes setelah X bulan |
| **Right to erasure** | Endpoint untuk pasien request hapus data |
| **Consent management** | Record informed consent sebelum konsultasi |
| **Data export** | Endpoint untuk pasien export data mereka |
| **HTTPS** | SSL/TLS wajib di production |
| **CSRF protection** | Laravel CSRF tokens |
| **Rate limiting** | 60 req/menit per IP, 5 login attempt/menit |

### Emergency Protocol

```
Keyword detection di chat/notes:
  "bunuh diri", "self harm", "menyakiti diri",
  "mau mati", "berakhir hidup", dll

Trigger:
  1. Alert ke psikolog (pop-up notice)
  2. Log to admin dashboard (urgent flag)
  3. Display emergency hotline (119 Kemenkes)
  4. Record incident for follow-up
```

---

## 16. API Endpoint Summary

### Auth Endpoints

| Method | Endpoint | Auth | Deskripsi |
|--------|----------|------|-----------|
| POST | `/api/v1/auth/register` | No | Register pasien baru |
| POST | `/api/v1/auth/login` | No | Login semua role |
| POST | `/api/v1/auth/logout` | Yes | Logout (revoke token) |
| GET | `/api/v1/auth/me` | Yes | Get current user |
| PUT | `/api/v1/auth/profile` | Yes | Update profile |

### Public Endpoints (No Auth)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/public/psikolog` | List psikolog (filter, paginate) |
| GET | `/api/v1/public/psikolog/{slug}` | Detail psikolog + schedules |
| GET | `/api/v1/public/psikolog/{slug}/reviews` | Reviews psikolog |
| GET | `/api/v1/public/specializations` | List spesialisasi |
| GET | `/api/v1/public/specializations/{slug}` | Detail spesialisasi |
| GET | `/api/v1/public/categories` | List kategori + harga |
| GET | `/api/v1/public/durations` | List durasi |

### Pasien Endpoints (Auth + role:pasien)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/pasien/orders` | Create order (pilih psikolog + kategori + durasi) |
| GET | `/api/v1/pasien/orders` | List orders |
| GET | `/api/v1/pasien/orders/{order}` | Detail order |
| POST | `/api/v1/pasien/orders/{order}/cancel` | Cancel order (pending_payment only) |
| POST | `/api/v1/pasien/orders/{order}/payment` | Create payment (Midtrans Snap) |
| GET | `/api/v1/pasien/payments/{payment}` | Check payment status |
| GET | `/api/v1/pasien/psikolog/{id}/slots` | Get available slots |
| POST | `/api/v1/pasien/orders/{order}/schedule` | Create booking (pilih jadwal) |
| GET | `/api/v1/pasien/bookings` | List bookings |
| GET | `/api/v1/pasien/bookings/{booking}` | Detail booking |
| PUT | `/api/v1/pasien/bookings/{booking}/reschedule` | Reschedule (max 2x) |
| POST | `/api/v1/pasien/bookings/{booking}/cancel` | Cancel + refund calc |

### Psikolog Endpoints (Auth + role:psikolog)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/psikolog/dashboard` | Stats overview |
| GET | `/api/v1/psikolog/schedules` | List jadwal praktek |
| POST | `/api/v1/psikolog/schedules` | Tambah jadwal |
| PUT | `/api/v1/psikolog/schedules/{schedule}` | Update jadwal |
| DELETE | `/api/v1/psikolog/schedules/{schedule}` | Hapus jadwal |
| GET | `/api/v1/psikolog/bookings` | List booking masuk |
| GET | `/api/v1/psikolog/bookings/{booking}` | Detail booking |
| PUT | `/api/v1/psikolog/bookings/{booking}/status` | Update status booking |
| GET | `/api/v1/psikolog/consultations` | List konsultasi |
| GET | `/api/v1/psikolog/consultations/{consultation}` | Detail konsultasi + notes |
| POST | `/api/v1/psikolog/bookings/{booking}/consultation/start` | Mulai konsultasi |
| POST | `/api/v1/psikolog/consultations/{consultation}/end` | Selesai konsultasi |
| GET | `/api/v1/psikolog/consultations/{consultation}/notes` | List catatan |
| POST | `/api/v1/psikolog/consultations/{consultation}/notes` | Tambah catatan (encrypted) |
| GET | `/api/v1/psikolog/profile` | Lihat profil |
| PUT | `/api/v1/psikolog/profile` | Update profil |
| GET | `/api/v1/psikolog/income` | Ringkasan pendapatan |
| GET | `/api/v1/psikolog/income/report` | Laporan pendapatan (date range) |

### Admin Endpoints (Auth + role:admin) — TODO Step 7

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| GET | `/api/v1/admin/dashboard` | Stats overview platform |
| GET | `/api/v1/admin/psikolog` | List semua psikolog |
| PUT | `/api/v1/admin/psikolog/{user}/verify` | Verifikasi psikolog |
| PUT | `/api/v1/admin/psikolog/{user}/suspend` | Suspend psikolog |
| PUT | `/api/v1/admin/psikolog/{user}/activate` | Aktifkan psikolog |
| GET/POST/PUT/DELETE | `/api/v1/admin/categories` | CRUD kategori |
| GET/POST/PUT/DELETE | `/api/v1/admin/durations` | CRUD durasi |
| GET/POST/PUT/DELETE | `/api/v1/admin/specializations` | CRUD spesialisasi |
| GET | `/api/v1/admin/transactions` | List semua transaksi |
| GET | `/api/v1/admin/transactions/{order}` | Detail transaksi |
| GET | `/api/v1/admin/refunds` | List refund request |
| PUT | `/api/v1/admin/refunds/{refund}/approve` | Approve refund |
| PUT | `/api/v1/admin/refunds/{refund}/reject` | Reject refund |
| GET | `/api/v1/admin/reports/transactions` | Report transaksi |
| GET | `/api/v1/admin/reports/psikolog` | Report psikolog |
| GET | `/api/v1/admin/reports/bookings` | Report booking |

### Webhook Endpoints (No Auth)

| Method | Endpoint | Deskripsi |
|--------|----------|-----------|
| POST | `/api/v1/webhooks/midtrans` | Midtrans payment notification |
| GET | `/api/v1/webhooks/midtrans` | Mock payment (development) |
| POST | `/api/v1/webhooks/fonnte` | Fonnte delivery status |

---

## 17. Frontend Pages (Vue SPA)

### Public Pages

| Page | Route | Auth | Deskripsi |
|------|-------|------|-----------|
| Landing Page | `/` | No | Hero, how it works, psikolog, pricing, FAQ |
| Login | `/login` | No | Form login semua role |
| Register | `/register` | No | Form register pasien |
| Psikolog List | `/psikolog` | No | Browse + filter psikolog |
| Psikolog Detail | `/psikolog/:slug` | No | Profil, reviews, jadwal preview |
| Cara Kerja | `/cara-kerja` | No | Penjelasan alur konsultasi |
| Harga | `/harga` | No | Tabel kategori & durasi |
| FAQ | `/faq` | No | Pertanyaan umum |
| Tentang | `/tentang` | No | Tentang Rumah Natasy |

### Authenticated Pages — Pasien

| Page | Route | Deskripsi |
|------|-------|-----------|
| Dashboard | `/dashboard` | Upcoming, riwayat, stats |
| Checkout | `/checkout/:psikologId` | Pilih kategori + durasi |
| Payment | `/payment/:orderId` | Midtrans redirect |
| Payment Success | `/payment/success/:orderId` | Pilih jadwal |
| Pilih Jadwal | `/booking/:orderId` | Calendar + slot picker |
| Booking Confirmed | `/booking/confirmed/:bookingId` | Detail + add to calendar |
| Konsultasi Room | `/consultation/:bookingId` | Jitsi embedded |
| Riwayat | `/riwayat` | Riwayat konsultasi + notes |
| Profil | `/profile` | Edit profil |

### Authenticated Pages — Psikolog

| Page | Route | Deskripsi |
|------|-------|-----------|
| Dashboard | `/psikolog/dashboard` | Stats, today's bookings |
| Jadwal Praktek | `/psikolog/schedules` | CRUD jadwal |
| Booking Masuk | `/psikolog/bookings` | List + detail |
| Konsultasi | `/psikolog/consultations` | List + start/end |
| Catatan | `/psikolog/consultations/:id` | Notes (encrypted) |
| Pendapatan | `/psikolog/income` | Income overview + report |
| Profil | `/psikolog/profile` | Edit profil psikolog |

### Authenticated Pages — Admin

| Page | Route | Deskripsi |
|------|-------|-----------|
| Dashboard | `/admin/dashboard` | Platform stats |
| Kelola Psikolog | `/admin/psikolog` | Verify, suspend, activate |
| Kelola Kategori | `/admin/categories` | CRUD kategori + harga |
| Kelola Durasi | `/admin/durations` | CRUD durasi |
| Transaksi | `/admin/transactions` | List semua transaksi |
| Refund | `/admin/refunds` | Approve/reject refund |
| Reports | `/admin/reports` | Laporan transaksi, psikolog, booking |

---

## 18. Deployment Architecture

```
                    ┌─────────────┐
                    │  User Browser│
                    └──────┬──────┘
                           │
                    ┌──────┴──────┐
                    │   Cloudflare │ (DNS, CDN, SSL)
                    └──────┬──────┘
                           │
           ┌───────────────┼───────────────┐
           │               │               │
    ┌──────┴──────┐ ┌─────┴─────┐ ┌──────┴──────┐
    │   Vercel    │ │ VPS/Railway│ │  VPS Jitsi  │
    │ (Frontend)  │ │ (Backend)  │ │ (Video Call)│
    │ Vue 3 SPA   │ │ Laravel 13 │ │ Docker     │
    │ Tailwind    │ │ MySQL      │ │ meet.domain│
    └─────────────┘ └─────┬─────┘ └────────────┘
                           │
                    ┌──────┴──────┐
                    │  3rd Party  │
                    │             │
                    │ Midtrans    │ ← Payment
                    │ Fonnte      │ ← WhatsApp
                    │ SMTP Mail   │ ← Email
                    └─────────────┘
```

### Hosting Plan

| Komponen | Service | Cost |
|----------|---------|------|
| Frontend (Vue) | Vercel (free) | Rp 0 |
| Backend (Laravel) | Railway / VPS | ~$5-10/bulan |
| Database | Railway / VPS MySQL | ~$5/bulan |
| Jitsi Meet | VPS (Hetzner) | ~$5/bulan |
| WhatsApp | Fonnte | ~Rp 49.000/bulan |
| Domain | .id / .com | ~Rp 150.000/tahun |
| **Total** | | **~Rp 200-400rb/bulan** |

---

## 19. Development Status

| Step | Scope | Status |
|------|-------|--------|
| Step 1 | Project setup, install dependencies | ✅ Complete |
| Step 2 | Database migrations & models | ✅ Complete |
| Step 3 | Auth & API controllers | ✅ Complete |
| Step 4 | Public APIs (psikolog, categories, durations) | ✅ Complete |
| Step 5 | Order, payment & booking flow | ✅ Complete |
| Step 6 | Psikolog dashboard APIs | ✅ Complete |
| Step 7 | Admin dashboard APIs | ⏳ Next |
| Step 8 | WhatsApp notifications (Fonnte) | ⏳ Todo |
| Step 9 | Scheduler (reminders, auto-refund) | ⏳ Todo |
| Step 10 | Video call (Jitsi) | ⏳ Todo |
| Step 11 | Frontend (Vue SPA) | ⏳ In Progress (teman) |

---

## 20. Akun Test (Development)

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@rumahnatasy.id | password |
| Psikolog | andi@rumahnatasy.id | password |
| Psikolog | sari@rumahnatasy.id | password |
| Psikolog | budi@rumahnatasy.id | password |
| Psikolog | maya@rumahnatasy.id | password |
| Psikolog | doni@rumahnatasy.id | password |
| Pasien | rina@example.com | password |
| Pasien | fajar@example.com | password |
| Pasien | dewi@example.com | password |
| Pasien | rizki@example.com | password |
| Pasien | putri@example.com | password |

---

*© 2026 Rumah Natasy — E-Konseling*
```

---