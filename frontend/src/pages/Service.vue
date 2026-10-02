<script setup lang="ts">
import { useTheme } from '../composables/useTheme'

const { theme } = useTheme()

const offerings = [
  { number: '01', title: 'Konsultasi psikolog', tag: 'PENDAMPINGAN PERSONAL', description: 'Ruang privat untuk membicarakan hal yang sedang kamu hadapi, memahami pola yang terasa berat, dan menyusun langkah yang mungkin dilakukan.', detail: 'Online · Terjadwal', href: '/form' },
  { number: '02', title: 'Dukungan kesehatan mental', tag: 'MULAI DARI KEBUTUHANMU', description: 'Pilih fokus percakapan sesuai kebutuhan—mulai dari kecemasan, relasi, keluarga, pekerjaan, hingga proses mengenal diri.', detail: 'Bersama psikolog berlisensi', href: '/form' },
  { number: '03', title: 'Ruang belajar komunitas', tag: 'BERTUMBUH BERSAMA', description: 'Kegiatan dan percakapan edukatif untuk membantu komunitas memahami kesehatan mental dengan bahasa yang lebih dekat dan terbuka.', detail: 'Program sesuai jadwal', href: '/gallry' },
]

const steps = [
  { number: '01', title: 'Ceritakan yang kamu cari', description: 'Isi form singkat agar kami memahami kebutuhan dan preferensimu.' },
  { number: '02', title: 'Pilih dukungan yang sesuai', description: 'Kenali pilihan konsultasi dan tentukan jadwal yang tersedia.' },
  { number: '03', title: 'Mulai dengan ritmemu', description: 'Bertemu psikolog secara online di ruang yang nyaman untukmu.' },
]

const faqs = [
  { question: 'Apakah konsultasi dilakukan secara online?', answer: 'Ya. Konsultasi dilakukan secara online sesuai jadwal yang dipilih saat pemesanan. Detail akses sesi akan tersedia setelah proses booking selesai.' },
  { question: 'Apakah saya perlu tahu masalah saya sebelum mulai?', answer: 'Tidak perlu. Kamu boleh mulai dari apa yang terasa, bahkan jika ceritanya belum tersusun rapi. Psikolog akan membantu mengeksplorasinya bersama.' },
  { question: 'Apakah layanan ini cocok untuk kondisi darurat?', answer: 'Layanan ini bukan pengganti bantuan darurat. Jika kamu atau seseorang berada dalam bahaya langsung, hubungi layanan darurat setempat atau pergi ke fasilitas kesehatan terdekat.' },
]
</script>

<template>
  <div class="service-page">
    <section class="service-hero" :class="`service-hero--${theme}`" aria-labelledby="service-title">
      <div class="service-hero__inner">
        <p class="service-eyebrow"><span></span> Rumah Nafasy · Layanan</p>
        <h1 id="service-title">Ruang untuk didengar.<br><em>Dukungan untuk bertumbuh.</em></h1>
        <div class="service-hero__bottom">
          <p>Setiap perjalanan punya titik mulainya sendiri. Temukan dukungan profesional yang terasa pas, dengan ruang aman untuk membicarakan apa pun yang sedang kamu hadapi.</p>
          <a href="#pilihan" class="service-hero__link">Jelajahi layanan <span aria-hidden="true">↓</span></a>
        </div>
        <div class="service-hero__index"><span>RN / 01</span><span>DUKUNGAN YANG DEKAT, LANGKAH YANG BERARTI</span></div>
      </div>
    </section>

    <section id="pilihan" class="service-offerings" aria-labelledby="offerings-title">
      <header class="service-section-head">
        <p class="service-label"><span>01</span> Pilihan dukungan</p>
        <div class="service-section-head__copy">
          <h2 id="offerings-title">Mulai dari yang paling kamu butuhkan.</h2>
          <p>Tak ada satu cara yang cocok untuk semua orang. Pilih ruang yang terasa tepat untukmu hari ini.</p>
        </div>
      </header>
      <div class="service-list">
        <article v-for="item in offerings" :key="item.number" class="service-item">
          <span class="service-item__number">{{ item.number }}</span>
          <div class="service-item__main">
            <p class="service-item__tag">{{ item.tag }}</p>
            <h3>{{ item.title }}</h3>
            <p class="service-item__description">{{ item.description }}</p>
          </div>
          <div class="service-item__aside">
            <span>{{ item.detail }}</span>
            <RouterLink :to="item.href" :aria-label="`Jelajahi ${item.title}`">Jelajahi <span aria-hidden="true">↗</span></RouterLink>
          </div>
        </article>
      </div>
      <p class="service-note"><span aria-hidden="true">✳</span> Pilihan dan ketersediaan layanan dapat berubah. Informasi terbaru akan ditampilkan saat kamu memulai proses pemesanan.</p>
    </section>

    <section class="service-approach" aria-labelledby="approach-title">
      <div class="service-approach__intro">
        <p class="service-label"><span>02</span> Pendekatan kami</p>
        <h2 id="approach-title">Bukan harus punya semua jawaban. Cukup mulai dari ceritamu.</h2>
        <p>Kamu didengar tanpa dihakimi. Psikolog akan menemanimu memahami apa yang penting, dengan menghormati batas dan ritme yang kamu pilih.</p>
      </div>
      <div class="service-approach__principles">
        <div><span>01</span><p><strong>Berpusat padamu</strong> — kebutuhanmu menjadi awal percakapan.</p></div>
        <div><span>02</span><p><strong>Profesional</strong> — didampingi psikolog berlisensi.</p></div>
        <div><span>03</span><p><strong>Menjaga privasi</strong> — ceritamu diperlakukan dengan penuh perhatian.</p></div>
      </div>
    </section>

    <section class="service-process" aria-labelledby="process-title">
      <header class="service-section-head service-section-head--light">
        <p class="service-label"><span>03</span> Alur sederhana</p>
        <div class="service-section-head__copy">
          <h2 id="process-title">Satu langkah kecil, lalu berikutnya.</h2>
          <p>Kamu tetap memegang kendali. Mulai kapan pun kamu siap.</p>
        </div>
      </header>
      <div class="service-steps">
        <article v-for="step in steps" :key="step.number" class="service-step">
          <span>{{ step.number }}</span>
          <h3>{{ step.title }}</h3>
          <p>{{ step.description }}</p>
        </article>
      </div>
      <RouterLink to="/form" class="service-cta">Mulai konsultasi <span aria-hidden="true">↗</span></RouterLink>
    </section>

    <section class="service-faq" aria-labelledby="faq-title">
      <div class="service-faq__heading">
        <p class="service-label"><span>04</span> Sebelum mulai</p>
        <h2 id="faq-title">Pertanyaan yang mungkin kamu punya.</h2>
      </div>
      <div class="service-faq__list">
        <details v-for="(faq, index) in faqs" :key="faq.question">
          <summary><span class="service-faq__number">0{{ index + 1 }}</span>{{ faq.question }}<span class="service-faq__plus" aria-hidden="true">+</span></summary>
          <p>{{ faq.answer }}</p>
        </details>
      </div>
    </section>

    <section class="service-closing" aria-labelledby="closing-title">
      <p class="service-label"><span>05</span> Saat kamu siap</p>
      <div class="service-closing__row">
        <div><h2 id="closing-title">Kamu tak harus melewati semuanya sendirian.</h2><p>Mulai dengan satu cerita. Kita cari langkah selanjutnya bersama.</p></div>
        <RouterLink to="/form">Temukan dukunganmu <span aria-hidden="true">↗</span></RouterLink>
      </div>
    </section>
  </div>
</template>

<style scoped>
.service-page { --service-accent: #287b61; width: 100%; color: var(--text); }
.service-hero { display: flex; align-items: flex-end; width: 100vw; min-height: min(820px, 92svh); margin-left: calc(50% - 50vw); padding: 150px max(24px, calc((100vw - 1280px) / 2 + 32px)) 36px; background: #103c32; color: #fff; }
.service-hero--dark { background: #090e0c; }
.service-hero__inner { width: min(100%, 1180px); margin: auto; }
.service-eyebrow,.service-label { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; }
.service-eyebrow { color: rgb(255 255 255 / 75%); }
.service-eyebrow span { width: 7px; height: 7px; border-radius: 50%; background: #bce5a7; }
.service-hero h1 { max-width: 1050px; margin: clamp(70px,11vw,140px) 0 0; font-size: clamp(3.5rem,9.2vw,8.2rem); font-weight: 500; line-height: .92; letter-spacing: -.075em; }
.service-hero h1 em { color: #bce5a7; font-style: normal; }
.service-hero__bottom { display: flex; align-items: flex-end; justify-content: space-between; gap: 32px; margin-top: clamp(44px,7vw,92px); }
.service-hero__bottom p { max-width: 490px; margin: 0; color: rgb(255 255 255 / 75%); font-size: clamp(15px,1.5vw,18px); line-height: 1.7; }
.service-hero__link { display: inline-flex; align-items: center; gap: 28px; padding: 0 0 9px; border-bottom: 1px solid rgb(255 255 255 / 55%); color: white; font-size: 14px; font-weight: 500; text-decoration: none; white-space: nowrap; }
.service-hero__link span { font-size: 20px; }
.service-hero__index { display: flex; justify-content: space-between; margin-top: 80px; padding-top: 14px; border-top: 1px solid rgb(255 255 255 / 20%); color: rgb(255 255 255 / 55%); font-family: var(--font-mono); font-size: 10px; letter-spacing: .08em; }
.service-offerings,.service-approach,.service-process,.service-faq,.service-closing { padding: clamp(80px,10vw,140px) 0; }
.service-section-head { display: grid; grid-template-columns: .65fr 1.35fr; gap: 48px; align-items: start; }
.service-label { color: var(--muted); }
.service-label span { color: var(--service-accent); font-family: var(--font-mono); }
.service-section-head__copy { display: grid; grid-template-columns: minmax(0,1fr) minmax(200px,.58fr); gap: 40px; align-items: end; }
.service-section-head h2,.service-approach h2,.service-faq h2,.service-closing h2 { margin: 0; color: var(--ink); font-size: clamp(2.25rem,5vw,4.7rem); font-weight: 500; line-height: 1; letter-spacing: -.065em; }
.service-section-head__copy > p { margin: 0 0 4px; color: var(--muted); font-size: 14px; line-height: 1.7; }
.service-list { margin-top: 68px; border-top: 1px solid var(--line); }
.service-item { display: grid; grid-template-columns: 56px minmax(0,1fr) 210px; gap: 26px; padding: 32px 0; border-bottom: 1px solid var(--line); }
.service-item__number { padding-top: 3px; color: var(--service-accent); font-family: var(--font-mono); font-size: 12px; }
.service-item__tag { margin: 0 0 10px; color: var(--muted); font-size: 10px; font-weight: 600; letter-spacing: .1em; }
.service-item h3 { margin: 0; color: var(--ink); font-size: clamp(1.55rem,3vw,2.5rem); font-weight: 500; letter-spacing: -.05em; }
.service-item__description { max-width: 640px; margin: 13px 0 0; color: var(--muted); font-size: 14px; line-height: 1.75; }
.service-item__aside { display: flex; flex-direction: column; align-items: flex-start; justify-content: space-between; gap: 24px; padding: 3px 0; color: var(--muted); font-size: 12px; }
.service-item__aside a { display: inline-flex; align-items: center; gap: 12px; color: var(--ink); font-size: 13px; font-weight: 600; text-decoration: none; }
.service-note { display: flex; align-items: flex-start; gap: 10px; max-width: 660px; margin: 24px 0 0 82px; color: var(--muted); font-size: 12px; line-height: 1.7; }
.service-note span { color: var(--service-accent); }
.service-approach { display: grid; grid-template-columns: 1fr .72fr; gap: clamp(48px,11vw,160px); align-items: end; padding-top: 40px; }
.service-approach__intro h2 { max-width: 700px; margin-top: 28px; font-size: clamp(2.2rem,4.8vw,4.4rem); }
.service-approach__intro > p:last-child { max-width: 560px; margin: 24px 0 0; color: var(--muted); font-size: 15px; line-height: 1.8; }
.service-approach__principles { border-top: 1px solid var(--line); }
.service-approach__principles > div { display: grid; grid-template-columns: 34px 1fr; gap: 12px; padding: 18px 0; border-bottom: 1px solid var(--line); }
.service-approach__principles span { color: var(--service-accent); font-family: var(--font-mono); font-size: 11px; }
.service-approach__principles p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.7; }
.service-approach__principles strong { color: var(--ink); font-weight: 600; }
.service-process { margin: 20px -1px 0; padding: clamp(36px,6vw,76px); border-radius: 22px; background: #103c32; color: white; }
.service-process .service-label,.service-process .service-label span { color: #b1d6c3; }
.service-section-head--light h2 { color: white; }
.service-section-head--light .service-section-head__copy > p { color: rgb(255 255 255 / 65%); }
.service-steps { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; margin-top: 68px; }
.service-step { min-height: 200px; padding-top: 18px; border-top: 1px solid rgb(255 255 255 / 30%); }
.service-step > span { color: #bce5a7; font-family: var(--font-mono); font-size: 11px; }
.service-step h3 { margin: 38px 0 10px; color: white; font-size: 19px; font-weight: 500; letter-spacing: -.03em; }
.service-step p { max-width: 290px; margin: 0; color: rgb(255 255 255 / 68%); font-size: 13px; line-height: 1.7; }
.service-cta { display: inline-flex; align-items: center; gap: 30px; min-height: 50px; margin-top: 32px; padding: 0 20px; border-radius: 999px; background: #bce5a7; color: #163a2d; font-size: 13px; font-weight: 600; text-decoration: none; }
.service-faq { display: grid; grid-template-columns: .72fr 1.28fr; gap: clamp(48px,10vw,140px); }
.service-faq h2 { max-width: 410px; margin-top: 24px; font-size: clamp(2rem,4vw,3.5rem); }
.service-faq__list { border-top: 1px solid var(--line); }
.service-faq details { border-bottom: 1px solid var(--line); }
.service-faq summary { display: grid; grid-template-columns: 40px 1fr 24px; gap: 10px; align-items: center; min-height: 70px; color: var(--ink); font-size: 14px; font-weight: 500; cursor: pointer; list-style: none; }
.service-faq summary::-webkit-details-marker { display: none; }
.service-faq__number { color: var(--service-accent); font-family: var(--font-mono); font-size: 10px; }
.service-faq__plus { color: var(--muted); font-size: 20px; font-weight: 300; text-align: right; transition: transform .2s; }
.service-faq details[open] .service-faq__plus { transform: rotate(45deg); }
.service-faq details > p { max-width: 600px; margin: -2px 34px 22px 50px; color: var(--muted); font-size: 13px; line-height: 1.8; }
.service-closing { padding-top: 24px; }
.service-closing__row { display: flex; align-items: flex-end; justify-content: space-between; gap: 40px; margin-top: 28px; padding: clamp(32px,6vw,76px); border-radius: 12px; background: #f4f3f0; }
.service-closing h2 { max-width: 690px; font-size: clamp(2.25rem,5vw,4.5rem); }
.service-closing__row p { margin: 16px 0 0; color: #62635e; font-size: 14px; }
.service-closing__row a { display: inline-flex; align-items: center; gap: 20px; flex-shrink: 0; padding-bottom: 8px; border-bottom: 1px solid #989993; color: #20211f; font-size: 13px; font-weight: 600; text-decoration: none; }
@media (max-width: 760px) {
  .service-hero { min-height: 760px; padding: 132px 24px 28px; }
  .service-hero h1 { margin-top: 100px; font-size: clamp(3.1rem,13vw,5.2rem); }
  .service-hero__bottom { align-items: flex-start; flex-direction: column; gap: 24px; margin-top: 48px; }
  .service-hero__bottom p { font-size: 15px; }
  .service-hero__index { gap: 20px; margin-top: 62px; font-size: 8px; }
  .service-section-head,.service-section-head__copy,.service-approach,.service-faq { grid-template-columns: 1fr; gap: 24px; }
  .service-section-head__copy { gap: 16px; }
  .service-list { margin-top: 42px; }
  .service-item { grid-template-columns: 30px minmax(0,1fr); gap: 14px; padding: 25px 0; }
  .service-item__aside { grid-column: 2; flex-direction: row; align-items: center; }
  .service-note { margin-left: 44px; }
  .service-approach { padding-top: 10px; }
  .service-process { margin: 0 -4px; padding: 32px 22px; border-radius: 18px; }
  .service-steps { grid-template-columns: 1fr; gap: 18px; margin-top: 44px; }
  .service-step { min-height: auto; padding: 16px 0 20px; }
  .service-step h3 { margin-top: 22px; }
  .service-faq__heading { margin-bottom: 6px; }
  .service-closing { padding-bottom: 70px; }
  .service-closing__row { align-items: flex-start; flex-direction: column; gap: 24px; padding: 30px 24px; }
}
@media (prefers-reduced-motion: reduce) { .service-faq__plus { transition: none; } }
</style>
