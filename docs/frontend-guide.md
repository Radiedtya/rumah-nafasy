# 📄 Frontend Documentation — Rumah Natasy


```markdown
# Rumah Natasy — Frontend Development Guide
## Vue 3 SPA untuk E-Konsultasi Psikologi

> **Versi:** 1.0 | **Backend:** Laravel 13 API | **Last Updated:** Sep 2026
```
---

## 1. Project Overview

Rumah Natasy adalah platform **E-Konsultasi Psikologi** yang menghubungkan pasien dengan psikolog berlisensi secara online via video call/chat.

### 3 User Roles
| Role | Akses |
|------|-------|
| **Pasien** | Browse psikolog → order → bayar → pilih jadwal → konsultasi → review |
| **Psikolog** | Kelola jadwal → terima booking → jalankan konsultasi → catat notes → lihat income |
| **Admin** | Verifikasi psikolog → kelola kategori/harga → kelola transaksi → approve refund → reports |

---

## 2. Tech Stack & Setup

### Dependencies
```bash
npm create vue@latest frontend
# ✓ TypeScript: Yes
# ✓ Vue Router: Yes
# ✓ Pinia: Yes
# ✓ ESLint: Yes
# ✓ Prettier: Yes

cd frontend
npm install

# Core
npm install axios @vueuse/core lucide-vue-next

# Styling
npm install -D tailwindcss @tailwindcss/vite

# Forms & Calendar
npm install @vuepic/vue-datepicker

# Charts (dashboard)
npm install chart.js vue-chartjs

# Notifications
npm install vue-sonner

# Video Call (optional, bisa pake iframe)
# npm install @jitsi/vue-jitsi-meet
```

### Vite Config
`vite.config.ts`:
```ts
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import path from 'path'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: { '@': path.resolve(__dirname, './src') },
  },
  server: {
    port: 8000,
  },
})
```

### Tailwind Config
`src/assets/styles/main.css`:
```css
@import "tailwindcss";

@theme {
  /* Brand colors dari logo Rumah Natasy */
  --color-brand-blue: #4A90D9;
  --color-brand-pink: #E85B9F;
  --color-brand-yellow: #F5C842;
  --color-brand-purple: #8B5FBF;

  /* Warm neutrals */
  --color-cream: #FAF7F2;
  --color-charcoal: #2D2D2D;
  --color-warm-gray: #8B8680;
  --color-light-border: #E8E0D5;

  /* Font */
  --font-sans: "Plus Jakarta Sans", sans-serif;
}
```

### Google Fonts
`index.html`:
```html
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
```

### Environment
`frontend/.env`:
```env
VITE_API_URL=http://localhost:8000/api/v1
VITE_APP_NAME="Rumah Natasy"
VITE_JITSI_DOMAIN=meet.jit.si
```

---

## 3. Brand Identity

### Color Palette
| Token | Hex | Usage |
|-------|-----|-------|
| `brand-blue` | #4A90D9 | Primary buttons, links, header |
| `brand-pink` | #E85B9F | Accent, secondary CTA |
| `brand-yellow` | #F5C842 | Ratings, highlights, badges |
| `brand-purple` | #8B5FBF | Headings accent |
| `cream` | #FAF7F2 | Page background |
| `charcoal` | #2D2D2D | Body text |
| `warm-gray` | #8B8680 | Secondary text |
| `light-border` | #E8E0D5 | Borders, dividers |

### Typography
| Element | Font | Weight | Size |
|---------|------|--------|------|
| Hero heading | Plus Jakarta Sans | Bold (700) | 48-64px |
| Section heading | Plus Jakarta Sans | SemiBold (600) | 24-32px |
| Card title | Plus Jakarta Sans | SemiBold (600) | 18-20px |
| Body text | Plus Jakarta Sans | Regular (400) | 16px |
| Small text | Plus Jakarta Sans | Medium (500) | 14px |

### UI Style
- Border radius: 12px (cards), 8px (buttons/inputs)
- Shadows: `0 2px 8px rgba(0,0,0,0.06)`
- Buttons: rounded, solid primary
- Cards: white bg, soft shadow, 12px radius
- Generous whitespace
- Micro-interactions: hover lift, smooth transitions (200-300ms)
- Icons: Lucide Icons (line style)

---

## 4. API Overview

### Base URL
```
http://localhost:8000/api/v1
```

### Auth
Token-based (Bearer token):
```
Authorization: Bearer <token>
```

### Response Format

**Success:**
```json
{
  "success": true,
  "message": "Success message",
  "data": { ... }
}
```

**Success with pagination:**
```json
{
  "success": true,
  "message": "List message",
  "data": [ ... ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 10,
    "total": 25,
    "from": 1,
    "to": 10,
    "has_more": true
  }
}
```

**Error:**
```json
{
  "success": false,
  "message": "Error message",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

**Common HTTP status codes:**
- 200: Success
- 201: Created
- 401: Unauthorized (token invalid/expired)
- 403: Forbidden (wrong role)
- 404: Not found
- 422: Validation error
- 500: Server error

---

## 5. Auth Endpoints

### Register (Pasien)
```
POST /auth/register
Content-Type: application/json
```
**Request:**
```json
{
  "name": "Budi Test",
  "email": "budi@test.com",
  "phone": "081234567890",
  "password": "password123",
  "password_confirmation": "password123"
}
```
**Response (201):**
```json
{
  "success": true,
  "message": "Registrasi berhasil. Selamat datang di Rumah Natasy!",
  "data": {
    "user": {
      "id": 12,
      "name": "Budi Test",
      "email": "budi@test.com",
      "phone": "081234567890",
      "avatar": null,
      "roles": ["pasien"]
    },
    "token": "1|abcdef123456...",
    "token_type": "Bearer"
  }
}
```

### Login
```
POST /auth/login
Content-Type: application/json
```
**Request:**
```json
{
  "email": "admin@rumahnatasy.id",
  "password": "password",
  "device_name": "web-browser"
}
```
**Response (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 1,
      "name": "Admin Rumah Natasy",
      "email": "admin@rumahnatasy.id",
      "phone": "081234567890",
      "avatar": null,
      "email_verified_at": "...",
      "is_active": true,
      "roles": ["admin"],
      "psikolog_profile": null
    },
    "token": "2|ghijkl789012...",
    "token_type": "Bearer"
  }
}
```

> **Login sebagai psikolog** → response includes `psikolog_profile` object

### Get Me
```
GET /auth/me
Authorization: Bearer <token>
```

### Update Profile
```
PUT /auth/profile
Authorization: Bearer <token>
Content-Type: multipart/form-data
```
**Request (JSON or multipart):**
```json
{
  "name": "Updated Name",
  "phone": "081234567999"
}
```
> Avatar upload: use `multipart/form-data` with `avatar` field (image, max 2MB)
> Password change: include `current_password` + `password` + `password_confirmation`

### Logout
```
POST /auth/logout
Authorization: Bearer <token>
```

---

## 6. Public Endpoints (No Auth)

### List Psikolog
```
GET /public/psikolog?per_page=10&page=1&specialization=klinis-dewasa&search=andi&sort=rating
```
**Query params:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| per_page | int | 10 | Items per page (max 50) |
| page | int | 1 | Page number |
| specialization | string | - | Filter by specialization slug |
| search | string | - | Search by psikolog name |
| sort | string | rating | `rating` / `experience` / `name` |

**Response:**
```json
{
  "success": true,
  "message": "Daftar psikolog terverifikasi",
  "data": [
    {
      "id": 2,
      "name": "dr. Andi Pratama, M.Psi",
      "avatar": null,
      "slug": "dr-andi-pratama-mpsi-xxx",
      "bio": "Psikolog klinis...",
      "specialization": "Klinis Dewasa",
      "specialization_slug": "klinis-dewasa",
      "specialization_icon": "brain",
      "experience_years": 10,
      "license_no": "SIP-10000",
      "education": "S2 Psikologi...",
      "workplace": "Praktik Pribadi",
      "status": "verified",
      "is_available": true,
      "rating_avg": 4.6,
      "total_reviews": 20,
      "total_consultations": 101,
      "custom_rate": null,
      "schedules": [],
      "created_at": "..."
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 10,
    "total": 5,
    "from": 1,
    "to": 5,
    "has_more": false
  }
}
```

### Psikolog Detail
```
GET /public/psikolog/{slug}
```
**Response:** Same as list item but with `schedules` populated:
```json
{
  "data": {
    "schedules": [
      {
        "id": 1,
        "day_of_week": 2,
        "day_name": "Selasa",
        "start_time": "10:00",
        "end_time": "13:00",
        "is_available": true
      }
    ]
  }
}
```

### Psikolog Reviews
```
GET /public/psikolog/{slug}/reviews?per_page=10&page=1
```
**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "pasien_name": "Rina Wijaya",
      "rating": 5,
      "comment": "Konsultasi sangat membantu...",
      "is_published": true,
      "created_at": "2026-09-12T..."
    }
  ],
  "meta": { ... }
}
```

### Specializations
```
GET /public/specializations
```
```json
{
  "data": [
    { "id": 1, "name": "Klinis Dewasa", "slug": "klinis-dewasa", "icon": "brain" },
    { "id": 2, "name": "Anak & Remaja", "slug": "anak-remaja", "icon": "child" },
    { "id": 3, "name": "Pernikahan & Keluarga", "slug": "pernikahan-keluarga", "icon": "heart" }
  ]
}
```

### Categories
```
GET /public/categories
```
```json
{
  "data": [
    { "id": 1, "name": "Siswa", "slug": "siswa", "base_price": 50000.0 },
    { "id": 2, "name": "Mahasiswa", "slug": "mahasiswa", "base_price": 75000.0 },
    { "id": 3, "name": "Umum", "slug": "umum", "base_price": 150000.0 },
    { "id": 4, "name": "Pasangan", "slug": "pasangan", "base_price": 250000.0 },
    { "id": 5, "name": "Keluarga", "slug": "keluarga", "base_price": 300000.0 }
  ]
}
```

### Durations
```
GET /public/durations
```
```json
{
  "data": [
    { "id": 1, "name": "30 Menit", "minutes": 30, "multiplier": 0.5 },
    { "id": 2, "name": "60 Menit", "minutes": 60, "multiplier": 1.0 },
    { "id": 3, "name": "90 Menit", "minutes": 90, "multiplier": 1.5 }
  ]
}
```

---

## 7. Pasien Endpoints (Auth + role:pasien)

### Create Order
```
POST /pasien/orders
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
  "psikolog_id": 2,
  "category_id": 2,
  "duration_id": 2,
  "consultation_type": "video"
}
```
**Response (201):**
```json
{
  "success": true,
  "message": "Pesanan dibuat. Silakan lakukan pembayaran dalam 24 jam.",
  "data": {
    "id": 4,
    "order_number": "RMF-260912-XXXXXX",
    "calculated_price": 75000,
    "consultation_type": "video",
    "status": "pending_payment",
    "expires_at": "2026-09-13T...",
    "psikolog": { "id": 2, "name": "dr. Andi Pratama, M.Psi", "slug": "..." },
    "category": { "id": 2, "name": "Mahasiswa", "base_price": 75000 },
    "duration": { "id": 2, "name": "60 Menit", "minutes": 60, "multiplier": 1.0 }
  }
}
```

### List Orders
```
GET /pasien/orders?per_page=10&status=pending_payment
```

### Order Detail
```
GET /pasien/orders/{order_id}
```
> Response includes `payment` and `booking` (when loaded)

### Cancel Order
```
POST /pasien/orders/{order_id}/cancel
```
> Only works if status is `pending_payment`

### Create Payment
```
POST /pasien/orders/{order_id}/payment
Authorization: Bearer <token>
```
**Response:**
```json
{
  "success": true,
  "message": "Pembayaran dibuat...",
  "data": {
    "payment": { "id": 1, "status": "pending", "amount": 75000 },
    "snap_url": "http://localhost:8000/api/v1/webhooks/midtrans?mock=1&order_id=RMF-...",
    "is_mock": true
  }
}
```
> **Production:** `snap_url` is Midtrans Snap redirect URL
> **Dev (mock):** `snap_url` triggers mock payment

### Check Payment Status
```
GET /pasien/payments/{payment_id}
```

### Get Available Slots
```
GET /pasien/psikolog/{psikolog_id}/slots?date=2026-09-15&duration=60
```
**Response:**
```json
{
  "data": {
    "psikolog": { "id": 2, "name": "dr. Andi Pratama, M.Psi", "slug": "..." },
    "date": "2026-09-15",
    "duration_minutes": 60,
    "available_slots": [
      { "start_time": "10:00", "end_time": "11:00", "is_available": true },
      { "start_time": "11:00", "end_time": "12:00", "is_available": true },
      { "start_time": "12:00", "end_time": "13:00", "is_available": true }
    ],
    "total_slots": 3
  }
}
```

### Create Booking (Pick Schedule)
```
POST /pasien/orders/{order_id}/schedule
Authorization: Bearer <token>
Content-Type: application/json
```
```json
{
  "booking_date": "2026-09-15",
  "start_time": "10:00"
}
```
**Response (201):**
```json
{
  "data": {
    "id": 3,
    "booking_date": "2026-09-15",
    "start_time": "10:00",
    "end_time": "11:00",
    "room_id": "room-uuid-here",
    "status": "confirmed",
    "can_reschedule": true,
    "can_cancel": true,
    "reschedule_count": 0,
    "order": {
      "order_number": "RMF-...",
      "calculated_price": 75000,
      "consultation_type": "video",
      "category_name": "Mahasiswa",
      "duration_name": "60 Menit"
    },
    "psikolog": { "id": 2, "name": "dr. Andi Pratama, M.Psi" }
  }
}
```

### List Bookings
```
GET /pasien/bookings?per_page=10&status=confirmed
```

### Booking Detail
```
GET /pasien/bookings/{booking_id}
```

### Reschedule Booking
```
PUT /pasien/bookings/{booking_id}/reschedule
Content-Type: application/json
```
```json
{
  "booking_date": "2026-09-17",
  "start_time": "14:00",
  "reason": "Jadwal bentrok"
}
```
> Max 2x reschedule, min H-1 (24 hours before)
> Response includes `reschedule_count` and `can_reschedule`

### Cancel Booking
```
POST /pasien/bookings/{booking_id}/cancel
Content-Type: application/json
```
```json
{
  "reason": "Tidak bisa hadir"
}
```
**Response:**
```json
{
  "data": {
    "booking": { "status": "cancelled" },
    "refund": {
      "id": 2,
      "amount": 75000,
      "percentage": 100,
      "status": "pending"
    }
  }
}
```
> Refund tiers: H-3+ = 100%, H-2 = 75%, H-1 = 50%, H-0 = 0%

---

## 8. Psikolog Endpoints (Auth + role:psikolog)

### Dashboard
```
GET /psikolog/dashboard
```
```json
{
  "data": {
    "today_bookings": [...],
    "today_bookings_count": 0,
    "upcoming_bookings_count": 0,
    "total_completed_consultations": 1,
    "monthly_income": 75000,
    "total_income": 75000,
    "rating_avg": 4.5,
    "total_reviews": 20,
    "is_available": true,
    "specialization": "Klinis Dewasa"
  }
}
```

### Schedules CRUD
```
GET    /psikolog/schedules
POST   /psikolog/schedules          { "day_of_week": 1, "start_time": "09:00", "end_time": "12:00" }
PUT    /psikolog/schedules/{id}     { "is_available": false }
DELETE /psikolog/schedules/{id}
```
> `day_of_week`: 0=Ahad, 1=Senin, 2=Selasa, ..., 6=Sabtu

### Bookings
```
GET /psikolog/bookings?status=confirmed&date=2026-09-15
GET /psikolog/bookings/{booking_id}
PUT /psikolog/bookings/{booking_id}/status    { "status": "in_progress" }
```

### Consultations
```
GET  /psikolog/consultations
GET  /psikolog/consultations/{consultation_id}
POST /psikolog/bookings/{booking_id}/consultation/start
POST /psikolog/consultations/{consultation_id}/end
```

### Consultation Notes
```
GET  /psikolog/consultations/{consultation_id}/notes
POST /psikolog/consultations/{consultation_id}/notes    { "content": "Pasien mengeluh..." }
```
> Notes are encrypted in database (UU PDP compliance)

### Profile
```
GET /psikolog/profile
PUT /psikolog/profile    { "bio": "Updated bio", "education": "...", "workplace": "..." }
```

### Income
```
GET /psikolog/income
GET /psikolog/income/report?start_date=2026-09-01&end_date=2026-09-30
```

---

## 9. Admin Endpoints (Auth + role:admin)

### Dashboard
```
GET /admin/dashboard
```
```json
{
  "data": {
    "users": { "total_pasien": 6, "total_psikolog": 5, "verified_psikolog": 5, "pending_psikolog": 0 },
    "orders": { "total": 4, "completed": 1, "cancelled": 2 },
    "revenue": { "total": 75000, "monthly": 75000, "total_refunds": 75000, "net_revenue": 0 },
    "consultations": { "total_completed": 1, "active_bookings": 1 },
    "pending": { "psikolog_verifications": 0, "refunds": 1 },
    "recent_orders": [...],
    "recent_users": [...]
  }
}
```

### Manage Psikolog
```
GET  /admin/psikolog?status=verified&search=andi
GET  /admin/psikolog/{user_id}
PUT  /admin/psikolog/{user_id}/verify
PUT  /admin/psikolog/{user_id}/suspend     { "reason": "Pelanggaran etika" }
PUT  /admin/psikolog/{user_id}/activate
```

### Categories CRUD
```
GET    /admin/categories
POST   /admin/categories     { "name": "Lansia", "description": "...", "base_price": 100000 }
PUT    /admin/categories/{id}    { "base_price": 120000 }
DELETE /admin/categories/{id}
```

### Durations CRUD
```
GET    /admin/durations
POST   /admin/durations     { "name": "45 Menit", "minutes": 45, "multiplier": 0.75 }
PUT    /admin/durations/{id}
DELETE /admin/durations/{id}
```

### Specializations CRUD
```
GET    /admin/specializations
POST   /admin/specializations     { "name": "Klinis Dewasa", "description": "...", "icon": "brain" }
PUT    /admin/specializations/{id}
DELETE /admin/specializations/{id}
```

### Transactions
```
GET /admin/transactions?status=completed&search=RMF&start_date=2026-09-01&end_date=2026-09-30
GET /admin/transactions/{order_id}
```

### Refunds
```
GET /admin/refunds?status=pending
PUT /admin/refunds/{refund_id}/approve
PUT /admin/refunds/{refund_id}/reject    { "reason": "Tidak memenuhi syarat" }
```

### Reports
```
GET /admin/reports/transactions?start_date=2026-09-01&end_date=2026-09-30
GET /admin/reports/psikolog
GET /admin/reports/bookings?start_date=2026-09-01&end_date=2026-09-30
```

---

## 10. Webhook Endpoints (No Auth, dev only)

### Mock Payment (Development only)
```
GET /webhooks/midtrans?mock=1&order_id=RMF-260912-XXXXXX
```
> Simulates payment success. Updates order status to `paid`.

---

## 11. User Flows

### Pasien Flow
```
1. Landing Page → [Daftar/Login]
2. Browse Psikolog → [Psikolog Detail]
3. [Bayar Sekarang] → Select Category + Duration → See Price
4. [Checkout] → Order created (pending_payment)
5. [Pay] → Midtrans Snap → Payment success
6. [Pilih Jadwal] → Calendar → Available Slots → [Confirm]
7. Booking Confirmed → Dashboard
8. H-1 → WA Reminder | H-1 hour → WA Reminder
9. [Join Konsultasi] → Jitsi Video Call
10. [Start/End] (psikolog side)
11. [Beri Review]
```

### Psikolog Flow
```
1. Login → Dashboard (stats)
2. [Kelola Jadwal] → Add/Edit/Delete schedules
3. [Booking Masuk] → View bookings
4. [Start Consultation] → Video call starts
5. [Add Notes] → Encrypted notes
6. [End Consultation] → Booking completed
7. [Income] → View earnings
```

### Admin Flow
```
1. Login → Dashboard (platform stats)
2. [Kelola Psikolog] → Verify/Suspend/Activate
3. [Kelola Kategori] → CRUD categories & prices
4. [Transaksi] → View all transactions
5. [Refund] → Approve/Reject refund requests
6. [Reports] → View reports (transactions, psikolog, bookings)
```

---

## 12. Frontend Structure

```
src/
├── assets/
│   ├── images/
│   │   ├── logo.svg
│   │   └── hero-illustration.svg
│   └── styles/
│       └── main.css
│
├── components/
│   ├── ui/
│   │   ├── BaseButton.vue        (variants: primary, secondary, ghost, danger)
│   │   ├── BaseCard.vue          (wrapper: shadow, radius)
│   │   ├── BaseBadge.vue         (status badges)
│   │   ├── BaseInput.vue         (text, password, with validation)
│   │   ├── BaseModal.vue         (dialog, confirm)
│   │   ├── BaseTable.vue         (data table with pagination)
│   │   ├── BasePagination.vue    (page navigation)
│   │   ├── BaseEmptyState.vue    (empty data illustration + text)
│   │   ├── BaseLoading.vue       (spinner, skeleton)
│   │   └── BaseToast.vue         (success/error notifications)
│   │
│   ├── layout/
│   │   ├── TheNavbar.vue         (public: logo, menu, login/register)
│   │   ├── TheFooter.vue         (links, social, copyright)
│   │   ├── DashboardLayout.vue   (sidebar + content for pasien/psikolog/admin)
│   │   └── AuthLayout.vue        (centered card for login/register)
│   │
│   ├── landing/
│   │   ├── HeroSection.vue
│   │   ├── StatsBar.vue
│   │   ├── HowItWorks.vue
│   │   ├── FeaturedPsikolog.vue
│   │   ├── PsikologCard.vue
│   │   ├── CategoriesPricing.vue
│   │   ├── WhyChooseUs.vue
│   │   ├── Testimonials.vue
│   │   ├── FAQSection.vue
│   │   └── CTASection.vue
│   │
│   ├── psikolog/
│   │   ├── PsikologList.vue
│   │   ├── PsikologFilter.vue
│   │   ├── PsikologDetail.vue
│   │   └── SchedulePreview.vue
│   │
│   ├── booking/
│   │   ├── CheckoutForm.vue       (select category + duration)
│   │   ├── OrderSummary.vue       (price breakdown)
│   │   ├── SchedulePicker.vue     (calendar + slot selection)
│   │   ├── BookingCard.vue        (upcoming/past booking card)
│   │   └── RescheduleModal.vue    (reschedule form)
│   │
│   ├── consultation/
│   │   ├── VideoCall.vue          (Jitsi embed)
│   │   └── ConsultationNotes.vue  (add/view notes)
│   │
│   └── dashboard/
│       ├── StatsCard.vue          (metric card)
│       ├── RevenueChart.vue      (income chart)
│       ├── BookingsTable.vue     (bookings list)
│       └── RecentActivity.vue    (recent orders/users)
│
├── views/
│   ├── public/
│   │   ├── LandingPage.vue
│   │   ├── PsikologListPage.vue
│   │   └── PsikologDetailPage.vue
│   │
│   ├── auth/
│   │   ├── LoginPage.vue
│   │   └── RegisterPage.vue
│   │
│   ├── pasien/
│   │   ├── DashboardPage.vue
│   │   ├── CheckoutPage.vue
│   │   ├── PaymentPage.vue
│   │   ├── PaymentSuccessPage.vue
│   │   ├── BookingPage.vue       (pilih jadwal)
│   │   ├── BookingConfirmedPage.vue
│   │   ├── ConsultationPage.vue  (video call)
│   │   ├── RiwayatPage.vue
│   │   └── ProfilePage.vue
│   │
│   ├── psikolog/
│   │   ├── DashboardPage.vue
│   │   ├── SchedulesPage.vue
│   │   ├── BookingsPage.vue
│   │   ├── ConsultationsPage.vue
│   │   ├── IncomePage.vue
│   │   └── ProfilePage.vue
│   │
│   └── admin/
│       ├── DashboardPage.vue
│       ├── PsikologManagePage.vue
│       ├── CategoriesPage.vue
│       ├── TransactionsPage.vue
│       ├── RefundsPage.vue
│       └── ReportsPage.vue
│
├── stores/
│   ├── auth.ts          (login, register, logout, user, token)
│   ├── psikolog.ts      (list, detail, filters)
│   ├── booking.ts       (order, payment, booking flow)
│   ├── schedule.ts      (available slots)
│   └── notification.ts  (toast, alert)
│
├── router/
│   └── index.ts         (routes, guards, lazy loading)
│
├── services/
│   ├── api.ts           (axios instance, interceptors)
│   ├── auth.service.ts
│   ├── psikolog.service.ts
│   ├── order.service.ts
│   ├── booking.service.ts
│   └── admin.service.ts
│
├── composables/
│   ├── useAuth.ts       (auth state, guards)
│   ├── useApi.ts        (fetch wrapper, loading, error)
│   └── useToast.ts      (notifications)
│
├── types/
│   ├── user.ts
│   ├── psikolog.ts
│   ├── order.ts
│   ├── booking.ts
│   └── api.ts
│
├── utils/
│   ├── format.ts        (currency, date, phone formatter)
│   └── validators.ts    (form validation helpers)
│
├── App.vue
└── main.ts
```

---

## 13. Router Structure

```ts
const routes = [
  // Public
  { path: '/', component: () => import('@/views/public/LandingPage.vue') },
  { path: '/psikolog', component: () => import('@/views/public/PsikologListPage.vue') },
  { path: '/psikolog/:slug', component: () => import('@/views/public/PsikologDetailPage.vue') },
  { path: '/cara-kerja', component: () => import('@/views/public/HowItWorksPage.vue') },
  { path: '/harga', component: () => import('@/views/public/PricingPage.vue') },
  { path: '/faq', component: () => import('@/views/public/FAQPage.vue') },

  // Auth
  { path: '/login', component: () => import('@/views/auth/LoginPage.vue'), meta: { guest: true } },
  { path: '/register', component: () => import('@/views/auth/RegisterPage.vue'), meta: { guest: true } },

  // Pasien (auth + role:pasien)
  {
    path: '/dashboard',
    component: () => import('@/views/pasien/DashboardPage.vue'),
    meta: { auth: true, role: 'pasien' }
  },
  { path: '/checkout/:psikologId', meta: { auth: true, role: 'pasien' } },
  { path: '/payment/:orderId', meta: { auth: true, role: 'pasien' } },
  { path: '/payment/success/:orderId', meta: { auth: true, role: 'pasien' } },
  { path: '/booking/:orderId', meta: { auth: true, role: 'pasien' } },
  { path: '/booking/confirmed/:bookingId', meta: { auth: true, role: 'pasien' } },
  { path: '/consultation/:bookingId', meta: { auth: true, role: 'pasien' } },
  { path: '/riwayat', meta: { auth: true, role: 'pasien' } },
  { path: '/profile', meta: { auth: true } },

  // Psikolog (auth + role:psikolog)
  {
    path: '/psikolog-panel',
    children: [
      { path: 'dashboard', meta: { auth: true, role: 'psikolog' } },
      { path: 'schedules', meta: { auth: true, role: 'psikolog' } },
      { path: 'bookings', meta: { auth: true, role: 'psikolog' } },
      { path: 'consultations', meta: { auth: true, role: 'psikolog' } },
      { path: 'income', meta: { auth: true, role: 'psikolog' } },
      { path: 'profile', meta: { auth: true, role: 'psikolog' } },
    ]
  },

  // Admin (auth + role:admin)
  {
    path: '/admin-panel',
    children: [
      { path: 'dashboard', meta: { auth: true, role: 'admin' } },
      { path: 'psikolog', meta: { auth: true, role: 'admin' } },
      { path: 'categories', meta: { auth: true, role: 'admin' } },
      { path: 'transactions', meta: { auth: true, role: 'admin' } },
      { path: 'refunds', meta: { auth: true, role: 'admin' } },
      { path: 'reports', meta: { auth: true, role: 'admin' } },
    ]
  },
]
```

### Route Guards
```ts
router.beforeEach(async (to, from, next) => {
  const auth = useAuthStore()

  if (to.meta.auth && !auth.isAuthenticated) {
    return next('/login')
  }

  if (to.meta.role && auth.user?.roles?.[0] !== to.meta.role) {
    // Redirect to correct dashboard
    const role = auth.user?.roles?.[0]
    if (role === 'pasien') return next('/dashboard')
    if (role === 'psikolog') return next('/psikolog-panel/dashboard')
    if (role === 'admin') return next('/admin-panel/dashboard')
    return next('/')
  }

  if (to.meta.guest && auth.isAuthenticated) {
    // Already logged in, redirect to dashboard
    return next('/dashboard')
  }

  next()
})
```

---

## 14. Pinia Stores

### Auth Store (`stores/auth.ts`)
```ts
export const useAuthStore = defineStore('auth', () => {
  const token = ref(localStorage.getItem('token') || '')
  const user = ref<User | null>(null)

  const isAuthenticated = computed(() => !!token.value)
  const role = computed(() => user.value?.roles?.[0] || null)

  async function login(email: string, password: string) {
    const res = await api.post('/auth/login', { email, password })
    token.value = res.data.data.token
    user.value = res.data.data.user
    localStorage.setItem('token', token.value)
  }

  async function register(data: RegisterData) {
    const res = await api.post('/auth/register', data)
    token.value = res.data.data.token
    user.value = res.data.data.user
    localStorage.setItem('token', token.value)
  }

  async function fetchUser() {
    const res = await api.get('/auth/me')
    user.value = res.data.data
  }

  async function logout() {
    await api.post('/auth/logout')
    token.value = ''
    user.value = null
    localStorage.removeItem('token')
  }

  return { token, user, isAuthenticated, role, login, register, fetchUser, logout }
})
```

### API Service (`services/api.ts`)
```ts
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
})

// Add token to every request
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

// Handle 401 → redirect to login
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

export default api
```

---

## 15. Jitsi Video Call Integration

### How It Works
Backend provides `room_id` in booking (e.g., `"room-2f4b0ec8-2523-41ff-9723-069cd6aa83bb"`).

Frontend embeds Jitsi using this `room_id`.

### Option 1: iframe (Simplest)
```vue
<template>
  <iframe
    :src="`https://${jitsiDomain}/${booking.room_id}`"
    allow="camera; microphone; fullscreen; display-capture"
    style="width: 100%; height: 100%; border: 0;"
  />
</template>

<script setup>
const jitsiDomain = import.meta.env.VITE_JITSI_DOMAIN || 'meet.jit.si'
</script>
```

### Option 2: Vue Component (More control)
```bash
npm install @jitsi/vue-jitsi-meet
```
```vue
<template>
  <JitsiMeet
    :domain="jitsiDomain"
    :room="booking.room_id"
    @on-participant-joined="onJoin"
  />
</template>
```

### Jitsi Domain
| Environment | Domain |
|-------------|--------|
| Development | `meet.jit.si` (free, public) |
| Production | `meet.rumahnatasy.id` (self-hosted) |

### Show "Join" Button Logic
```ts
// Only show join button when:
// 1. Booking status is confirmed
// 2. Consultation time is within ±15 minutes of start_time
const canJoin = computed(() => {
  if (booking.value?.status !== 'confirmed') return false

  const now = new Date()
  const consultationTime = new Date(`${booking.value.booking_date} ${booking.value.start_time}`)
  const diffMinutes = (consultationTime.getTime() - now.getTime()) / 60000

  return Math.abs(diffMinutes) <= 15 // 15 min before or after
})
```

---

## 16. Mock Data (For Development)

If public API isn't ready yet, use static data:

```ts
// src/data/mock.ts
export const mockPsikolog = [
  { id: 2, name: 'dr. Andi Pratama, M.Psi', specialization: 'Klinis Dewasa', rating_avg: 4.6, total_reviews: 20, slug: 'dr-andi-pratama' },
  { id: 3, name: 'dr. Sari Dewi, M.Psi', specialization: 'Anak & Remaja', rating_avg: 4.8, total_reviews: 35, slug: 'dr-sari-dewi' },
  { id: 4, name: 'dr. Budi Santoso, M.Psi', specialization: 'Pernikahan & Keluarga', rating_avg: 5.0, total_reviews: 50, slug: 'dr-budi-santoso' },
]

export const mockCategories = [
  { id: 1, name: 'Siswa', base_price: 50000 },
  { id: 2, name: 'Mahasiswa', base_price: 75000 },
  { id: 3, name: 'Umum', base_price: 150000 },
]

export const mockDurations = [
  { id: 1, name: '30 Menit', minutes: 30, multiplier: 0.5 },
  { id: 2, name: '60 Menit', minutes: 60, multiplier: 1.0 },
  { id: 3, name: '90 Menit', minutes: 90, multiplier: 1.5 },
]
```

---

## 17. Test Accounts

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

## 18. Pricing Calculator (Frontend)

```ts
function calculatePrice(category: Category, duration: Duration, customRate?: number | null): number {
  const base = customRate ? customRate : category.base_price
  return base * duration.multiplier
}

// Example:
// Mahasiswa (75.000) × 60 min (1.0x) = 75.000
// Siswa (50.000) × 30 min (0.5x) = 25.000
```

---

## 19. Status Colors (UI)

### Order Status
| Status | Color | Label (ID) |
|--------|-------|------------|
| pending_payment | amber | Menunggu Pembayaran |
| paid | blue | Sudah Dibayar |
| scheduled | purple | Terjadwal |
| completed | green | Selesai |
| cancelled | rose | Dibatalkan |
| refunded | orange | Dikembalikan |
| expired | gray | Kedaluwarsa |

### Booking Status
| Status | Color | Label (ID) |
|--------|-------|------------|
| confirmed | green | Dikonfirmasi |
| in_progress | blue | Sedang Berlangsung |
| completed | green | Selesai |
| cancelled | rose | Dibatalkan |
| rescheduled | amber | Dijadwalkan Ulang |

---

## 20. Development Priority

### Phase 1 (Must have)
- [ ] Landing page (hero, how it works, psikolog, pricing, FAQ)
- [ ] Login & Register page
- [ ] Psikolog listing & detail page
- [ ] Checkout flow (select category + duration → order)
- [ ] Payment page (Midtrans redirect)
- [ ] Payment success → pick schedule
- [ ] Pasien dashboard (upcoming, riwayat)

### Phase 2 (Should have)
- [ ] Psikolog dashboard
- [ ] Psikolog schedule management
- [ ] Psikolog booking management
- [ ] Consultation page (Jitsi embed)
- [ ] Psikolog consultation notes

### Phase 3 (Nice to have)
- [ ] Admin dashboard
- [ ] Admin psikolog management
- [ ] Admin transaction & refund management
- [ ] Admin reports (charts)
- [ ] Profile management (all roles)

---

*© 2026 Rumah Natasy — E-Konseling*