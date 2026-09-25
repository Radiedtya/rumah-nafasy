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
  <section class="hero-section" id="mulai" aria-labelledby="hero-heading">
    <div class="hero-media" aria-hidden="true">
      <img src="/images/bg/image.png" alt="" />
    </div>
    <div class="hero-copy">
      <h1 id="hero-heading">
        Mental Sehat,<br class="desktop-break" />
        Hidup Kuat
      </h1>
    </div>

    <div class="hero-side-copy">
      <p class="hero-description">
        Temui psikolog berlisensi secara aman, mudah, dan fleksibel dari rumah.
      </p>
      <div class="hero-actions">
        <a class="button button-hero" href="#cara-kerja">Mulai Konsultasi <span aria-hidden="true">→</span></a>
        <a
          href="#ahli"
          class="hero-secondary-button"
        >
          Lihat Psikolog <span aria-hidden="true">→</span>
        </a>
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

  <!-- ===== KENAPA Rumah Nafasy ===== -->
  <HomeKenapa />

  <!-- ===== TESTIMONI ===== -->
  <HomeTestimoni />

  <!-- ===== FAQ ===== -->
  <HomeFaq />
</template>
