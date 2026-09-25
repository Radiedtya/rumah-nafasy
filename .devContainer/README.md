# Rumah Nafasy containers

Semua konfigurasi container disimpan di folder ini. Tidak ada Dockerfile atau compose file yang diperlukan di root proyek.

## Pilih mode

### Frontend only

Menjalankan hasil build Vue/Vite melalui Nginx. Backend tidak dijalankan oleh compose ini.

```bash
docker compose -f .devContainer/docker-compose.frontend-only.yml up --build
```

Buka `http://localhost:3000`.

### Backend only

Menjalankan Laravel melalui Apache/PHP. `APP_KEY` wajib diberikan dari environment host dan tidak disimpan di repository.

```bash
export APP_KEY='base64:your-production-key'
docker compose -f .devContainer/docker-compose.backend-only.yml up --build
```

Untuk menjalankan migrasi secara eksplisit pada startup:

```bash
RUN_MIGRATIONS=1 docker compose -f .devContainer/docker-compose.backend-only.yml up --build
```

Buka API di `http://localhost:8000`.

### Fullstack

Menjalankan frontend Nginx dan backend Laravel bersama-sama.

```bash
export APP_KEY='base64:your-production-key'
docker compose -f .devContainer/docker-compose.fullstack.yml up --build
```

Buka frontend di `http://localhost:3000` dan backend di `http://localhost:8000`.

## Environment production

Jangan menaruh secret di file compose atau commit ke repository. Compose menerima variable dari shell atau file environment lokal yang tidak dilacak Git, misalnya:

```bash
docker compose --env-file .devContainer/.env.production -f .devContainer/docker-compose.fullstack.yml up --build
```

Untuk frontend yang diakses melalui domain/tunnel berbeda, set URL backend saat build:

```bash
VITE_BACKEND_URL=https://api.example.com \
  docker compose -f .devContainer/docker-compose.fullstack.yml build frontend
```

`RUN_MIGRATIONS` default-nya `0` supaya migrasi production tidak berjalan tanpa sengaja. Set `RUN_MIGRATIONS=1` hanya saat deployment yang memang memerlukannya.
