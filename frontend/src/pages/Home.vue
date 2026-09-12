<script setup lang="ts">
import HomeStats from '../components/section/home/HomeStats.vue'
import HomeHowItWorks from '../components/section/home/HomeHowItWorks.vue'
import HomePsikolog from '../components/section/home/HomePsikolog.vue'
import HomeKategori from '../components/section/home/HomeKategori.vue'
import HomeKenapa from '../components/section/home/HomeKenapa.vue'
import HomeTestimoni from '../components/section/home/HomeTestimoni.vue'
import HomeFaq from '../components/section/home/HomeFaq.vue'
</script>

<template>
  <!-- ===== HERO ===== -->
  <section class="hero-section pt-10 pb-0" id="mulai" aria-labelledby="hero-heading">
    <div class="hero-copy">
      <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-4" aria-hidden="true">
        Platform Konsultasi Psikologi #1 Indonesia
      </p>
      <h1 id="hero-heading" class="font-display text-[clamp(2rem,5vw,3.5rem)] font-semibold leading-[1.18] tracking-tight text-[var(--ink)] max-w-[820px] mx-auto">
        Kesehatan Mental Anda<br class="desktop-break" />
        Adalah Prioritas Kami
      </h1>
      <p class="hero-description">
        Konsultasi dengan psikolog berlisensi dari rumah Anda. Mudah, aman, terjangkau—tersedia 7 hari seminggu untuk mendampingi perjalanan kesehatan mental Anda.
      </p>
      <div class="flex items-center justify-center gap-3 flex-wrap mt-6">
        <a class="button button-hero" href="#cara-kerja">Mulai Konsultasi</a>
        <a
          href="#ahli"
          class="inline-flex items-center justify-center min-h-[57px] px-6 rounded-full border border-[var(--line)] text-[var(--ink)] font-semibold text-sm hover:border-[var(--accent)]/50 hover:text-[var(--accent)] transition-all duration-200"
        >
          Lihat Psikolog
        </a>
      </div>
    </div>

    <div class="hero-image-wrap mt-10">
      <img
        class="hero-image"
        src="https://images.unsplash.com/photo-1573497019236-17f8177b81e8?auto=format&fit=crop&w=1800&q=85"
        alt="Psikolog profesional berbicara dengan klien dalam sesi konsultasi yang nyaman"
        fetchpriority="high"
        decoding="async"
      />
      <!-- overlay gradient -->
      <div class="absolute inset-0 bg-gradient-to-t from-black/30 via-transparent to-transparent pointer-events-none" aria-hidden="true" />

      <div class="feature-list" aria-label="Keunggulan Rumah Natasy">
        <span class="feature-pill">
          <CheckBadgeIcon class="w-[19px] h-[19px]" aria-hidden="true" />
          150+ Psikolog SIP
        </span>
        <span class="feature-pill">
          <LockClosedIcon class="w-[19px] h-[19px]" aria-hidden="true" />
          100% Rahasia
        </span>
        <span class="feature-pill">
          <CalendarDaysIcon class="w-[19px] h-[19px]" aria-hidden="true" />
          Jadwal Fleksibel
        </span>
      </div>
    </div>
  </section>

  <!-- ===== STATS ===== -->
  <HomeStats />

  <!-- ===== CARA KERJA ===== -->
  <HomeHowItWorks />

  <!-- ===== PSIKOLOG PILIHAN ===== -->
  <HomePsikolog />

  <!-- ===== KATEGORI LAYANAN ===== -->
  <HomeKategori />

  <!-- ===== KENAPA RUMAH NATASY ===== -->
  <HomeKenapa />

  <!-- ===== TESTIMONI ===== -->
  <HomeTestimoni />

  <!-- ===== FAQ ===== -->
  <HomeFaq />
</template>
