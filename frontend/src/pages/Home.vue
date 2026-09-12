<script setup lang="ts">
import HomeStats from '../components/section/home/HomeStats.vue'
</script>

<template>
    <main class="home-page">
        <section class="hero-section" id="mulai">
            <div class="hero-copy">
                <p class="eyebrow">OurRitual</p>
                <h1>Perkuat Hubungan Anda<br class="desktop-break" /> dengan Dukungan Personal</h1>
                <p class="hero-description">
                    OurRitual adalah pendekatan baru untuk terapi pasangan, memadukan bimbingan ahli dengan
                    pengalaman digital personal yang mendukung pertumbuhan dan kedekatan berkelanjutan
                </p>
                <a class="button button-hero" href="#cara-kerja">Mulai Sekarang</a>
            </div>

            <div class="hero-image-wrap">
                <img
                    class="hero-image"
                      src="https://images.unsplash.com/photo-1529333166437-7750a6dd5a70?auto=format&fit=crop&w=1800&q=85"
                    alt="Pasangan tersenyum bersama di luar ruangan"
                />
                <div class="feature-list" aria-label="Keunggulan OurRitual">
                    <span class="feature-pill"><UserGroupIcon :size="19" aria-hidden="true" />300+ Ahli Terverifikasi</span>
                    <span class="feature-pill"><CalendarDaysIcon :size="19" aria-hidden="true" />Paket Fleksibel</span>
                    <span class="feature-pill"><DevicePhoneMobileIcon :size="19" aria-hidden="true" />Alat Digital Personal</span>
                </div>
            </div>
        </section>

        <HomeStats />

        <section class="home-followup" id="cara-kerja" aria-label="Informasi OurRitual">
            <span>Dukungan yang dirancang untuk hubungan yang sedang Anda bangun.</span>
        </section>
    </main>
</template>
