# Rumah Nafasy

A unified development & build environment for **Rumah Nafasy** (Laravel Backend API + Queue Worker + Vue 3 Frontend).

---

## 🚀 Perintah Utama (CLI)

### 1. Mode Development (`nafasy dev`)
Menjalankan seluruh layanan (**Laravel Server**, **Queue Worker**, dan **Vue Frontend**) dalam satu proses terpadu mirip framework modern:

```bash
nafasy dev
```

Output terminal bersih dan hanya menampilkan port Frontend (`http://localhost:3000`):

```text
        .d$b.
       i$$A$$L  .d$b
     .$$F` `$$L.$$A$$.
    j$$'    `4$$:` `$$.
   j$$'     .4$:    `$$.
  j$$`     .$$:      `4$L
 :$$:____.d$$:  _____.:$$:
 `4$$$$$$$$P` .i$$$$$$$$P`
  Rumah Nafasy Unified Full-Stack Framework

  ➜  Local:    http://localhost:3000/
  ➜  Backend:  http://127.0.0.1:8000 (proxied via /api)
  ➜  Queue:    Active (database driver)

  Ready for requests. Logs will appear below:
  Legend: ■ Frontend (Biru)  ■ Backend (Merah)  ■ Queue (Orange)

[nafasy]  INFO Processing jobs from the [default] queue.
[nafasy] Queue worker active & listening for jobs...
```

---

### 🎨 Sistem Warna Log `[nafasy]`

Semua output log menggunakan satu format prefix **`[nafasy]`**, dengan pembagian warna:

- 🔵 **Biru**: Log dari Frontend (Vue / Vite)
- 🔴 **Merah**: Log dari Backend (Laravel API)
- 🟠 **Orange**: Log dari Queue Worker (Job processing, completion, failed jobs)

---

### 2. Mode Production Build (`nafasy build`)
Membangun frontend Vue (Vite) dan menyimpan hasil build langsung ke dalam direktori [`.nafasy/dist`](file:///c:/Users/Hype/Documents/Developer/playground/rumah-natasy/.nafasy) (mirip seperti `.nuxt` di Nuxt) sekaligus menjalankan test suite backend Laravel (`php artisan test`):

```bash
nafasy build
```

---

## 🛠️ Tabel Referensi Perintah CLI

| Perintah | Deskripsi |
|---|---|
| `nafasy dev` | Menjalankan unified dev server di `http://localhost:3000` (Laravel + Queue + Vue) |
| `nafasy build` | Build Vue ke `.nafasy/dist` + jalankan backend test suite |
| `nafasy preview` | Preview production-like: Laravel backend di `8000` + frontend build di `3000` dengan cache Laravel aktif |
| `nafasy test` | Menjalankan unit & feature test backend (`php artisan test`) |
| `nafasy clean` | Membersihkan folder build `.nafasy/dist` |
| `nafasy backend` | Menjalankan Laravel API Server & Queue Worker saja |
| `nafasy frontend` | Menjalankan Frontend Vue saja (port 3000) |
| `nafasy work` | Menjalankan Queue Worker saja |
| `nafasy install` | Menginstall seluruh dependensi (`composer install` + `pnpm install`) |
| `nafasy config:clear` | Membersihkan cache konfigurasi Laravel |
| `nafasy cache:clear` | Membersihkan application cache Laravel |
| `nafasy optimize:clear` | Membersihkan seluruh cache Laravel (alias: `nafasy clear`) |
| `nafasy route:list` | Menampilkan daftar route terdaftar |
| `nafasy queue:restart` | Meminta queue worker restart setelah job aktif selesai |
| `nafasy artisan <command>` | Menjalankan command maintenance Laravel yang diizinkan |
| `nafasy help` | Menampilkan menu bantuan CLI |

> CLI hanya menyediakan command maintenance untuk operasional harian. Command database seperti `migrate`, `db:seed`, dan `db:wipe` tidak diteruskan oleh Nafasy.

---

## 👥 Setup Tim (1 Kali Saja)

Agar anggota tim baru bisa langsung mengetik perintah `nafasy` di terminal manapun (Git Bash, PowerShell, CMD):

```bash
cd .nafasy && npm link
```