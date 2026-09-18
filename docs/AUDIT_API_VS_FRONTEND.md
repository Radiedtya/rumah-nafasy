  #  Laporan Audit: Backend API vs Frontend — Rumah Natasy

  > Tanggal audit: 2026-09-18
  > By @davingm
  > Scope: seluruh controller, service, model, scheduler, dan route backend (`/api/v1`) dibandingkan dengan seluruh halaman frontend (pasien, psikolog, admin, publik).
  > Sifat laporan: **analisis & rekomendasi saja — tidak ada perubahan kode.**

  ---

  ## 1. Ringkasan Eksekutif

  Backend sudah cukup matang untuk sebuah MVP platform konseling: alur e-commerce order → pembayaran → jadwal → konsultasi → review lengkap, ada scheduler (reminder WA, auto-expire, refund), catatan klinis terenkripsi, dan notifikasi WhatsApp via queue.

  Namun audit menemukan:

  - **14 endpoint/fitur backend yang belum (atau baru sebagian) terpakai di frontend** — termasuk dua fitur besar: **ulasan (review)** dan **pendapatan psikolog (pendapatan)**.
  - **3 bug/logika bisnis yang salah** di backend (bug parameter slot, webhook dapat dipalsukan siapa pun, dead code yang membingungkan).
  - **2 kebocoran privasi** yang melanggar standar platform psikologi (nama pasien terekspos publik; **aturan jam sebelum sesi untuk chat** tidak ada).
  - **Beberapa logika bisnis yang kurang praktis** — dengan pemecahan masalah yang diusulkan, tanpa menulis kode.

  **Estimasi dampak:** gap terbesar ada di **sisi psikolog** (tidak bisa melihat pendapatan detail) dan **sisi siklus hidup pasca-sesi** (tidak ada review → tidak ada loop kepercayaan → rating di katalog statis).

  ---

  ## 2. Peta Endpoint: Status Integrasi Frontend

  Legenda: ✅ terpakai penuh · 🟡 terpakai sebagian · ❌ belum terpakai · ⚠️ terpakai dengan cacat

  ### 2.1 Auth

  | Endpoint | Metode | Fungsi | Status | Catatan |
  |---|---|---|---|---|
  | `/auth/register` | POST | Registrasi + Turnstile | ✅ | Halaman Register ada |
  | `/auth/login` | POST | Login Sanctum | ✅ | Support `?redirect=` sudah ditambahkan |
  | `/auth/logout` | POST | Revoke token | ✅ | Dipakai `UserMenu` |
  | `/auth/me` | GET | Profil + bootstrap sesi | ✅ | `fetchMe()` |
  | `/auth/profile` | PUT | Update nama/telepon | ✅ | Halaman Profil |
  | `/auth/profile/avatar` | POST/DELETE | Upload/hapus avatar | ✅ | Halaman Profil |

  ### 2.2 Publik (tanpa login)

  | Endpoint | Metode | Fungsi | Status | Catatan |
  |---|---|---|---|---|
  | `/public/psikolog` | GET | Katalog psikolog | ✅ | Halaman Cari Psikolog |
  | `/public/psikolog/{slug}` | GET | Detail psikolog + jadwal | ❌ | **Belum ada halaman detail psikolog**; wizard booking mengambil detail ini tapi tidak menampilkan bio, pendidikan, jadwal mingguan, atau rating |
  | `/public/psikolog/{slug}/reviews` | GET | Review publik | ❌ | **Seluruh fitur review tidak ada di frontend** (lihat §4.1) |
  | `/public/specializations` | GET | Daftar spesialisasi | ✅ | Filter katalog |
  | `/public/specializations/{slug}` | GET | Detail spesialisasi | ❌ | Tidak ada halaman kategori/spesialisasi |
  | `/public/categories` | GET | Kategori klien + harga | ✅ | Langkah 1 wizard booking |
  | `/public/durations` | GET | Durasi + multiplier | ✅ | Langkah 1 wizard booking |

  ### 2.3 Pasien

  | Endpoint | Metode | Fungsi | Status | Catatan |
  |---|---|---|---|---|
  | `/pasien/orders` | GET | Daftar order | 🟡 | Hanya dipakai di Overview; **tidak ada halaman riwayat order** dengan filter status & paginasi yang didukung backend |
  | `/pasien/orders` | POST | Buat order | ✅ | Langkah 1 wizard |
  | `/pasien/orders/{id}` | GET | Detail order | ❌ | Tidak dipakai — wizard menyimpan order di Pinia; **refresh di langkah pembayaran membuat wizard di-reset ke langkah 1** meski order sudah dibayar |
  | `/pasien/orders/{id}/cancel` | POST | Batalkan order pending | ❌ | Pasien tidak bisa membatalkan order yang belum dibayar (order lalu menunggu expire 24 jam) |
  | `/pasien/orders/{id}/payment` | POST | Buat Snap payment | ⚠️ | Terpakai, tapi **`snap_url` diabaikan** — frontend selalu pakai jalur mock webhook (lihat §4.2) |
  | `/pasien/payments/{id}` | GET | Status pembayaran | ❌ | Tidak dipakai; frontend tidak bisa menampilkan status pembayaran aktual (pending/sukses/gagal) |
  | `/pasien/psikolog/{id}/slots` | GET | Slot tersedia | ⚠️ | Terpakai tapi **dengan bug parameter** (lihat §3.1) |
  | `/pasien/bookings` | GET | Daftar booking | ✅ | Halaman Sesi Saya |
  | `/pasien/bookings/{id}` | GET | Detail booking | ❌ | Halaman Sesi hanya memakai data list; tidak ada detail (riwayat reschedule, catatan) |
  | `/pasien/bookings/{id}/reschedule` | PUT | Reschedule (maks 2×, H-1) | ✅ | Modal reschedule |
  | `/pasien/bookings/{id}/cancel` | POST | Batal + hitung refund | 🟡 | Terpakai; respons berisi **jumlah & persentase refund yang dihitung backend, tetapi frontend tidak menampilkannya** ke user |
  | `/bookings/{id}/meeting` | GET | Info meeting Jitsi + JWT | ⚠️ | **Tidak dipakai** — frontend masih pakai link `meet.jit.si/{room_id}` langsung tanpa JWT, dan **tanpa cek status/jadwal** (lihat §5.1) |

  ### 2.4 Psikolog

  | Endpoint | Metode | Fungsi | Status | Catatan |
  |---|---|---|---|---|
  | `/psikolog/dashboard` | GET | Statistik harian/pendapatan | 🟡 | Dipakai Overview; `total_income`, `today_bookings` (list lengkap) tidak ditampilkan |
  | `/psikolog/schedules` CRUD | GET/POST/PUT/DELETE | Jadwal praktek | ✅ | Halaman Jadwal Praktek |
  | `/psikolog/bookings` | GET | Antrean booking | ✅ | Halaman Konsultasi |
  | `/psikolog/bookings/{id}/status` | PUT | Konfirmasi/selesai/no-show | ❌ | **Psikolog tidak bisa menandai no-show/batal dari UI**; status hanya berubah otomatis via start/end konsultasi |
  | `/psikolog/consultations` | GET | Daftar konsultasi | ❌ | Halaman Konsultasi pakai `psikolog/bookings`, bukan daftar konsultasi |
  | `/psikolog/consultations/{id}` | GET | Detail konsultasi | ❌ | Tidak dipakai |
  | `/psikolog/consultations/{id}/end` | POST | Akhiri sesi | ✅ | Halaman Konsultasi |
  | `/psikolog/consultations/{id}/notes` | GET/POST | Catatan klinis | ✅ | Modal catatan (enkripsi AES) |
  | `/psikolog/profile` | GET/PUT | Profil profesional | ❌ | **Psikolog tidak bisa mengedit bio, tarif kustom, atau mematikan ketersediaan** — hanya admin bisa |
  | `/psikolog/income` | GET | Ringkasan pendapatan | ❌ | **Tidak ada halaman Pendapatan** |
  | `/psikolog/income/report` | GET | Laporan per periode | ❌ | Tidak ada halaman laporan |

  ### 2.5 Admin

  | Endpoint | Metode | Fungsi | Status | Catatan |
  |---|---|---|---|---|
  | `/admin/dashboard` | GET | Statistik + revenue | ❌ | **Admin Overview masih menampilkan dashboard psikolog/pasien generik**; data revenue admin tidak tampil |
  | `/admin/psikolog` CRUD + verify/suspend/activate | — | Kelola psikolog | ✅ | Halaman Kelola Psikolog |
  | `/admin/bookings` | GET | Semua jadwal | ✅ | Halaman Semua Jadwal |
  | `/admin/bookings/{id}/status` | PUT | Paksa status | ❌ | Tidak ada tombol di Semua Jadwal |
  | `/admin/consultations` | GET | Semua konsultasi | ✅ | Halaman Semua Konsultasi |
  | `/admin/consultations/{id}` | GET | Detail konsultasi | ❌ | Kartu tidak bisa diklik ke detail |
  | `/admin/categories` CRUD | — | Kelola kategori klien | ❌ | **Tidak ada halaman** — harga & kategori tidak bisa dikelola |
  | `/admin/durations` CRUD | — | Kelola durasi | ❌ | Tidak ada halaman |
  | `/admin/specializations` CRUD | — | Kelola spesialisasi | 🟡 | Hanya dibaca (dropdown di Kelola Psikolog); tidak ada halaman kelola |
  | `/admin/transactions` | GET | Daftar transaksi | ❌ | Tidak ada halaman Transaksi |
  | `/admin/transactions/{id}` | GET | Detail transaksi | ❌ | — |
  | `/admin/refunds` | GET | Daftar refund | ❌ | **Refund tidak bisa disetujui/ditolak admin** — refund pasien tersangkut "pending" selamanya |
  | `/admin/refunds/{id}/approve\|reject` | PUT | Proses refund | ❌ | Sama |
  | `/admin/reports/*` | GET | Laporan transaksi/psikolog/booking | ❌ | Tidak ada halaman Laporan |

  ### 2.6 Webhook & Scheduler (internal, tidak untuk frontend)

  | Item | Status | Catatan |
  |---|---|---|
  | `/webhooks/midtrans` | ⚠️ | Terpakai sebagai **mock pembayaran**, tapi ada masalah keamanan (lihat §3.2) |
  | `slots:release-locked` (tiap menit) | ✅ internal | — |
  | `payments:sync-status` (5 menit) | ✅ internal | Expire order >24 jam |
  | `reminders:tomorrow` / `reminders:one-hour` (H-1, H-1 jam) | ✅ internal | WA reminder |
  | `orders:no-schedule-reminder` (H+3/H+6) | ✅ internal | — |
  | `orders:auto-expire` (refund 100% otomatis) | ✅ internal | — |
  | `reports:daily-admin` | ✅ internal | — |

  ---

  ## 3. 🚨 Bug & Risiko Keamanan (perlu perbaikan backend)

  ### 3.1 BUG — Parameter slot salah nama → durasi selalu 60 menit

  - **Backend** (`Pasien\BookingController::availableSlots`) memvalidasi `duration` (`in:30,60,90`).
  - **Frontend** mengirim `duration_minutes=...`.
  - Akibatnya `duration` selalu fallback ke `60`: slot 30 menit & 90 menit yang ditampilkan frontend **bukan slot sesungguhnya**. Booking 30 menit tetap dihitung konflik terhadap slot 60 menit → slot yang tampil bisa tidak benar-benar valid.
  - **Perbaikan disarankan:** samakan nama parameter (pilih salah satu), atau terima kedua nama di backend. Prioritas: tinggi (mempengaruhi kebenaran data jadwal).

  ### 3.2 KEAMANAN — Webhook mock dapat dipanggil publik tanpa autentikasi

  - `POST/GET /webhooks/midtrans?mock=1&order_id=...` **tidak butuh login** (by design untuk callback Midtrans), dan mode mock aktif selama Midtrans keys belum diisi. Artinya **siapa pun di internet bisa menandai order mana pun sebagai LUNAS** dengan satu GET request.
  - **Perbaikan disarankan:**
    1. Lindungi endpoint mock dengan flag env `APP_MOCK_PAYMENT=true` (nonaktif di produksi), **dan**
    2. Batasi ke user pemilik order (cek `pasien_id === auth user`) — endpoint ini dipanggil dari frontend, bukan dari Midtrans, jadi seharusnya bukan route publik, **atau** pindahkan ke route ter-autentikasi `pasien/orders/{order}/pay/mock`.
    3. Saat produksi (Midtrans configured): verifikasi signature `Midtrans\Notification` (sudah otomatis via SDK) — pastikan `APP_DEBUG=false` dan keys terisi sebelum rilis.

  ### 3.3 Dead code yang berbahaya kalau diaktifkan

  - `PaymentController::mockSuccess()` **selalu melempar 403** dengan pesan kontradiktif ("hanya untuk development" tapi juga "hanya tersedia saat Midtrans tidak dikonfigurasi" — dua kondisi bertentangan) dan **tidak terdaftar di routes**. Kode mati yang kelihatannya fitur.
  - **Perbaikan:** hapus, atau implementasikan betulan sebagai pengganti webhook mock (lihat §3.2 poin 2).

  ### 3.4 Logika `no_show` tidak pernah terjadi otomatis

  - Status `no_show` ada di enum validasi (`admin/bookings/{id}/status`, psikolog updateStatus) tapi tidak ada mekanisme apa pun yang menandainya (scheduler hanya mengirim reminder). Psikolog memang bisa set manual via API, tapi **tidak ada UI** (lihat tabel 2.4).
  - **Perbaikan:** tambahkan aksi "Tandai Tidak Hadir" di halaman Konsultasi psikolog (dan admin), atau command scheduler yang menandai booking lewat waktu tanpa start konsultasi.

  ---

  ## 4. Fitur Backend yang Belum Dipakai Frontend (Gap Fungsional)

  ### 4.1 🟢 Siklus review — belum ada sama sekali (dampak terbesar)

  Backend sudah lengkap: `Review` model + boot auto-update rating psikolog, endpoint publik `/public/psikolog/{slug}/reviews`, plus relasi `booking.reviews`. Frontend: **tidak ada satu pun UI review** (memberi, menampilkan, atau memoderasi).

  Konsekuensi bisnis: `rating_avg` di katalog = angka seed dari seeder yang **tidak akan pernah berubah**; testimoni rumah sakit tidak bisa hidup; standar platform psikologi umumnya mensyaratkan ulasan terverifikasi (hanya klien yang benar-benar sesi) — dan backend sudah mendukung itu karena review ter-tie ke `booking_id`.

  Yang perlu dibangun (3 bagian):
  1. **Sisi pasien:** setelah konsultasi `completed`, tampilkan kartu "Beri Ulasan" di Sesi Saya (rating 1–5 + komentar), POST ke endpoint baru yang perlu dibuat (belum ada endpoint pasien untuk membuat review — backend hanya punya read publik; **ini gap backend juga**).
  2. **Sisi publik:** daftar review di halaman detail psikolog.
  3. **Sisi admin/moderasi:** daftar review + tombol publish/unpublish (kolom `is_published` sudah ada).

  ### 4.2 🟠 Pembayaran real Midtrans belum terintegrasi di UI

  - Backend mengembalikan `snap_url` (Midtrans Snap) + `is_mock`; frontend **mengabaikannya** dan selalu memanggil webhook mock — artinya platform ini **belum bisa menerima uang sungguhan**.
  - Yang perlu dibangun: langkah 2 wizard menampilkan **tombol "Bayar Sekarang"** yang membuka `snap_url` (popup Snap.js / redirect), lalu polling `GET /pasien/payments/{id}` (atau listener callback) untuk mendeteksi status `success` → lanjut ke langkah 3. Mode mock dipertahankan hanya sebagai fallback dev (`is_mock: true`).

  ### 4.3 Daftar lengkap gap per halaman (checklist kandidat pekerjaan)

  | # | Gap | Peran | Endpoint yang belum dipakai | Prioritas |
  |---|---|---|---|---|
  | 1 | Halaman detail psikolog publik (`bio`, pendidikan, jadwal mingguan, review, CTA booking) | Publik | `public/psikolog/{slug}`, `public/psikolog/{slug}/reviews` | Tinggi |
  | 2 | Halaman Pendapatan psikolog (ringkasan + laporan per periode) | Psikolog | `psikolog/income`, `psikolog/income/report` | Tinggi |
  | 3 | Admin: halaman Transaksi + Refund (approve/reject) | Admin | `admin/transactions`, `admin/refunds` + approve/reject | Tinggi |
  | 4 | Admin: halaman Overview sendiri (revenue, pending verifikasi) | Admin | `admin/dashboard` | Tinggi |
  | 5 | Admin: kelola Kategori/Durasi/Spesialisasi | Admin | `admin/categories|durations|specializations` | Sedang |
  | 6 | Psikolog: edit profil profesional (bio, tarif kustom, ketersediaan) | Psikolog | `psikolog/profile` | Tinggi |
  | 7 | Psikolog: aksi status booking (konfirmasi / no-show / batal) | Psikolog | `psikolog/bookings/{id}/status` | Sedang |
  | 8 | Pasien: riwayat order + batalkan order pending | Pasien | `pasien/orders` (dengan filter), `pasien/orders/{id}/cancel` | Sedang |
  | 9 | Pasien: detail booking (riwayat reschedule) | Pasien | `pasien/bookings/{id}` | Rendah |
  | 10 | Wizard: pulihkan state setelah refresh via detail order | Pasien | `pasien/orders/{id}` | Sedang |
  | 11 | Pembayaran Snap asli + polling status payment | Pasien | `snap_url`, `pasien/payments/{id}` | **Kritis untuk rilis** |
  | 12 | Meeting: pakai `booking/meeting` (JWT + validasi status) | Kedua | `bookings/{id}/meeting` | Sedang |
  | 13 | Review: beri ulasan (pasien), tampil (publik), moderasi (admin) | Semua | endpoint baru + `public/.../reviews` | Tinggi |
  | 14 | Admin: aksi paksa status jadwal; detail konsultasi klik-ke-detail | Admin | `admin/bookings/{id}/status`, `admin/consultations/{id}` | Rendah |

  ---

  ## 5. Audit Standar Platform Psikologi (Privasi, Etika, Keamanan)

  ### 5.1 🔴 Ruang meeting tanpa proteksi jadwal/identitas

  Frontend membuat link `https://meet.jit.si/{room_id}` langsung di banyak tempat. Masalah:

  - `meet.jit.si` publik — **siapa pun yang tahu room_id bisa masuk**. `room_id` terekspos ke pasien dan psikolog (dan di booking list admin). Backend sudah menyediakan `GET /bookings/{booking}/meeting` yang: (a) memvalidasi user adalah peserta booking, (b) memvalidasi status `confirmed/in_progress`, (c) mengeluarkan JWT dengan fitur recording/livestreaming **dimatikan** — persis standar yang dibutuhkan platform terapi. Frontend belum memakainya.
  - **Rekomendasi:** semua tombol "Masuk Ruang" memanggil endpoint meeting, dan render iframe/embed Jitsi dengan token, bukan link mentah. Tambahkan juga pengecekan waktu (mis. boleh masuk 15 menit sebelum jadwal) di backend agar ruangan tidak bisa diakses minggu sebelum sesi.
  - **Catatan etika:** matikan recording (sudah) dan tampilkan disclaimer "sesi tidak direkam" — penting untuk rasa aman klien.

  ### 5.2 🔴 Nama lengkap pasien terekspos di review publik

  `ReviewResource` mengembalikan `pasien_name` utuh dan akan ditampilkan publik per `/public/psikolog/{slug}/reviews`. Standar platform kesehatan mental: identitas klien **tidak boleh** tampil di konten publik tanpa persetujuan eksplisit.

  **Rekomendasi:** ganti ke nama tersamar (mis. "Rina W." / "R••• W••••") atau inisial + inisial kota; idealnya backend yang menyamarkan (jangan bergantung frontend). Tambahkan juga opt-in saat pasien menulis review ("tampilkan nama saya sebagai …").

  ### 5.3 🔴 Tidak ada pembatasan waktu akses untuk konsultasi chat

  Booking `consultation_type = chat` dibuat sebagai booking berjadwal, tapi tidak ada aturan kapan sesi chat boleh dimulai/berakhir — tidak ada "sesi chat 1×24 jam mulai dari jadwal" misalnya. Video punya jadwal jelas; chat tidak. Ini menimbulkan ambiguitas ekspektasi (pasien bisa mengharap chat kapan pun; psikolog kewalahan).

  **Rekomendasi (pilih salah satu):**
  - a) Definisikan window sesi chat di backend (mis. akses chat hanya `booking_date 00:00 → +24 jam`), tampilkan sisa waktu di UI; atau
  - b) Ubah chat menjadi "asinkron terjadwal": pasien menulis dalam window, psikolog membalas dalam SLA 24 jam — dan tulis ekspektasinya di langkah 1 wizard saat memilih media.

  ### 5.4 🟠 Catatan klinis: sudah bagus, 3 penyempurnaan

  Yang sudah benar: enkripsi at-rest (cast `encrypted`), akses terbatas ke psikolog pemilik, dekripsi otomatis via accessor. Kekhawatiran standar praktik:

  1. **Tidak ada jejak audit (audit trail)** siapa membuka/mengubah catatan dan kapan — di banyak yurisdiksi (termasuk UU PDP Indonesia) ini diharuskan. Rekomendasi: tabel `note_access_logs` (note_id, user_id, action, ip, timestamp) diisi saat index/store.
  2. **Retensi & penghapusan**: tidak ada kebijakan retensi atau penghapusan catatan saat akun dihapus (admin `destroy` menghapus user — apakah catatan ikut terhapus bersih?). Rekomendasi: definisikan retensi (mis. 5 tahun) + anonimisasi.
  3. **Catatan hanya bisa dibuat psikolog** — tidak ada tempat pasien melihat ringkasan hasil konsultasi. Untuk standar praktik etis, pasien layak mendapat ringkasan/kesimpulan sesi. Rekomendasi: kolom `shared_summary` opsional yang psikolog publish ke pasien (terpisah dari catatan klinis mentah).

  ### 5.5 🟠 Verifikasi identitas pasien: model ada, flow tidak jalan

  `PatientVerification` (KTP/paspor, status, rejection_reason, verified_by) ada di backend, **tapi tidak ada endpoint controller** yang mengeksposnya — tidak bisa upload, tidak bisa review admin. Sementara itu `RegisterController` langsung set `phone_verified_at = now()` (telepon tidak benar-benar diverifikasi) dan `email_verified_at = now()` (email juga tidak diverifikasi — hanya dicek Turnstile bot).

  Dampak standar-platform: platform psikologi biasanya mewajibkan verifikasi identitas minimal sebelum sesi pertama (bukan hanya kepatuhan SIP untuk psikolog, yang sudah baik). **Rekomendasi:** buat endpoint pasien upload dokumen + halaman admin verifikasi; jangan tandai `phone_verified_at` sampia ada OTP; email bisa tetap belum terverifikasi (kirim link verifikasi).

  ### 5.6 🟡 Ejaan/etika kecil lain

  - **Anonimisasi data di dashboard admin**: admin melihat nama lengkap pasien di daftar jadwal/konsultasi/transaksi. Wajar untuk operasional, tapi tambahkan penanda akses (audit log admin juga bernilai di sini).
  - **"Umur/kategori klien"** tidak ada batasan usia minimum — kategori "Siswa/Anak" ada, tapi tidak ada field usia atau syarat persetujuan wali. Standar praktik anak/remaja: butuh persetujuan orang tua. Rekomendasi: flag `requires_guardian_consent` pada kategori + field wali saat booking kategori tersebut.

  ---

  ## 6. Logika Bisnis yang Kurang Praktis + Usulan Solusi (tanpa kode)

  ### 6.1 Pesanan 24 jam + jadwal 7 hari: dua sistem expiry yang membingungkan

  **Masalah praktis.** Order pending payment kadaluarsa 24 jam (`expires_at`), order paid kadaluarsa 7 hari untuk memilih jadwal. Dua masalah:
  1. `AutoExpireOrders` menentukan kadaluarsa dari `updated_at` — padahal `updated_at` berubah **karena alasan apa pun** (mis. payment status berubah, atau kolom lain ter-update). Rentan salah hitung.
  2. `Order::scheduleDeadlineExpired()` memakai `updated_at->addDays(7)` — bug yang sama, dan **kontradiksi dengan `expires_at = now()+7 days`** yang diset webhook saat paid. Dua sumber kebenaran berbeda.
  3. Pasien tidak melihat deadline di mana pun di frontend — tidak ada countdown, tidak ada badge "segera kadaluarsa".

  **Solusi yang diusulkan:**
  - Gunakan **satu kolom deadline eksplisit** (`schedule_deadline_at`) yang diset sekali saat payment sukses; `scheduleDeadlineExpired()` dan `AutoExpireOrders` membaca kolom itu. Hapus ketergantungan pada `updated_at`.
  - Tampilkan deadline & countdown di langkah 2 wizard dan di Sesi Saya ("Pilih jadwal sebelum 21 Okt").
  - Email/WA "H-2 belum pilih jadwal" sudah ada (`no-schedule-reminder` H+3/H+6) — cukup, tapi tambahkan link deep ke wizard.

  ### 6.2 Refund "pending" tidak pernah selesai — uang user menggantung

  **Masalah praktis.** Saat pasien membatalkan booking, `Refund` dibuat `status: pending`, dan WA mengabari "refund sedang diproses". Tapi: tidak ada UI admin untuk approve/reject (gap 2.5), tidak ada job yang memproses refund ke Midtrans, dan tidak ada notifikasi lanjutan saat refund selesai. Di produksi ini **aduan pelanggan yang pasti terjadi**.

  **Solusi yang diusulkan:**
  1. Bangun halaman admin Refund (sudah ada endpoint-nya) — tombol approve → trigger job proses refund Midtrans (`Midtrans::refund`) atau transfer manual dengan bukti; reject → kirim WA penjelasan.
  2. Tambahkan kolom `processed_at`/`proof_url` di resource untuk transparansi.
  3. Tampilkan status refund di Sesi Saya ("Refund Rp150.000 (75%) — diproses").

  ### 6.3 Wizard booking: state hilang saat refresh + tidak bisa balik ke order yang dibayar

  **Masalah praktis.** Wizard menyimpan order di Pinia (in-memory). Refresh di langkah 2/3 → state hilang → user diarahkan ke langkah 1 → kalau submit lagi → **order baru dibuat, order lama yatim** menunggu expire 24 jam (dan WA "order dibuat" terkirim lagi). User yang sudah bayar tapi refresh akan **ditagih dua kali** jika tidak sadar.

  **Solusi yang diusulkan:**
  - Saat masuk langkah 1 dengan psikolog yang sama, cek dulu "apakah ada order pending_payment/paid terbaru untuk psikolog ini?" (endpoint list order sudah mendukung filter status). Ada order paid tanpa booking → langsung lempar ke langkah 3. Ada pending → tawarkan lanjut bayar order itu, bukan buat baru.
  - Ini memakai endpoint yang sudah ada (`GET /pasien/orders?status=...`), tanpa endpoint baru.

  ### 6.4 Alur "pilih slot saat booking" vs "slot dipegang saat dibayar" — race condition

  **Masalah praktis.** Slot dicek saat `POST schedule`, bukan saat pembayaran. Dua pasien bisa bayar untuk psikolog yang sama lalu berebut slot; yang kalah sudah membayar dan harus menunggu refund (atau admin membantu). `locked_until` di booking dan command `slots:release-locked` ada, tapi tidak pernah dipakai oleh alur mana pun — **dead feature**.

  **Solusi yang diusulkan (pilih salah satu, urut kompleksitas):**
  1. **Ringan:** setelah pembayaran sukses, tampilkan slot langsung dan booking dibuat via API yang sudah ada — risiko rebutan tetap ada tapi jendela kecil (hanya antara bayar & pilih jadwal). Tambahkan pesan error yang jelas + otomatis tawarkan slot lain saat konflik (respons 422 dari backend sudah bagus).
  2. **Lebih baik:** aktifkan hold slot: `POST pasien/psikolog/{id}/slots/hold` menyimpan `locked_until = now+10 menit` (tabel/relasi sudah ada). Wizard langkah 3: hold → bayar → konfirmasi pakai hold. Scheduler release sudah tersedia.
  3. Slot yang di-hold tampil sebagai "dipesan sementara" ke user lain.

  ### 6.5 Pembatalan order pending vs batal booking: dua jalur berbeda yang tidak konsisten

  **Masalah praktis.** Order pending bisa "cancel" (soft) — tapi frontend tidak menyediakannya, sehingga order menunggu expire 24 jam + WA order-created terkirim ke user padahal user sudah tidak berminat. Sebaliknya pembatalan booking punya skema refund berlapis. Dua model mental berbeda untuk "membatalkan".

  **Solusi yang diusulkan:**
  - Satukan di UI: di riwayat order, tombol "Batalkan" memanggil endpoint yang sesuai status (pending → cancel order; scheduled → cancel booking + refund). Backend sudah konsisten; hanya frontend yang belum.
  - Saat membuat order baru untuk psikolog yang sama saat masih ada order pending (dari 6.3), auto-cancel order lama.

  ### 6.6 Reschedule H-1 tapi refund H-3: aturan yang saling mencengkeram

  **Masalah praktis.** Pasien boleh reschedule sampai H-1 (24 jam), tapi kalau membatalkan di 24–48 jam refund 50%, di <24 jam tidak ada refund. Artinya **reschedule jauh lebih menguntungkan daripada batal** — dan itu bagus, TAPI: aturan ini tidak dijelaskan di UI saat pasien memilih aksi (hanya di modal cancel). Reschedule juga hanya 2× dan dihitung hanya reschedule oleh pasien — baik — tapi tidak ada tampilan sisa kuota ("1x reschedule tersisa") di UI.

  **Solusi yang diusulkan:**
  - Tampilkan "sisa kuota reschedule" di kartu booking (data `reschedule_count` sudah dikirim backend).
  - Saat pasien klik "Batalkan", tampilkan dulu **perkiraan refund secara live** (hitung client-side dari waktu sesi; rumusnya sederhana 100/75/50/0) sebelum konfirmasi — bukan hanya setelah terjadi (backend sudah mengembalikan angka itu di respons, frontend tinggal menampilkannya juga).

  ### 6.7 Slot & jadwal: waktu lokal vs UTC dan "slot 08:00 vs 08:05"

  **Masalah praktis.**
  1. `Booking.start_time` disimpan sebagai string `H:i` (aksessor substr) — pembulatan ke menit; slot dihasilkan dengan langkah = durasi (30/60/90). Durasi 90 menit dengan jadwal 09:00–12:00 menghasilkan slot 09:00, 10:30 — OK. Tapi dua jadwal bertumpuk (mis. 09:00–12:00 & 10:00–14:00) menghasilkan **slot duplikat** 10:00, 11:00 (dari schedule pertama) + 10:00 (dari schedule kedua). `array_filter` tidak dedupe.
  2. Semua waktu ditangani sebagai waktu lokal server (Asia/Jakarta diasumsikan). Tidak ada penanganan DST (Indonesia aman) tapi **tidak ada penanda timezone** di respons API (`"start_time": "09:00"` tanpa offset). Frontend menampilkan "WIB" hardcoded.

  **Solusi yang diusulkan:**
  - Dedupe slot berdasarkan `start_time` sebelum mengembalikan (atau merge interval schedules jadi union sebelum generate slot).
  - Standarkan respons slot/booking menyertakan `timezone: "Asia/Jakarta"` (atau ISO 8601 dengan offset) agar client tidak menebak.

  ### 6.8 Statistik pendapatan psikolog memakai `updated_at` order

  **Masalah praktis.** `monthly_income` dihitung dari `whereMonth('updated_at')` pada order completed. `updated_at` berubah kalau order di-touch untuk alasan lain → pendapatan bulanan bisa salah bulan. (Sama dengan 6.1 — akar masalah yang sama.)

  **Solusi:** gunakan waktu penyelesaian konsultasi (`consultation.ended_at`) sebagai basis statistik pendapatan, bukan `updated_at` order. Data ini sudah ada.

  ### 6.9 Admin CRUD psikolog: hapus akun = hilang riwayat klinis

  **Masalah praktis.** `Admin\PsikologController::destroy` menghapus user + profil (soft delete) — tapi order/booking/konsultasi/catatan yang terhubung tetap menunjuk user yang soft-deleted. Dashboard admin & WA reminder bisa error saat eager-load psikolog yang sudah dihapus. Sementara itu standar praktik: **psikolog yang berhenti tidak boleh membuat riwayat klien hilang**.

  **Solusi yang diusulkan:**
  - Ganti "Hapus" dengan **"Nonaktifkan"** (set `is_active=false` + `status=suspended`) sebagai aksi utama; hapus permanen hanya untuk akun tanpa transaksi.
  - Jika tetap hapus: pastikan semua UI memakai `withTrashed()` saat menampilkan psikolog historis, dan booking berjalan ditangani dulu (pindahkan/batalkan).

  ### 6.10 Overview pasien menghitung "upcoming" dari seluruh booking

  **Masalah praktis.** Overview pasien menghitung `upcomingBookings` = filter status `confirmed` saja dari **halaman pertama** booking (per_page default 10). Kalau pasien punya 15 booking dan yang confirmed ada di halaman 2, angka salah. (Kecil, tapi mudah diperbaiki.)

  **Solusi:** overview cukup memanggil `GET /pasien/bookings?status=confirmed&per_page=3` dan pakai `meta.total` untuk angka statistik — tanpa endpoint baru.

  ---

  ## 7. Checklist Pekerjaan (Status Saat Ini)

  ### A. Frontend — sudah dikerjakan

  | Item | Status |
  |---|---|
  | Login + redirect query `?redirect=` | ✅ Selesai |
  | Register + Turnstile | ✅ Selesai |
  | Dashboard layout (sidebar, theme, drawer) | ✅ Selesai |
  | Overview pasien & psikolog | ✅ Selesai |
  | Profil (nama, telepon, avatar) | ✅ Selesai |
  | Cari Psikolog (katalog + filter + urutan) | ✅ Selesai |
  | Wizard booking 4 langkah sebagai **halaman penuh** (bukan modal) | ✅ Selesai |
  | Stepper + ringkasan sticky + guard login + deep-link | ✅ Selesai |
  | Sesi Saya: list, reschedule (maks 2×, H-1), batalkan | ✅ Selesai |
  | Psikolog: jadwal praktek CRUD | ✅ Selesai |
  | Psikolog: antrean booking + mulai/akhiri konsultasi + catatan klinis | ✅ Selesai |
  | Admin: kelola psikolog (CRUD, verifikasi, suspend) | ✅ Selesai |
  | Admin: semua jadwal & semua konsultasi | ✅ Selesai |
  | Fix parsing response `data`/`meta` (root, bukan `data.data`) | ✅ Selesai |

  ### B. Frontend — belum dikerjakan (gap)

  | Item | Status | Endpoint terkait | Prioritas |
  |---|---|---|---|
  | Halaman detail psikolog publik + review | ❌ Belum | `public/psikolog/{slug}`, reviews | Tinggi |
  | Halaman Pendapatan psikolog | ❌ Belum | `psikolog/income`, `income/report` | Tinggi |
  | Admin: Transaksi, Refund (approve/reject), Overview, Laporan | ❌ Belum | `admin/*` | Tinggi |
  | Admin: kelola kategori/durasi/spesialisasi | ❌ Belum | `admin/categories|durations|specializations` | Sedang |
  | Psikolog: edit profil profesional | ❌ Belum | `psikolog/profile` | Tinggi |
  | Psikolog: aksi status booking (no-show dsb.) | ❌ Belum | `psikolog/bookings/{id}/status` | Sedang |
  | Integrasi Snap Midtrans + polling status payment | ❌ Belum | `snap_url`, `pasien/payments/{id}` | **Kritis** |
  | Pulihkan state wizard setelah refresh (pakai order existing) | ❌ Belum | `pasien/orders?status=` | Sedang |
  | Tampilkan hasil refund di UI setelah batal | ❌ Belum | respons `cancel` | Sedang |
  | Meeting via endpoint (JWT, validasi status) | ❌ Belum | `bookings/{id}/meeting` | Sedang |
  | Riwayat order + batal order pending | ❌ Belum | `pasien/orders/{id}/cancel` | Sedang |
  | Review: beri/tampil/moderasi | ❌ Belum | butuh endpoint POST baru di backend | Tinggi |
  | Kuota reschedule + estimasi refund di UI | ❌ Belum | data sudah tersedia di respons | Rendah |
  | Admin: aksi status booking + detail konsultasi | ❌ Belum | `admin/bookings/{id}/status` | Rendah |

  ### C. Backend — perlu perbaikan (bug & keamanan)

  | Item | Status | Referensi |
  |---|---|---|
  | Fix parameter `duration` vs `duration_minutes` di slots | ❌ Bug aktif | §3.1 |
  | Kunci endpoint mock webhook (env flag + auth pemilik order) | ❌ Risiko tinggi | §3.2 |
  | Hapus/implementasi ulang `mockSuccess()` dead code | ❌ Dead code | §3.3 |
  | Deadline jadwal pakai kolom eksplisit, bukan `updated_at` | ❌ Logika rawan | §6.1 |
  | Dedupe slot bertumpuk (schedules overlap) | ❌ Logika rawan | §6.7 |
  | Statistik pendapatan pakai `consultation.ended_at` | ❌ Logika rawan | §6.8 |
  | Refund: job pemrosesan + notifikasi selesai | ❌ Logika rawan | §6.2 |
  | Review: endpoint pasien membuat review | ❌ Fitur hilang | §4.1 |
  | Verifikasi identitas pasien: endpoint + admin | ❌ Fitur hilang | §5.5 |
  | Audit trail catatan klinis + retensi | ❌ Standar praktik | §5.4 |
  | Samarkan nama pasien di review publik | ❌ Privasi | §5.2 |
  | Chat: window sesi / SLA eksplisit | ❌ Standar praktik | §5.3 |
  | Meeting: batas waktu akses (mis. 15 menit sebelum sesi) | ❌ Standar praktik | §5.1 |
  | Guard consent orang tua untuk kategori anak | ❌ Standar praktik | §5.6 |
  | "Hapus psikolog" → nonaktifkan, jangan hapus | ❌ Praktik lebih aman | §6.9 |

  ### D. DevOps / rilis

  | Item | Status |
  |---|---|
  | Midtrans keys produksi + signature verification | ⚠️ Sebelum rilis |
  | `APP_DEBUG=false`, `APP_ENV=production` | ⚠️ Sebelum rilis |
  | Cron `schedule:run` per menit (scheduler WA/expire) | ⚠️ Sebelum rilis |
  | Queue worker (`SendWhatsAppNotification` di-dispatch via queue) | ⚠️ Sebelum rilis |
  | Hapus `usePolling` vite dev (hanya dev, bukan produksi) | 🟡 Opsional |

  ---

  ## 8. Urutan Pengerjaan yang Disarankan

  1. **Perbaiki bug backend dulu** (§3.1, §3.2, §6.1) — kecil, berdampak besar, dan memblokir integrasi yang benar.
  2. **Integrasi pembayaran Snap + polling** (gap kritis #11) — tanpa ini platform tidak bisa menerima uang.
  3. **Halaman Pendapatan psikolog + Admin Transaksi/Refund/Overview** (gap #2, #3, #4) — nilai operasional tertinggi.
  4. **Siklus review end-to-end** (gap #13) — loop kepercayaan platform.
  5. **Meeting via endpoint + batas waktu akses** (§5.1) — privasi sesi.
  6. Sisanya mengikuti prioritas tabel §4.3.

  ---

  *Laporan ini dihasilkan dari pembacaan statis seluruh kode backend & frontend pada branch `frontend`. Tidak ada perubahan kode yang dilakukan selama audit.*
