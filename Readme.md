# Rumah Nafasy

A unified development & build environment for **Rumah Nafasy** (Laravel Backend API + Queue Worker + Vue 3 Frontend).

---

## 🚀 Perintah Utama (CLI)

### 1. Mode Development (`nafasy dev`)
Menjalankan seluruh layanan (**Laravel Server**, **Queue Worker**, dan **Vue Frontend**) secara bersamaan dalam satu terminal:

```bash
nafasy dev
```

Output log otomatis diformat dengan prefix warna:
```text
[vue]     ➜  Local:   http://localhost:5173/
[laravel] INFO Server running on [http://127.0.0.1:8000].
[work]    Processing jobs from the [default] queue.
```

---

### 2. Mode Production Build (`nafasy build`)
Membangun frontend Vue (Vite) dan menyimpan hasil build langsung ke dalam direktori [`.nafasy/dist`](file:///c:/Users/Hype/Documents/Developer/playground/rumah-natasy/.nafasy) (mirip seperti `.nuxt` di Nuxt) sekaligus menjalankan test suite backend Laravel (`php artisan test`):

```bash
nafasy build
```

Hasil build di `.nafasy/dist/` sudah otomatis di-ignore oleh git sehingga repositori tetap bersih.

---

## 🛠️ Tabel Referensi Perintah CLI

| Perintah | Deskripsi |
|---|---|
| `nafasy dev` | Menjalankan **Laravel serve**, **Queue worker**, dan **Vue frontend** bersamaan |
| `nafasy dev --listen` | Menjalankan dev environment dengan `queue:listen` |
| `nafasy build` | Build Vue ke `.nafasy/dist` + jalankan backend test suite |
| `nafasy preview` | Preview production build dari `.nafasy/dist` |
| `nafasy test` | Menjalankan unit & feature test backend (`php artisan test`) |
| `nafasy clean` | Membersihkan folder build `.nafasy/dist` |
| `nafasy backend` | Menjalankan Laravel API Server & Queue Worker saja |
| `nafasy frontend` | Menjalankan Frontend Vue saja |
| `nafasy work` | Menjalankan Queue Worker saja (`php artisan queue:work`) |
| `nafasy install` | Menginstall seluruh dependensi (`composer install` + `pnpm install`) |
| `nafasy help` | Menampilkan menu bantuan CLI |

---

## 👥 Setup Tim (1 Kali Saja)

Agar anggota tim baru bisa langsung mengetik perintah `nafasy` di terminal manapun (Git Bash, PowerShell, CMD):

```bash
cd .nafasy && npm link
```