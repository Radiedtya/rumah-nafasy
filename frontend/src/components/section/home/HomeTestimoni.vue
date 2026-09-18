<template>
  <section
    id="ulasan"
    aria-labelledby="testimoni-heading"
    class="testimoni-section"
    @mouseenter="pause"
    @mouseleave="resume"
  >

    <!-- ── HEADING ROW ── -->
    <div class="testimoni-top">
      <div class="testimoni-headline">
        <p class="testimoni-eyebrow">Kata Mereka</p>
        <h2 id="testimoni-heading">
          Baca ulasan,<br />
          <em>konsultasi dengan yakin.</em>
        </h2>
        <p class="testimoni-rating-line">
          <span class="rating-score">4.9 / 5</span>
          <span class="rating-stars" aria-hidden="true">★★★★★</span>
          <span class="rating-label">Berdasarkan 10.000+ sesi</span>
        </p>
      </div>
    </div>

    <!-- ── CAROUSEL ROW ── -->
    <div class="testimoni-row">

      <!-- Kiri: label + progress + arrows -->
      <div class="testimoni-sidebar">
        <span class="sidebar-quote" aria-hidden="true">"</span>
        <p class="sidebar-label">Apa yang klien<br />kami rasakan</p>

        <div class="sidebar-arrows" aria-label="Navigasi ulasan">
          <button type="button" aria-label="Ulasan sebelumnya" @click="manualScroll(-1)">←</button>
          <!-- Progress track -->
          <div class="progress-track" aria-hidden="true">
            <span class="progress-fill" :style="{ width: progress + '%' }"></span>
          </div>
          <button type="button" aria-label="Ulasan berikutnya" @click="manualScroll(1)">→</button>
        </div>
      </div>

      <!-- Kanan: scrollable cards -->
      <div ref="trackEl" class="testimoni-track">
        <article
          v-for="(t, i) in loopedTestimoni"
          :key="i"
          class="testimoni-card"
        >
          <p class="card-quote">{{ t.quote }}</p>

          <div class="card-stars" :aria-label="`${t.stars} dari 5 bintang`">
            <span
              v-for="n in 5"
              :key="n"
              :class="n <= t.stars ? 'star-on' : 'star-off'"
              aria-hidden="true"
            >★</span>
          </div>

          <footer class="card-footer">
            <div
              class="card-avatar"
              :style="{ background: t.avatarColor }"
              aria-hidden="true"
            >{{ t.name[0] }}</div>
            <div class="card-meta">
              <strong>{{ t.name }}</strong>
              <span>{{ t.ago }}</span>
            </div>
          </footer>
        </article>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'

/* ── Data ── */
const testimoni = [
  {
    name: 'Anisa R.',
    ago: '3 hari lalu',
    stars: 5,
    quote: 'Saya tidak pernah menyangka bisa berbicara dengan psikolog seenyaman ini. Prosesnya mudah dan psikolognya sangat memahami kondisi saya.',
    avatarColor: 'linear-gradient(135deg, #f9a8d4, #fda4af)',
  },
  {
    name: 'Dito S.',
    ago: '1 minggu lalu',
    stars: 5,
    quote: 'Setelah burnout parah, saya coba Rumah Natasy. Dalam 4 sesi saja saya sudah merasakan perbedaan besar. Harganya juga sangat terjangkau.',
    avatarColor: 'linear-gradient(135deg, #93c5fd, #6ee7b7)',
  },
  {
    name: 'Maya K.',
    ago: '10 hari lalu',
    stars: 5,
    quote: 'Psikolognya sabar sekali mendengarkan. Tidak menghakimi, sangat profesional. Sekarang hubungan saya dengan pasangan jauh lebih baik.',
    avatarColor: 'linear-gradient(135deg, #a78bfa, #c084fc)',
  },
  {
    name: 'Farid A.',
    ago: '2 minggu lalu',
    stars: 5,
    quote: 'Platform yang benar-benar mengutamakan privasi. Saya merasa aman bercerita dan tidak pernah khawatir data saya bocor ke mana-mana.',
    avatarColor: 'linear-gradient(135deg, #6ee7b7, #34d399)',
  },
  {
    name: 'Rina L.',
    ago: '3 minggu lalu',
    stars: 5,
    quote: 'Sudah coba beberapa platform, Rumah Natasy yang terbaik. Psikolognya berlisensi dan pendekatan terapinya terasa sangat terstruktur.',
    avatarColor: 'linear-gradient(135deg, #fbbf24, #fb923c)',
  },
  {
    name: 'Bagas P.',
    ago: '1 bulan lalu',
    stars: 5,
    quote: 'Jadwal fleksibel sangat membantu saya yang sibuk kerja. Bisa konsultasi malam hari setelah pulang kantor. Benar-benar solusi yang tepat.',
    avatarColor: 'linear-gradient(135deg, #f87171, #fb923c)',
  },
]

/* Duplikasi 3x agar infinite loop mulus */
const loopedTestimoni = computed(() => [...testimoni, ...testimoni, ...testimoni])

/* ── Refs ── */
const trackEl   = ref<HTMLElement | null>(null)
const progress  = ref(0)       // 0–100
const paused    = ref(false)

/* ── Autoplay config ── */
const DURATION  = 4000         // ms per card
const CARD_W    = 336          // card width + gap (320 + 16)

let rafId       = 0
let startTime   = 0
let currentIdx  = 0            // logical index dalam array asli (0–5)

/* Scroll track ke posisi card ke-n (dalam looped array, mulai dari salinan ke-2 agar ada ruang ke kiri) */
function scrollToCard(n: number, smooth = true) {
  const el = trackEl.value
  if (!el) return
  // offset ke salinan tengah (index offset = testimoni.length)
  const target = (testimoni.length + n) * CARD_W
  el.scrollTo({ left: target, behavior: smooth ? 'smooth' : 'instant' })
}

/* Advance satu card ke kanan */
function advance() {
  currentIdx = (currentIdx + 1) % testimoni.length
  scrollToCard(currentIdx)
}

/* Manual arrow */
function manualScroll(dir: number) {
  currentIdx = (currentIdx + dir + testimoni.length) % testimoni.length
  scrollToCard(currentIdx)
  resetTimer()
}

/* ── RAF loop ── */
function tick(ts: number) {
  if (!startTime) startTime = ts
  if (!paused.value) {
    const elapsed = ts - startTime
    progress.value = Math.min((elapsed / DURATION) * 100, 100)

    if (elapsed >= DURATION) {
      advance()
      startTime = ts
      progress.value = 0
    }
  } else {
    // update startTime so we don't jump when resumed
    startTime = ts - (progress.value / 100) * DURATION
  }
  rafId = requestAnimationFrame(tick)
}

function resetTimer() {
  progress.value = 0
  startTime = 0
}

function pause()  { paused.value = true  }
function resume() { paused.value = false }

onMounted(() => {
  // Mulai dari tengah looped array tanpa animasi
  scrollToCard(0, false)
  rafId = requestAnimationFrame(tick)
})

onBeforeUnmount(() => {
  cancelAnimationFrame(rafId)
})
</script>

<style scoped>
/* ── Section shell ── */
.testimoni-section {
  position: relative;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-top: -1px;
  padding: 80px 0 88px;
  background: var(--background);
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  z-index: 1;
  overflow: hidden;
}

/* ── Heading area ── */
.testimoni-top {
  width: min(100%, 1280px);
  margin: 0 auto 56px;
  padding: 0 max(24px, (100% - 1280px) / 2 + 24px);
}

.testimoni-eyebrow {
  margin: 0 0 14px;
  color: var(--muted);
  font-size: 11px;
  font-weight: 500;
  letter-spacing: 0.12em;
  text-transform: uppercase;
}

.testimoni-headline h2 {
  margin: 0 0 20px;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  letter-spacing: -0.055em;
  line-height: 1.06;
}

.testimoni-headline h2 em {
  font-style: italic;
  font-weight: 800;
}

.testimoni-rating-line {
  display: flex;
  align-items: center;
  gap: 10px;
  margin: 0;
  color: var(--muted);
  font-size: 13px;
}

.rating-score {
  color: var(--ink);
  font-weight: 700;
  font-size: 15px;
}

.rating-stars {
  color: #22c55e;
  font-size: 17px;
  letter-spacing: 1px;
  line-height: 1;
}

/* ── Carousel row ── */
.testimoni-row {
  display: flex;
  width: min(100%, 1280px);
  margin: 0 auto;
  padding-left: max(24px, (100vw - 1280px) / 2 + 24px);
  align-items: flex-start;
  gap: 48px;
}

/* ── Sidebar ── */
.testimoni-sidebar {
  flex: 0 0 180px;
  display: flex;
  flex-direction: column;
  padding-top: 8px;
}

.sidebar-quote {
  display: block;
  color: var(--line);
  font-family: var(--font-display);
  font-size: 96px;
  font-weight: 900;
  line-height: 0.7;
  margin-bottom: 20px;
  user-select: none;
}

.sidebar-label {
  margin: 0 0 28px;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: 15px;
  font-weight: 600;
  line-height: 1.4;
  letter-spacing: -0.02em;
}

/* Progress + arrows */
.sidebar-arrows {
  display: flex;
  align-items: center;
  gap: 10px;
}

.sidebar-arrows button {
  display: inline-flex;
  width: 36px;
  height: 36px;
  align-items: center;
  justify-content: center;
  border: 1.5px solid var(--line);
  border-radius: 50%;
  background: transparent;
  color: var(--ink);
  font-size: 16px;
  cursor: pointer;
  flex-shrink: 0;
  transition: border-color 180ms ease, background 180ms ease;
}

.sidebar-arrows button:hover {
  border-color: var(--ink);
  background: color-mix(in srgb, var(--ink) 6%, transparent);
}

/* Progress track */
.progress-track {
  position: relative;
  flex: 1;
  height: 2px;
  background: var(--line);
  border-radius: 999px;
  overflow: hidden;
}

.progress-fill {
  position: absolute;
  inset: 0 auto 0 0;
  background: var(--ink);
  border-radius: 999px;
  transition: width 80ms linear;
}

/* ── Cards track ── */
.testimoni-track {
  display: flex;
  flex: 1;
  gap: 16px;
  overflow-x: auto;
  padding-bottom: 8px;
  scroll-behavior: smooth;
  scrollbar-width: none;
}

.testimoni-track::-webkit-scrollbar {
  display: none;
}

/* ── Card ── */
.testimoni-card {
  flex: 0 0 320px;
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px;
  border: 1px solid var(--line);
  border-radius: 12px;
  background: var(--surface);
  transition: border-color 180ms ease, box-shadow 180ms ease;
}

.testimoni-card:hover {
  border-color: color-mix(in srgb, var(--ink) 25%, transparent);
  box-shadow: 0 4px 20px rgb(0 0 0 / 5%);
}

.card-quote {
  flex: 1;
  margin: 0;
  color: var(--copy);
  font-size: 14px;
  line-height: 1.65;
}

.card-stars {
  display: flex;
  gap: 2px;
  font-size: 15px;
}

.star-on  { color: #22c55e; }
.star-off { color: var(--line); }

.card-footer {
  display: flex;
  align-items: center;
  gap: 10px;
  padding-top: 16px;
  border-top: 1px solid var(--line);
}

.card-avatar {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: 13px;
  font-weight: 700;
}

.card-meta {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}

.card-meta strong {
  color: var(--ink);
  font-size: 13px;
  font-weight: 600;
  line-height: 1.2;
}

.card-meta span {
  color: var(--muted);
  font-size: 11px;
}

/* ── Mobile ── */
@media (max-width: 768px) {
  .testimoni-section {
    padding: 64px 0 72px;
  }

  .testimoni-top {
    padding: 0 20px;
    margin-bottom: 40px;
  }

  .testimoni-headline h2 {
    font-size: 1.85rem;
  }

  .testimoni-row {
    flex-direction: column;
    gap: 24px;
    padding-left: 20px;
  }

  .testimoni-sidebar {
    flex-direction: row;
    align-items: center;
    flex: none;
    gap: 16px;
    padding-top: 0;
    width: 100%;
  }

  .sidebar-quote {
    display: none;
  }

  .sidebar-label {
    margin: 0;
    font-size: 13px;
    white-space: nowrap;
  }

  .testimoni-card {
    flex-basis: min(80vw, 300px);
  }
}
</style>
