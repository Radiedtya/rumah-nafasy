<script setup lang="ts">
import MaskedHeading from '../components/app/MaskedHeading.vue'
import { useTheme } from '../composables/useTheme'

const { theme } = useTheme()

const principles = [
  {
    number: '01',
    title: 'Didengar lebih dulu',
    description: 'Setiap perjalanan dimulai dari ceritamu, tanpa asumsi tentang apa yang seharusnya kamu rasakan.',
  },
  {
    number: '02',
    title: 'Pendampingan yang tepat',
    description: 'Temukan psikolog berlisensi dan pilih cara konsultasi yang terasa nyaman untukmu.',
  },
  {
    number: '03',
    title: 'Aman untuk menjadi diri sendiri',
    description: 'Privasi dan rasa aman menyertai setiap langkah, dari memilih layanan hingga sesi selesai.',
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
          <a class="about-hero__link" href="#cerita">
            Cerita kami
            <span aria-hidden="true">↓</span>
          </a>
        </div>
      </div>
    </section>

    <section id="cerita" class="about-story" aria-labelledby="about-story-title">
      <p class="about-section-label"><span>01</span> Cerita kami</p>
      <div class="about-story__grid">
        <h2 id="about-story-title">Tidak semua hal harus kamu hadapi sendirian.</h2>
        <div class="about-story__copy">
          <p>
            Rumah Nafasy hadir untuk membuat dukungan kesehatan mental terasa lebih dekat. Meminta bantuan bukan tanda kelemahan; itu langkah berani untuk merawat diri.
          </p>
          <p>
            Kami menghubungkanmu dengan psikolog berlisensi melalui pengalaman konsultasi yang aman, fleksibel, dan berpusat pada kebutuhanmu.
          </p>
        </div>
      </div>
    </section>

    <section class="about-principles" aria-labelledby="about-principles-title">
      <div class="about-principles__header">
        <p class="about-section-label"><span>02</span> Cara kami hadir</p>
        <h2 id="about-principles-title">Kecil langkahnya. Berarti dampaknya.</h2>
      </div>
      <div class="about-principles__list">
        <article v-for="principle in principles" :key="principle.number" class="about-principle">
          <span class="about-principle__number">{{ principle.number }}</span>
          <h3>{{ principle.title }}</h3>
          <p>{{ principle.description }}</p>
        </article>
      </div>
    </section>

    <section class="about-closing" aria-labelledby="about-closing-title">
      <p class="about-section-label"><span>03</span> Langkah berikutnya</p>
      <div class="about-closing__row">
        <h2 id="about-closing-title">Kamu boleh mulai dengan satu cerita.</h2>
        <a href="/#cara-kerja" class="about-closing__link">
          Temukan cara yang cocok
          <span aria-hidden="true">↗</span>
        </a>
      </div>
    </section>
  </div>
</template>

<style scoped>
.about-hero {
  position: relative;
  isolation: isolate;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100vw;
  min-height: clamp(680px, 88svh, 980px);
  margin-left: calc(50% - 50vw);
  padding: 148px max(24px, calc((100vw - 1280px) / 2 + 32px)) 72px;
  overflow: hidden;
  background: var(--about-hero-background);
  color: var(--about-hero-foreground);
  transition: background-color 220ms ease, color 220ms ease;
}

.about-hero--light {
  --about-hero-background: #f4f4f0;
  --about-hero-foreground: #171717;
  --about-hero-secondary: rgb(23 23 23 / 76%);
  --about-hero-border: rgb(23 23 23 / 30%);
}

.about-hero--dark {
  --about-hero-background: #080808;
  --about-hero-foreground: #fff;
  --about-hero-secondary: rgb(255 255 255 / 82%);
  --about-hero-border: rgb(255 255 255 / 38%);
}

.about-hero__content {
  width: min(100%, 1080px);
  text-align: center;
}

.about-eyebrow,
.about-section-label {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  font-size: 12px;
  font-weight: 600;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.about-eyebrow {
  margin-bottom: 28px;
  color: var(--about-hero-secondary);
  justify-content: center;
}

.about-eyebrow span {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #bce5a7;
}

.about-hero__heading {
  display: block;
  max-width: 1120px;
  margin: 0 auto;
  color: var(--about-hero-foreground);
}

.about-hero__bottom {
  display: flex;
  align-items: center;
  justify-content: center;
  margin-top: 28px;
}

.about-hero__intro {
  max-width: 440px;
  margin: 0;
  color: var(--about-hero-secondary);
  font-size: 17px;
  line-height: 1.6;
  text-align: center;
}

.about-hero__link,
.about-closing__link {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 18px;
  min-height: 48px;
  padding-bottom: 5px;
  border-bottom: 1px solid currentColor;
  color: var(--about-hero-foreground);
  font-size: 14px;
  font-weight: 600;
  text-decoration: none;
}

.about-hero__link span,
.about-closing__link span {
  font-size: 20px;
  font-weight: 400;
  transition: transform 180ms ease;
}

.about-hero__link:hover span {
  transform: translateY(4px);
}

.about-hero__index {
  position: absolute;
  right: max(24px, calc((100vw - 1280px) / 2 + 32px));
  bottom: 56px;
  display: flex;
  gap: 12px;
  margin: 0;
  color: var(--about-hero-secondary);
  font-size: 11px;
}

.about-hero__index span {
  padding-left: 12px;
  border-left: 1px solid var(--about-hero-border);
}

.about-story,
.about-principles,
.about-closing {
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 76px 0;
  color: var(--text);
}

.about-section-label {
  color: var(--muted);
}

.about-section-label span {
  color: var(--accent);
  font-family: var(--font-mono);
}

.about-story__grid {
  display: grid;
  grid-template-columns: 1.1fr 0.9fr;
  gap: 12%;
  align-items: start;
  margin-top: 34px;
}

.about-story h2,
.about-principles__header h2,
.about-closing h2 {
  max-width: 650px;
  margin: 0;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: clamp(2rem, 4vw, 3.6rem);
  font-weight: 600;
  line-height: 1.08;
}

.about-story__copy {
  max-width: 470px;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.75;
}

.about-story__copy p {
  margin: 0 0 18px;
}

.about-principles {
  max-width: none;
  padding: 76px max(24px, calc((100vw - 1280px) / 2));
  background: var(--surface);
}

.about-principles__header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 32px;
  margin-bottom: 52px;
}

.about-principles__header h2 {
  max-width: 560px;
  font-size: clamp(1.8rem, 3.2vw, 2.8rem);
}

.about-principles__list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  border-top: 1px solid var(--line);
}

.about-principle {
  min-width: 0;
  padding: 24px 24px 0 0;
}

.about-principle + .about-principle {
  padding-left: 24px;
  border-left: 1px solid var(--line);
}

.about-principle__number {
  color: var(--accent);
  font-family: var(--font-mono);
  font-size: 12px;
}

.about-principle h3 {
  margin: 34px 0 10px;
  color: var(--ink);
  font-size: 18px;
  font-weight: 600;
}

.about-principle p {
  max-width: 330px;
  margin: 0;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.7;
}

.about-closing {
  padding-top: 88px;
  padding-bottom: 100px;
}

.about-closing__row {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 32px;
  margin-top: 30px;
}

.about-closing__link {
  color: var(--ink);
}

.about-closing__link:hover span {
  transform: translate(3px, -3px);
}

@media (max-width: 768px) {
  .about-hero {
    min-height: 640px;
    padding: 124px 24px 48px;
  }

  .about-hero__bottom,
  .about-closing__row {
    align-items: flex-start;
    flex-direction: column;
    gap: 20px;
  }

  .about-hero__bottom {
    margin-top: 24px;
  }

  .about-hero__intro {
    max-width: 360px;
    font-size: 15px;
  }

  .about-hero__index {
    right: 24px;
    bottom: 20px;
  }

  .about-story,
  .about-closing {
    padding: 56px 0;
  }

  .about-story__grid {
    grid-template-columns: 1fr;
    gap: 24px;
    margin-top: 28px;
  }

  .about-principles {
    padding: 56px 24px;
  }

  .about-principles__header {
    flex-direction: column;
    gap: 24px;
    margin-bottom: 32px;
  }

  .about-principles__list {
    grid-template-columns: 1fr;
  }

  .about-principle,
  .about-principle + .about-principle {
    padding: 20px 0;
    border-left: 0;
  }

  .about-principle + .about-principle {
    border-top: 1px solid var(--line);
  }

  .about-principle h3 {
    margin-top: 18px;
  }

  .about-closing__row {
    margin-top: 24px;
  }
}

@media (prefers-reduced-motion: reduce) {
  .about-hero__link span,
  .about-closing__link span {
    transition: none;
  }
}
</style>
