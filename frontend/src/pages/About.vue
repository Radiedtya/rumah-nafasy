<script setup lang="ts">
import MaskedHeading from '../components/app/MaskedHeading.vue'
import { useTheme } from '../composables/useTheme'

const { theme } = useTheme()

const principles = [
  {
    number: '01',
    title: 'Mulai dari ceritamu',
    description: 'Kamu didengar tanpa dihakimi. Setiap orang punya pengalaman dan ritme pulih yang berbeda.',
  },
  {
    number: '02',
    title: 'Bertumbuh bersama ahli',
    description: 'Psikolog berlisensi mendampingi dengan pendekatan profesional yang disesuaikan dengan kebutuhanmu.',
  },
  {
    number: '03',
    title: 'Ruang yang terasa aman',
    description: 'Privasi dan rasa nyaman menjadi bagian dari perjalanan, dari langkah pertama sampai sesi berakhir.',
  },
]

const communityPhotos = [
  {
    src: '/images/album/WhatsApp%20Image%202026-09-23%20at%2011.04.53%20(2).jpeg',
    alt: 'Dua peserta berbincang di area terbuka dalam suasana yang tenang',
    caption: 'Percakapan hangat membuka ruang untuk saling memahami',
  },
  {
    src: '/images/album/WhatsApp%20Image%202026-09-23%20at%2011.05.00%20(2).jpeg',
    alt: 'Suasana ruang kegiatan Rumah Nafasy yang hangat dan terbuka',
    caption: 'Ruang aman dimulai dari rasa diterima',
  },
]
</script>

<template>
  <div class="about-page">
    <section class="about-hero" :class="`about-hero--${theme}`" aria-labelledby="about-hero-title">
      <div class="about-hero__content">
        <p class="about-eyebrow"><span></span> Rumah Nafasy · Tentang Kami</p>
        <MaskedHeading
          id="about-hero-title"
          class="about-hero__heading"
          tag="h1"
          text="Ruang Untuk Kembali Pulih"
          media-type="video"
          src="/mikudayo.mp4"
          :fill-scale="1.2"
          :parallax="20"
          :brightness="1.15"
          :saturation="0.9"
          reveal="rise"
          trigger="view"
          align="center"
          :weight="680"
          :tracking="-0.045"
          :line-height="0.9"
          :text-scale="0.13"
          :duration="1.25"
          :stagger="0.06"
        />
        <div class="about-hero__bottom">
          <a class="about-hero__link" href="#cerita">Cerita kami <span aria-hidden="true">↓</span></a>
        </div>
      </div>
    </section>

    <section id="cerita" class="about-story" aria-labelledby="about-story-title">
      <div class="about-story__intro">
        <p class="about-section-label"><span>01</span> Cerita kami</p>
        <h2 id="about-story-title">Tidak semua hal harus kamu hadapi sendirian.</h2>
      </div>
      <div class="about-story__copy">
        <p>Rumah Nafasy hadir untuk membuat dukungan kesehatan mental terasa lebih dekat. Meminta bantuan bukan tanda kelemahan; itu langkah berani untuk merawat diri.</p>
        <p>Kami menghubungkanmu dengan psikolog berlisensi melalui pengalaman konsultasi yang aman, fleksibel, dan berpusat pada kebutuhanmu.</p>
        <a class="about-text-link" href="/#cara-kerja">Kenali cara kerjanya <span aria-hidden="true">↗</span></a>
      </div>
    </section>

    <section class="about-community" aria-labelledby="about-community-title">
      <div class="about-community__copy">
        <p class="about-section-label"><span>02</span> Hadir di tengah komunitas</p>
        <h2 id="about-community-title">Kesehatan mental tumbuh saat kita saling terhubung.</h2>
        <p>Selain pendampingan personal, kami percaya percakapan yang terbuka membantu lebih banyak orang merasa dipahami. Kami hadir lewat ruang belajar dan kegiatan komunitas yang menguatkan.</p>
      </div>
      <div class="about-community__photos">
        <figure v-for="photo in communityPhotos" :key="photo.src" class="about-community__photo">
          <img :src="photo.src" :alt="photo.alt" loading="lazy">
          <figcaption>{{ photo.caption }}</figcaption>
        </figure>
      </div>
    </section>

    <section class="about-principles" aria-labelledby="about-principles-title">
      <div class="about-principles__panel">
        <div class="about-principles__header">
          <div>
            <p class="about-section-label"><span>03</span> Cara kami hadir</p>
            <h2 id="about-principles-title">Ruang aman untuk memahami dirimu.</h2>
          </div>
          <div class="about-principles__aside">
            <p class="about-principles__lead">Setiap cerita layak didengar dengan utuh. Psikolog mendampingi tanpa menghakimi, sambil membantumu menemukan langkah yang terasa tepat.</p>
            <a class="about-principles__link" href="/#cara-kerja">Lihat cara kerja kami <span aria-hidden="true">↗</span></a>
          </div>
        </div>
        <figure class="about-principles__photo">
          <img src="/images/album/WhatsApp%20Image%202026-09-23%20at%2011.04.53.jpeg" alt="Psikolog mendengarkan cerita klien dalam percakapan yang tenang" loading="lazy">
          <figcaption><span class="about-principles__photo-dot"></span> Didengar dengan empati, didampingi secara profesional</figcaption>
        </figure>
        <div class="about-principles__list">
          <article v-for="principle in principles" :key="principle.number" class="about-principle">
            <span class="about-principle__number">{{ principle.number }}</span>
            <div>
              <h3>{{ principle.title }}</h3>
              <p>{{ principle.description }}</p>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="about-closing" aria-labelledby="about-closing-title">
      <div class="about-closing__content">
        <p class="about-section-label"><span>04</span> Langkah berikutnya</p>
        <div class="about-closing__row">
          <div>
            <h2 id="about-closing-title">Kamu boleh mulai dengan satu cerita.</h2>
            <p>Tak perlu punya semua jawabannya hari ini. Mulailah saat kamu siap.</p>
          </div>
          <a href="/#cara-kerja" class="about-closing__link">Temukan cara yang cocok <span aria-hidden="true">↗</span></a>
        </div>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-page { --about-accent: #287b61; width: 100%; color: var(--text); }
.about-hero { position: relative; isolation: isolate; display: flex; align-items: center; justify-content: center; width: 100vw; min-height: clamp(680px, 88svh, 980px); margin-left: calc(50% - 50vw); padding: 148px max(24px, calc((100vw - 1280px) / 2 + 32px)) 72px; overflow: hidden; background: var(--about-hero-background); color: var(--about-hero-foreground); transition: background-color 220ms ease, color 220ms ease; }
.about-hero--light { --about-hero-background: #f4f4f0; --about-hero-foreground: #171717; }
.about-hero--dark { --about-hero-background: #080808; --about-hero-foreground: #fff; }
.about-hero__content { position: relative; z-index: 0; width: min(100%, 1180px); text-align: center; }
.about-hero__content::before { content: ''; position: absolute; left: 50%; top: 52%; width: calc(100vw - 24px); height: clamp(360px, 44vw, 620px); transform: translate(-50%, -50%); border-radius: 28px; background: #000; z-index: 0; pointer-events: none; }
.about-eyebrow,.about-section-label { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 12px; font-weight: 600; letter-spacing: .08em; text-transform: uppercase; }
.about-eyebrow { position: relative; z-index: 1; justify-content: center; margin-bottom: 28px; color: #fff; }
.about-eyebrow span { width: 7px; height: 7px; border-radius: 50%; background: #bce5a7; }
.about-hero__heading { position: relative; z-index: 1; display: block; max-width: 1120px; margin: 0 auto; color: var(--about-hero-foreground); }
.about-hero__bottom { position: relative; z-index: 1; display: flex; justify-content: center; margin-top: 28px; }
.about-hero__link,.about-closing__link { display: inline-flex; align-items: center; gap: 18px; min-height: 48px; padding-bottom: 5px; border-bottom: 1px solid currentColor; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; }
.about-hero__link span,.about-closing__link span { font-size: 20px; font-weight: 400; transition: transform 180ms ease; }
.about-section-label { color: var(--muted); }
.about-section-label span { color: var(--about-accent); font-family: var(--font-mono); }
.about-story,.about-community,.about-principles,.about-closing { width: 100%; max-width: none; margin: 0; color: var(--text); }
.about-story { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(300px,.72fr); align-items: end; gap: clamp(48px,10vw,160px); padding: clamp(88px,11vw,156px) 0; }
.about-story h2,.about-community h2,.about-principles__header h2,.about-closing h2 { max-width: 760px; margin: 24px 0 0; color: var(--ink); font-family: var(--font-display); font-size: clamp(2.4rem,5.5vw,5.2rem); font-weight: 500; line-height: 1.02; letter-spacing: -.055em; }
.about-story__copy { max-width: 480px; color: var(--muted); font-size: 16px; line-height: 1.8; }
.about-story__copy p { margin: 0 0 18px; }
.about-text-link { display: inline-flex; align-items: center; gap: 12px; margin-top: 8px; color: var(--ink); font-size: 14px; font-weight: 600; text-decoration: none; }
.about-community { display: grid; grid-template-columns: minmax(260px,.72fr) minmax(0,1.28fr); align-items: center; gap: clamp(48px,9vw,132px); padding: clamp(72px,9vw,120px) 0; }
.about-community__copy h2 { font-size: clamp(2rem,4.1vw,3.8rem); }
.about-community__copy > p:last-child { max-width: 500px; margin: 24px 0 0; color: var(--muted); font-size: 16px; line-height: 1.8; }
.about-community__photos { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: start; }
.about-community__photo { min-width: 0; margin: 0; }
.about-community__photo:nth-child(2) { margin-top: 64px; }
.about-community__photo img { display: block; width: 100%; aspect-ratio: 1.12; object-fit: cover; border-radius: 10px; background: var(--surface); }
.about-community__photo figcaption { margin-top: 12px; color: var(--muted); font-size: 12px; line-height: 1.5; }
.about-principles { padding: clamp(52px,7vw,96px) 0; color: #1e1f1d; }
.about-principles__panel { padding: clamp(28px,5vw,64px); border: 1px solid color-mix(in srgb,#1e1f1d 5%,transparent); border-radius: 26px; background: #f4f3f0; }
.about-principles__header { display: grid; grid-template-columns: minmax(0,1.1fr) minmax(260px,.7fr); align-items: end; gap: clamp(32px,8vw,112px); margin-bottom: 42px; }
.about-principles__header .about-section-label { color: #777873; }
.about-principles__header h2 { max-width: 650px; margin-top: 20px; color: #20211f; font-size: clamp(2.15rem,4.4vw,4rem); }
.about-principles__aside { padding-bottom: 5px; }
.about-principles__lead { max-width: 430px; margin: 0; color: #5c5d58; font-size: 15px; line-height: 1.75; }
.about-principles__link { display: inline-flex; align-items: center; gap: 12px; margin-top: 18px; padding-bottom: 4px; border-bottom: 1px solid #9a9b95; color: #242521; font-size: 13px; font-weight: 600; text-decoration: none; }
.about-principles__link span { font-size: 17px; }
.about-principles__photo { position: relative; height: clamp(260px,42vw,500px); margin: 0; overflow: hidden; border-radius: 16px; background: #e5e3dc; }
.about-principles__photo img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 54%; }
.about-principles__photo figcaption { position: absolute; right: 18px; bottom: 18px; display: flex; align-items: center; gap: 9px; padding: 11px 15px; border: 1px solid rgb(255 255 255 / 38%); border-radius: 999px; background: rgb(255 255 255 / 88%); color: #353631; font-size: 12px; backdrop-filter: blur(10px); }
.about-principles__photo-dot { width: 7px; height: 7px; border-radius: 50%; background: #287b61; }
.about-principles__list { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 28px; margin-top: 30px; }
.about-principle { display: grid; grid-template-columns: 28px 1fr; gap: 13px; min-width: 0; padding-top: 18px; border-top: 1px solid rgb(30 31 29 / 14%); }
.about-principle__number { padding-top: 2px; color: #287b61; font-family: var(--font-mono); font-size: 11px; }
.about-principle h3 { margin: 0 0 8px; color: #20211f; font-size: 15px; font-weight: 600; }
.about-principle p { max-width: 360px; margin: 0; color: #666762; font-size: 13px; line-height: 1.7; }
.about-closing { padding: clamp(80px,10vw,136px) 0; }
.about-closing__content { padding: clamp(32px,6vw,80px); border-radius: 12px; background: #103c32; color: #fff; }
.about-closing .about-section-label,.about-closing .about-section-label span { color: #b1d6c3; }
.about-closing__row { display: flex; align-items: flex-end; justify-content: space-between; gap: 40px; margin-top: 28px; }
.about-closing h2 { max-width: 660px; color: #fff; }
.about-closing__row p { margin: 18px 0 0; color: rgb(255 255 255 / 72%); font-size: 15px; }
.about-closing__link { flex-shrink: 0; color: #fff; }
.about-hero__link:hover span { transform: translateY(4px); }
.about-closing__link:hover span { transform: translate(3px,-3px); }
@media (max-width: 760px) {
  .about-hero { min-height: 640px; padding: 124px 24px 48px; }
  .about-story,.about-community { grid-template-columns: 1fr; gap: 28px; padding: 64px 0; }
  .about-story h2,.about-community h2 { margin-top: 20px; }
  .about-story__copy { max-width: none; }
  .about-community__photos { gap: 12px; }
  .about-community__photo:nth-child(2) { margin-top: 36px; }
  .about-community__photo img { aspect-ratio: .88; }
  .about-principles { padding: 48px 0; }
  .about-principles__panel { padding: 28px 20px 22px; border-radius: 20px; }
  .about-principles__header { grid-template-columns: 1fr; gap: 18px; margin-bottom: 26px; }
  .about-principles__photo { height: 300px; margin-right: -4px; margin-left: -4px; }
  .about-principles__photo img { object-position: 55% center; }
  .about-principles__photo figcaption { right: 10px; bottom: 10px; max-width: calc(100% - 20px); padding: 9px 12px; font-size: 10px; }
  .about-principles__list { grid-template-columns: 1fr; gap: 18px; margin-top: 24px; }
  .about-principle { padding-top: 14px; }
  .about-closing { padding: 64px 0; }
  .about-closing__content { padding: 28px 24px; }
  .about-closing__row { align-items: flex-start; flex-direction: column; gap: 24px; }
}
@media (prefers-reduced-motion: reduce) { .about-hero__link span,.about-closing__link span { transition: none; } }
</style>
