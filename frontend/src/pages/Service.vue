<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { gsap } from 'gsap'
import { useTheme } from '../composables/useTheme'
import InfiniteSpiral from '../components/InfiniteSpiral.vue'
import ScrollStack, { ScrollStackItem } from '../components/ScrollStack.vue'
import PixelTransition from '../components/PixelTransition.vue'

const { theme } = useTheme()
const heroCopy = ref<HTMLElement | null>(null)
let heroAnimation: gsap.core.Timeline | null = null
let processWordAnimation: gsap.core.Timeline | null = null

onMounted(() => {
  const processWord = document.querySelector<HTMLElement>('.service-process__word')
  const nextWord = processWord?.querySelector<HTMLElement>('.service-process__word-next')
  if (processWord && nextWord && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    const words = ['memulai.', 'memahami.', 'bertumbuh.']
    let wordIndex = 0
    let activeWord = processWord.querySelector<HTMLElement>('.service-process__word-current')!
    let incomingWord = nextWord

    const animateNextWord = () => {
      processWordAnimation = gsap.timeline({
        onComplete: () => {
          wordIndex = (wordIndex + 1) % words.length
          const previousWord = activeWord
          activeWord = incomingWord
          incomingWord = previousWord
          incomingWord.textContent = words[(wordIndex + 1) % words.length]
          gsap.set(incomingWord, { y: 8, autoAlpha: 0 })
          animateNextWord()
        },
      })
        .to({}, { duration: 1.5 })
        .to(activeWord, { y: -8, autoAlpha: 0, duration: 0.25, ease: 'power1.in' }, 0)
        .fromTo(incomingWord,
          { y: 8, autoAlpha: 0 },
          { y: 0, autoAlpha: 1, duration: 0.3, ease: 'power1.out' },
          0,
        )
    }

    animateNextWord()
  }

  if (!heroCopy.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return
  const kicker = heroCopy.value.querySelector('.service-hero__kicker')
  const title = heroCopy.value.querySelector('h1')
  const description = heroCopy.value.querySelector('.service-hero__description')
  const facts = heroCopy.value.querySelectorAll('.service-hero__facts span')

  heroAnimation = gsap.timeline({ delay: 0.12, defaults: { ease: 'power3.out' } })
    .fromTo(kicker, { autoAlpha: 0, x: -20 }, { autoAlpha: 1, x: 0, duration: 0.65 })
    .fromTo(title,
      { autoAlpha: 0, y: 42, clipPath: 'inset(0 0 100% 0)' },
      { autoAlpha: 1, y: 0, clipPath: 'inset(0 0 0% 0)', duration: 1.05, ease: 'power4.out' },
      '-=0.28',
    )
    .fromTo(description,
      { autoAlpha: 0, y: 20, filter: 'blur(7px)' },
      { autoAlpha: 1, y: 0, filter: 'blur(0px)', duration: 0.8 },
      '-=0.48',
    )
    .fromTo(facts,
      { autoAlpha: 0, y: 12 },
      { autoAlpha: 1, y: 0, duration: 0.55, stagger: 0.12 },
      '-=0.38',
    )
})

onUnmounted(() => {
  heroAnimation?.kill()
  processWordAnimation?.kill()
})

const spiralImages = [
  { src: '/images/ilustration/guru.png', alt: 'guru' },
  { src: '/images/ilustration/sd.png', alt: 'Sadar diri' },
  { src: '/images/ilustration/keluarga.png', alt: 'keluarga' },
  { src: '/images/ilustration/smk.png', alt: 'smk' },
  { src: '/images/ilustration/kantor.png', alt: 'Kantor' },
]

const offerings = [
  { number: '01', title: 'Konsultasi individual', tag: 'RUANG PRIVAT BERSAMA PSIKOLOG', description: 'Bicarakan kecemasan, suasana hati, relasi, pekerjaan, atau hal lain yang terasa berat. Psikolog membantu memahami pola dan menentukan langkah yang realistis.', cost: 'Mulai Rp100.000', unit: 'acuan / 60 menit', image: '/images/album/WhatsApp%20Image%202026-09-23%20at%2011.04.53.jpeg', alt: 'Sesi konsultasi individual bersama psikolog', benefits: ['Sesi privat 1:1 dengan psikolog', 'Pilihan konsultasi video atau tatap muka', 'Durasi sesi 30, 60, atau 90 menit', 'Pembayaran langsung setelah sesi'] },
  { number: '02', title: 'Konsultasi pasangan & keluarga', tag: 'MEMAHAMI RELASI BERSAMA', description: 'Ruang percakapan untuk melihat dinamika hubungan, memperbaiki komunikasi, dan mencari cara saling memahami dengan pendampingan profesional.', cost: 'Acuan biaya ditampilkan saat booking', unit: 'sesuai kategori & psikolog', image: '/images/album/WhatsApp%20Image%202026-09-23%20at%2011.04.54.jpeg', alt: 'Ruang berbagi untuk pasangan dan keluarga', benefits: ['Fokus pada pola komunikasi dan relasi', 'Pendampingan sesuai kebutuhan bersama', 'Jadwal dan format mengikuti ketersediaan', 'Nominal akhir disepakati dengan psikolog'] },
  { number: '03', title: 'Ruang belajar komunitas', tag: 'EDUKASI & PERTUMBUHAN BERSAMA', description: 'Kegiatan edukatif untuk komunitas, sekolah, atau organisasi yang ingin memahami kesehatan mental dengan bahasa yang dekat dan terbuka.', cost: 'Biaya menyesuaikan program', unit: 'konsultasikan kebutuhanmu', image: '/images/album/WhatsApp%20Image%202026-09-23%20at%2011.05.00%20(2).jpeg', alt: 'Kegiatan komunitas Rumah Nafasy', benefits: ['Topik dapat disesuaikan dengan peserta', 'Format diskusi atau edukasi kelompok', 'Cocok untuk komunitas dan organisasi', 'Rincian program dan biaya dikonfirmasi lebih dulu'] },
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
        <div class="service-hero__layout">
          <div ref="heroCopy" class="service-hero__copy">
            <p class="service-hero__kicker">RUANG AMAN UNTUK MEMULAI</p>
            <h1 id="service-title">Kamu layak <em>didengar.</em></h1>
            <p class="service-hero__description">Dukungan profesional untuk memahami apa yang kamu rasakan, dengan cara dan ritme yang terasa nyaman.</p>
            <div class="service-hero__facts"><span><i></i> Psikolog berlisensi</span><span><i></i> Konsultasi online</span></div>
          </div>
          <div class="service-hero__visual" aria-label="Galeri suasana Rumah Nafasy">
            <InfiniteSpiral :items="spiralImages" animation-mode="all" :speed="0.32" :radius="150" :card-width="128" :card-height="128" :vertical-spacing="76" :perspective="1000" :card-radius="12" :center-scale="1.16" :edge-blur="3" :cards-per-turn="7" pause-on-hover />
          </div>
        </div>
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
    </section>

    <section class="service-offering-cards" aria-label="Pilihan dukungan">
      <ScrollStack class-name="service-scroll-stack" :base-scale="0.97" :item-distance="120" :item-stack-distance="28" stack-position="8%" scale-end-position="0%">
        <ScrollStackItem v-for="item in offerings" :key="item.number" item-class-name="service-stack-card">
          <div class="service-stack-card__copy">
            <p class="service-item__tag"><span>{{ item.number }}</span>{{ item.tag }}</p>
            <h3>{{ item.title }}</h3>
            <p class="service-item__description">{{ item.description }}</p>
            <div class="service-stack-card__cost"><span>{{ item.cost }}</span><small>{{ item.unit }}</small></div>
            <ul><li v-for="benefit in item.benefits" :key="benefit">{{ benefit }}</li></ul>
          </div>
          <figure class="service-stack-card__image"><img :src="item.image" :alt="item.alt" loading="lazy"></figure>
        </ScrollStackItem>
      </ScrollStack>
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

    <section class="service-story" aria-label="Suasana pendampingan Rumah Nafasy">
      <figure class="service-story__photo service-story__photo--main">
        <img src="/images/album/WhatsApp%20Image%202026-09-23%20at%2011.04.53.jpeg" alt="Psikolog mendengarkan cerita klien dalam suasana konsultasi yang tenang" loading="lazy">
        <figcaption><span></span> Percakapan yang berpusat pada dirimu</figcaption>
      </figure>
      <div class="service-story__side">
        <figure class="service-story__photo service-story__photo--community">
          <PixelTransition :grid-size="12" pixel-color="#f4f3f0" :animation-step-duration="0.4" class="service-story__transition">
            <template #first>
              <img src="/images/album/WhatsApp%20Image%202026-09-23%20at%2011.05.00%20(2).jpeg" alt="Suasana kegiatan komunitas Rumah Nafasy yang hangat dan terbuka" loading="lazy">
            </template>
            <template #second>
              <div class="service-story__reveal">
                <img src="/images/og/anime.png" alt="" aria-hidden="true" loading="lazy">
                <span>RUANG UNTUK TUMBUH BERSAMA</span>
              </div>
            </template>
          </PixelTransition>
          <figcaption>Ruang untuk saling memahami</figcaption>
        </figure>
        <div class="service-story__caption"><span>RUANG AMAN · HADIR DENGAN EMPATI</span><p>Dukungan terasa lebih dekat saat kita diberi ruang untuk didengar, belajar, dan tumbuh bersama.</p></div>
      </div>
    </section>

    <section class="service-process" aria-labelledby="process-title">
      <header class="service-process__heading">
        <h2 id="process-title">Satu langkah kecil untuk <span class="service-process__word"><span class="service-process__word-current">memulai.</span><span class="service-process__word-next" aria-hidden="true">memahami.</span></span></h2>
      </header>
      <div class="service-steps">
        <article v-for="step in steps" :key="step.number" class="service-step">
          <span>{{ step.number }}</span>
          <h3>{{ step.title }}</h3>
          <p>{{ step.description }}</p>
        </article>
      </div>
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
        <p class="service-closing__note">Rumah Nafasy · Ruang untuk kembali pulih</p>
      </div>
    </section>
  </div>
</template>

<style scoped>
.service-page { --service-accent: #287b61; width: 100%; color: var(--text); }
.service-hero { display: flex; align-items: center; width: 100vw; min-height: min(760px, calc(100svh - 58px)); margin-left: calc(50% - 50vw); padding: 104px clamp(24px, 5vw, 80px) 34px; background: #f4f3f0; color: #20211f; }
.service-hero--dark { background: #090e0c; }
.service-hero--dark { color: #f4f3f0; }
.service-hero__inner { display: flex; flex-direction: column; justify-content: space-between; width: min(100%, 1180px); min-height: min(590px, calc(100svh - 196px)); margin: auto; }
.service-eyebrow,.service-label { display: flex; align-items: center; gap: 10px; margin: 0; font-size: 11px; font-weight: 600; letter-spacing: .09em; text-transform: uppercase; }
.service-hero__topline,.service-hero__bottomline { display: flex; align-items: center; justify-content: space-between; gap: 20px; }
.service-eyebrow { color: #51534e; }
.service-hero--dark .service-eyebrow { color: rgb(255 255 255 / 75%); }
.service-eyebrow span { width: 7px; height: 7px; border-radius: 50%; background: #287b61; }
.service-eyebrow i { color: #a1a29d; font-style: normal; }
.service-hero__edition,.service-hero__bottomline { color: #777873; font-family: var(--font-mono); font-size: 9px; letter-spacing: .08em; }
.service-hero--dark .service-hero__edition,.service-hero--dark .service-hero__bottomline { color: rgb(255 255 255 / 56%); }
.service-hero__layout { display: grid; grid-template-columns: minmax(0,1fr) minmax(360px,.92fr); align-items: center; gap: clamp(36px,6vw,90px); margin: 46px 0 0; }
.service-hero__copy { position: relative; top: -12px; max-width: 660px; padding-left: clamp(0px,2.5vw,36px); }
.service-hero__kicker { margin: 0 0 20px; color: #287b61; font-size: 10px; font-weight: 600; letter-spacing: .1em; }
.service-hero--dark .service-hero__kicker { color: #bce5a7; }
.service-hero h1 { max-width: 440px; margin: 0; color: #20211f; font-size: clamp(2.8rem,5.3vw,5rem); font-weight: 500; line-height: .99; letter-spacing: -.07em; }
.service-hero--dark h1 { color: #fff; }
.service-hero h1 em { color: #287b61; font-style: normal; }
.service-hero--dark h1 em { color: #bce5a7; }
.service-hero__description { max-width: 390px; margin: 22px 0 0; color: #62635e; font-size: 15px; line-height: 1.75; }
.service-hero--dark .service-hero__description { color: rgb(255 255 255 / 68%); }
.service-hero__facts { display: flex; flex-wrap: wrap; gap: 9px 18px; margin-top: 26px; color: #555650; font-size: 11px; }
.service-hero--dark .service-hero__facts { color: rgb(255 255 255 / 75%); }
.service-hero__facts span { display: inline-flex; align-items: center; gap: 7px; }
.service-hero__facts i { width: 6px; height: 6px; border-radius: 50%; background: #287b61; }
.service-hero--dark .service-hero__facts i { background: #bce5a7; }
.service-hero__visual { position: relative; height: clamp(500px,48vw,600px); margin: 0; overflow: hidden; background: transparent; }
.service-hero__visual img { display: block; width: 100%; height: 100%; object-fit: cover; object-position: center 54%; }
.service-hero__visual figcaption { position: absolute; right: 14px; bottom: 14px; left: 14px; display: flex; justify-content: space-between; gap: 12px; padding: 11px 13px; border: 1px solid rgb(255 255 255 / 45%); border-radius: 6px; background: rgb(255 255 255 / 88%); color: #33342f; font-size: 10px; backdrop-filter: blur(12px); }
.service-hero__visual figcaption span:first-child { color: #287b61; font-family: var(--font-mono); }
.service-hero__bottomline { padding-top: 12px; border-top: 1px solid rgb(32 33 31 / 17%); }
.service-hero--dark .service-hero__bottomline { border-color: rgb(255 255 255 / 20%); }
.service-offerings,.service-approach,.service-process,.service-faq,.service-closing { padding: clamp(80px,10vw,140px) 0; }
.service-offerings { min-height: 46svh; display: flex; flex-direction: column; justify-content: center; }
.service-offering-cards { padding: 0 0 clamp(220px,30svh,360px); }
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
.service-item__aside { align-items: flex-end; }
.service-note { display: flex; align-items: flex-start; gap: 10px; max-width: 660px; margin: 24px 0 0 82px; color: var(--muted); font-size: 12px; line-height: 1.7; }
.service-note span { color: var(--service-accent); }
.service-scroll-stack { position: relative; width: 100%; overflow: visible; }
.service-scroll-stack :deep(.service-stack-card) { position: relative; display: grid; grid-template-columns: minmax(0,1fr) minmax(280px,.86fr); gap: clamp(24px,4vw,52px); width: 100%; height: 68svh; min-height: 0; margin: 0; padding: clamp(24px,3.5vw,44px); overflow: hidden; border: 1px solid var(--line); border-radius: 22px; background: var(--surface); box-shadow: 0 20px 56px rgb(20 34 27 / 9%); transform-origin: top center; }
.service-stack-card__copy { display: flex; flex-direction: column; align-items: flex-start; justify-content: center; }
.service-stack-card__copy .service-item__tag { display: flex; align-items: center; gap: 10px; }
.service-stack-card__copy .service-item__tag span { display: grid; width: 30px; height: 30px; place-items: center; border-radius: 50%; background: #dff0e4; color: #287b61; font-family: var(--font-mono); }
.service-stack-card__copy h3 { max-width: 520px; margin: 12px 0 0; color: var(--ink); font-size: clamp(2rem,4vw,3.6rem); font-weight: 500; line-height: 1; letter-spacing: -.065em; }
.service-stack-card__copy .service-item__description { max-width: 490px; margin-top: 18px; }
.service-stack-card__cost { display: flex; flex-direction: column; gap: 4px; margin-top: 24px; color: var(--service-accent); }
.service-stack-card__cost span { font-size: clamp(1.1rem,2vw,1.45rem); font-weight: 600; letter-spacing: -.04em; }
.service-stack-card__cost small { color: var(--muted); font-size: 11px; }
.service-stack-card__copy ul { display: grid; gap: 9px; margin: 22px 0 0; padding: 0; color: var(--ink); font-size: 12px; list-style: none; }
.service-stack-card__copy li { display: flex; align-items: flex-start; gap: 9px; }
.service-stack-card__copy li::before { content: '✓'; color: var(--service-accent); }
.service-stack-card__image { min-height: 0; height: 100%; margin: 0; overflow: hidden; border-radius: 16px; background: var(--line); }
.service-stack-card__image img { display: block; width: 100%; height: 100%; min-height: 0; object-fit: cover; }
.service-approach { display: grid; grid-template-columns: 1fr .72fr; gap: clamp(48px,11vw,160px); align-items: end; padding-top: clamp(72px,9vw,120px); }
.service-approach__intro h2 { max-width: 700px; margin-top: 28px; font-size: clamp(2.2rem,4.8vw,4.4rem); }
.service-approach__intro > p:last-child { max-width: 560px; margin: 24px 0 0; color: var(--muted); font-size: 15px; line-height: 1.8; }
.service-approach__principles { border-top: 1px solid var(--line); }
.service-approach__principles > div { display: grid; grid-template-columns: 34px 1fr; gap: 12px; padding: 18px 0; border-bottom: 1px solid var(--line); }
.service-approach__principles span { color: var(--service-accent); font-family: var(--font-mono); font-size: 11px; }
.service-approach__principles p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.7; }
.service-approach__principles strong { color: var(--ink); font-weight: 600; }
.service-story { display: grid; grid-template-columns: 1.35fr .65fr; gap: 22px; padding: 20px 0 clamp(80px,10vw,140px); }
.service-story figure { position: relative; margin: 0; overflow: hidden; border-radius: 14px; background: var(--surface); }
.service-story__photo--main { min-height: 520px; }
.service-story__photo img { display: block; width: 100%; height: 100%; object-fit: cover; }
.service-story__photo--main img { position: absolute; inset: 0; object-position: center 54%; }
.service-story__photo figcaption { position: absolute; right: 16px; bottom: 16px; left: 16px; display: flex; align-items: center; gap: 9px; width: fit-content; max-width: calc(100% - 32px); padding: 11px 14px; border: 1px solid rgb(255 255 255 / 40%); border-radius: 999px; background: rgb(255 255 255 / 88%); color: #30312d; font-size: 11px; backdrop-filter: blur(10px); }
.service-story__photo figcaption span { width: 7px; height: 7px; flex: none; border-radius: 50%; background: var(--service-accent); }
.service-story__side { display: flex; flex-direction: column; gap: 22px; }
.service-story__photo--community { flex: 1; min-height: 280px; }
.service-story__transition { position: absolute; inset: 0; }
.service-story__reveal { position: relative; display: grid; width: 100%; height: 100%; place-items: center; }
.service-story__reveal::after { position: absolute; inset: 0; background: linear-gradient(180deg, rgb(18 33 27 / 8%), rgb(18 33 27 / 58%)); content: ''; }
.service-story__reveal img { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
.service-story__reveal span { z-index: 1; padding: 10px 14px; border: 1px solid rgb(255 255 255 / 58%); border-radius: 999px; color: white; font-family: var(--font-mono); font-size: 10px; letter-spacing: .08em; }
.service-story__caption { padding: 5px 0 0; }
.service-story__caption > span { color: var(--service-accent); font-family: var(--font-mono); font-size: 9px; letter-spacing: .08em; }
.service-story__caption p { max-width: 300px; margin: 12px 0 0; color: var(--muted); font-family: var(--font-display); font-size: clamp(1.3rem,2.4vw,2rem); line-height: 1.2; letter-spacing: -.04em; }
.service-process { margin: 20px -1px 0; padding: clamp(36px,6vw,76px); border-radius: 22px; background: #103c32; color: white; }
.service-process__heading { text-align: center; }
.service-process__heading h2 { max-width: 820px; margin: 0 auto; color: white; font-size: clamp(2.25rem,5vw,4.7rem); font-weight: 500; line-height: 1; letter-spacing: -.065em; }
.service-process__word { position: relative; display: inline-block; width: 7.5em; height: 1em; text-align: center; vertical-align: bottom; }
.service-process__word-current,.service-process__word-next { position: absolute; inset: 0 auto auto 0; display: block; width: 100%; white-space: nowrap; }
.service-process__word-next { visibility: hidden; }
.service-steps { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; margin-top: 68px; }
.service-step { min-height: 200px; padding-top: 18px; border-top: 1px solid rgb(255 255 255 / 30%); }
.service-step > span { color: #bce5a7; font-family: var(--font-mono); font-size: 11px; }
.service-step h3 { margin: 38px 0 10px; color: white; font-size: 19px; font-weight: 500; letter-spacing: -.03em; }
.service-step p { max-width: 290px; margin: 0; color: rgb(255 255 255 / 68%); font-size: 13px; line-height: 1.7; }
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
.service-closing h2 { max-width: 690px; color: #000; font-size: clamp(2.25rem,5vw,4.5rem); }
.service-closing__row p { margin: 16px 0 0; color: #62635e; font-size: 14px; }
.service-closing__note { flex-shrink: 0; margin: 0 0 8px; color: #62635e; font-family: var(--font-mono); font-size: 10px; letter-spacing: .04em; }
@media (max-width: 760px) {
  .service-hero { min-height: auto; padding: 98px 20px 26px; }
  .service-hero__inner { min-height: 0; gap: 0; }
  .service-hero__edition { max-width: 120px; text-align: right; line-height: 1.5; }
  .service-hero__layout { grid-template-columns: 1fr; gap: 22px; margin: 36px 0 26px; }
  .service-hero__copy { top: -6px; padding-left: 0; }
  .service-hero h1 { max-width: 360px; font-size: clamp(2.8rem,11vw,4rem); }
  .service-hero__description { max-width: 350px; margin-top: 17px; font-size: 14px; }
  .service-hero__facts { margin-top: 20px; }
  .service-hero__visual { height: clamp(360px,92vw,420px); }
  .service-hero__bottomline { font-size: 8px; }
  .service-section-head,.service-section-head__copy,.service-approach,.service-faq { grid-template-columns: 1fr; gap: 24px; }
  .service-section-head__copy { gap: 16px; }
  .service-list { margin-top: 42px; }
  .service-offerings { min-height: 0; padding: 72px 0 40px; }
  .service-offering-cards { padding-bottom: clamp(180px,28svh,280px); }
  .service-scroll-stack :deep(.service-stack-card) { grid-template-columns: 1fr; height: 78svh; gap: 12px; padding: 18px 16px; border-radius: 18px; }
  .service-stack-card__copy .service-item__tag { margin-bottom: 4px; font-size: 9px; }
  .service-stack-card__copy h3 { margin-top: 6px; font-size: clamp(1.65rem,7vw,2.25rem); }
  .service-stack-card__copy .service-item__description { margin-top: 9px; font-size: 12px; line-height: 1.55; }
  .service-stack-card__cost { gap: 2px; margin-top: 10px; }
  .service-stack-card__copy ul { gap: 5px; margin-top: 10px; font-size: 10px; }
  .service-stack-card__image,.service-stack-card__image img { min-height: 0; height: 17svh; }
  .service-item { grid-template-columns: 30px minmax(0,1fr); gap: 14px; padding: 25px 0; }
  .service-item__aside { grid-column: 2; flex-direction: row; align-items: center; }
  .service-note { margin-left: 44px; }
  .service-approach { padding-top: 64px; }
  .service-story { grid-template-columns: 1fr; gap: 14px; padding: 0 0 72px; }
  .service-story__photo--main { min-height: 360px; }
  .service-story__side { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; align-items: center; }
  .service-story__photo--community { min-height: 220px; }
  .service-story__caption p { font-size: 1.25rem; }
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
