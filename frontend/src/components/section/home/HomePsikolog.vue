<template>
  <section id="ahli" aria-labelledby="psikolog-heading" class="py-20 md:py-28">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-12">
      <div>
        <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Tim Profesional Kami</p>
        <h2 id="psikolog-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight">
          Psikolog Pilihan
        </h2>
        <p class="mt-3 text-[var(--copy)] text-base max-w-lg leading-relaxed">
          Semua psikolog kami bersertifikat resmi dengan pengalaman klinis minimal 3 tahun.
        </p>
      </div>
      <a href="#" class="inline-flex items-center gap-1.5 text-[var(--accent)] font-semibold text-sm shrink-0 hover:opacity-70 transition-opacity group">
        Lihat semua psikolog
        <ArrowUpRightIcon class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" aria-hidden="true" />
      </a>
    </div>

    <!-- Scroll carousel di mobile, grid di desktop -->
    <div class="flex gap-5 overflow-x-auto snap-x snap-mandatory pb-4 md:grid md:grid-cols-4 md:overflow-visible md:pb-0 scrollbar-hide -mx-4 px-4 md:mx-0 md:px-0">
      <article
        v-for="psikolog in psikologList"
        :key="psikolog.name"
        class="snap-start shrink-0 w-[76vw] sm:w-[300px] md:w-auto bg-[var(--background)] border border-[var(--line)] rounded-3xl overflow-hidden hover:shadow-lg hover:border-[var(--accent)]/30 transition-all duration-300 group flex flex-col"
      >
        <!-- Avatar / foto area -->
        <div class="relative h-44 overflow-hidden" :style="{ background: psikolog.avatarBg }">
          <div class="absolute inset-0 flex items-end justify-center">
            <div class="w-32 h-36 rounded-t-full" :style="{ background: psikolog.avatarColor }" />
          </div>
          <!-- badge tersedia -->
          <span
            v-if="psikolog.available"
            class="absolute top-3 left-3 text-[10px] font-semibold px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200"
          >
            Tersedia hari ini
          </span>
        </div>

        <div class="p-5 flex flex-col flex-1 gap-3">
          <!-- Nama & spesialisasi -->
          <div>
            <h3 class="font-display font-semibold text-[var(--ink)] text-base leading-snug">{{ psikolog.name }}</h3>
            <p class="text-[var(--muted)] text-xs mt-0.5">{{ psikolog.title }}</p>
          </div>

          <!-- Tags spesialisasi -->
          <div class="flex flex-wrap gap-1.5">
            <span
              v-for="tag in psikolog.specializations"
              :key="tag"
              class="text-[10px] px-2.5 py-1 rounded-full bg-[var(--surface)] border border-[var(--line)] text-[var(--copy)]"
            >
              {{ tag }}
            </span>
          </div>

          <!-- Rating & pengalaman -->
          <div class="flex items-center justify-between text-xs text-[var(--muted)] pt-1 border-t border-[var(--line)]">
            <span class="flex items-center gap-1">
              <span class="text-amber-400">★</span>
              <strong class="text-[var(--ink)]">{{ psikolog.rating }}</strong>
              <span>({{ psikolog.reviews }} ulasan)</span>
            </span>
            <span>{{ psikolog.experience }} pengalaman</span>
          </div>

          <!-- Harga & CTA -->
          <div class="flex items-center justify-between mt-auto pt-1">
            <div>
              <p class="text-[10px] text-[var(--muted)]">Mulai dari</p>
              <p class="font-display font-bold text-[var(--ink)] text-lg leading-tight">{{ psikolog.price }}</p>
            </div>
            <button
              type="button"
              class="px-4 py-2 rounded-full bg-[var(--accent)] text-white text-xs font-bold hover:opacity-85 active:scale-95 transition-all"
            >
              Booking
            </button>
          </div>
        </div>
      </article>
    </div>

    <!-- Badges kepercayaan -->
    <div class="mt-10 flex flex-wrap items-center justify-center gap-6 text-xs text-[var(--muted)]">
      <span class="flex items-center gap-2">
        <CheckBadgeIcon class="w-4 h-4 text-emerald-500" aria-hidden="true" />
        Tersertifikasi SIP (Surat Izin Praktik)
      </span>
      <span class="flex items-center gap-2">
        <CheckBadgeIcon class="w-4 h-4 text-emerald-500" aria-hidden="true" />
        Anggota aktif HIMPsi
      </span>
      <span class="flex items-center gap-2">
        <CheckBadgeIcon class="w-4 h-4 text-emerald-500" aria-hidden="true" />
        Verifikasi latar belakang penuh
      </span>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ArrowUpRightIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline'

const psikologList = [
  {
    name: 'Dra. Sari Dewi, M.Psi., Psikolog',
    title: 'Psikolog Klinis',
    specializations: ['Kecemasan', 'Depresi', 'Trauma'],
    rating: '4.9',
    reviews: '312',
    experience: '8 tahun',
    price: 'Rp 150.000/sesi',
    available: true,
    avatarBg: 'linear-gradient(135deg, #fde8e6 0%, #fcd9d7 100%)',
    avatarColor: 'linear-gradient(160deg, #f9a8a0, #fbd0c8)',
  },
  {
    name: 'Dr. Bima Arya, M.Psi., Psikolog',
    title: 'Psikolog Klinis & Konselor',
    specializations: ['Hubungan', 'Keluarga', 'Stres'],
    rating: '4.8',
    reviews: '198',
    experience: '6 tahun',
    price: 'Rp 130.000/sesi',
    available: true,
    avatarBg: 'linear-gradient(135deg, #e0f0ff 0%, #c8e4ff 100%)',
    avatarColor: 'linear-gradient(160deg, #93c5fd, #bfdbfe)',
  },
  {
    name: 'Nadia Putri, M.Psi., Psikolog',
    title: 'Psikolog Anak & Remaja',
    specializations: ['Anak', 'Remaja', 'ADHD'],
    rating: '5.0',
    reviews: '147',
    experience: '5 tahun',
    price: 'Rp 140.000/sesi',
    available: false,
    avatarBg: 'linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%)',
    avatarColor: 'linear-gradient(160deg, #86efac, #bbf7d0)',
  },
  {
    name: 'Rian Kusuma, M.Psi., Psikolog',
    title: 'Psikolog Klinis',
    specializations: ['Burnout', 'Karier', 'Self-esteem'],
    rating: '4.7',
    reviews: '256',
    experience: '7 tahun',
    price: 'Rp 120.000/sesi',
    available: true,
    avatarBg: 'linear-gradient(135deg, #fdf4ff 0%, #f3e8ff 100%)',
    avatarColor: 'linear-gradient(160deg, #d8b4fe, #e9d5ff)',
  },
]
</script>
