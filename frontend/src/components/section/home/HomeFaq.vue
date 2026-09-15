<template>
  <section id="faq" aria-labelledby="faq-heading" class="faq-section">

    <!-- ── EYEBROW ── -->
    <div class="faq-header">
      <h2 id="faq-heading" class="faq-title">Pertanyaan<br />yang sering ditanyakan.</h2>
      <p class="faq-eyebrow">
        Temukan jawaban tepercaya seputar layanan kesehatan mental di Rumah Natasy.
      </p>
    </div>

    <!-- ── MAIN GRID: gambar kiri | accordion kanan ── -->
    <div class="faq-grid">

      <!-- Kiri: slot gambar -->
      <div class="faq-image-wrap" aria-hidden="true">
        <img
          src="/images/assets/bunga.png"
          alt=""
          class="faq-image"
        />
      </div>

      <!-- Kanan: accordion -->
      <div class="faq-accordion" role="list">
        <div
          v-for="(faq, idx) in faqs.slice(0, 5)"
          :key="faq.question"
          class="faq-item"
          role="listitem"
        >
          <button
            class="faq-trigger"
            :aria-expanded="openIdx === idx"
            :aria-controls="`faq-answer-${idx}`"
            @click="toggle(idx)"
          >
            <span>{{ faq.question }}</span>
            <span class="faq-icon" :class="{ 'is-open': openIdx === idx }" aria-hidden="true">+</span>
          </button>

          <div
            :id="`faq-answer-${idx}`"
            class="faq-answer"
            :class="{ 'is-open': openIdx === idx }"
          >
            <p>{{ faq.answer }}</p>
          </div>
        </div>
      </div>

    </div>

    <!-- ── CTA STRIP ── -->
    <div class="faq-cta">
      <h2 id="faq-heading" class="faq-cta-heading">Tetap terhubung</h2>
      <p class="faq-cta-sub">Dapatkan artikel kesehatan mental dan info promo langsung di inbox Anda.</p>
      <form class="faq-form" @submit.prevent="submitEmail">
        <label for="faq-email" class="faq-form-label">Alamat email</label>
        <div class="faq-form-row">
          <input
            id="faq-email"
            v-model="email"
            type="email"
            class="faq-input"
            placeholder="Masukkan email"
            required
            autocomplete="email"
          />
          <button type="submit" class="faq-submit">Kirim</button>
        </div>
        <p class="faq-form-note">
          Dengan mendaftar, Anda menyetujui
          <a href="#" class="faq-form-link">Syarat & Ketentuan</a>
          kami.
        </p>
      </form>
    </div>

  </section>
</template>

<script setup lang="ts">
import { ref } from 'vue'

const openIdx = ref<number | null>(null)
const email   = ref('')

function toggle(idx: number) {
  openIdx.value = openIdx.value === idx ? null : idx
}

function submitEmail() {
  // TODO: integrate with newsletter API
  email.value = ''
}

const faqs = [
  {
    question: 'Apakah semua psikolog Rumah Natasy berlisensi resmi?',
    answer: 'Ya, seluruh psikolog kami memegang Surat Izin Praktik (SIP) resmi dari Kementerian Kesehatan RI dan terdaftar aktif sebagai anggota HIMPsi. Semua melewati proses verifikasi latar belakang yang ketat sebelum bergabung.',
  },
  {
    question: 'Berapa biaya konsultasi di Rumah Natasy?',
    answer: 'Harga mulai dari Rp 100.000 per sesi, disesuaikan dengan kategori layanan dan psikolog yang Anda pilih. Tidak ada biaya tersembunyi — semua tarif ditampilkan transparan sebelum Anda melakukan booking.',
  },
  {
    question: 'Apakah data dan percakapan saya terjaga kerahasiaannya?',
    answer: 'Tentu. Seluruh data pribadi dan percakapan Anda dilindungi sesuai UU PDP No. 27 Tahun 2022. Tidak ada rekaman sesi, tidak ada penyimpanan isi percakapan, dan tidak ada pihak ketiga yang dapat mengakses data Anda.',
  },
  {
    question: 'Bisa konsultasi lewat apa saja?',
    answer: 'Anda bisa memilih antara sesi video call atau chat teks, sesuai kenyamanan Anda. Semua dilakukan langsung di platform Rumah Natasy tanpa perlu mengunduh aplikasi tambahan.',
  },
  {
    question: 'Bagaimana jika saya perlu reschedule?',
    answer: 'Reschedule dapat dilakukan hingga 2 jam sebelum jadwal sesi tanpa biaya apapun. Cukup masuk ke dashboard, pilih sesi, dan pilih waktu baru yang tersedia.',
  },
  {
    question: 'Apakah konsultasi online sama efektifnya dengan tatap muka?',
    answer: 'Penelitian klinis internasional menunjukkan terapi online memiliki efektivitas setara tatap muka untuk mayoritas kondisi, termasuk kecemasan dan depresi ringan-sedang. Psikolog kami terlatih khusus untuk sesi online.',
  },
]
</script>

<style scoped>
/* ── Section shell ── */
.faq-section {
  position: relative;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-top: -1px;
  padding: 72px 0 0;
  background: var(--background);
  border-top: 1px solid var(--line);
  z-index: 1;
  overflow: hidden;
}

/* ── Eyebrow ── */
.faq-header {
  width: min(100%, 1280px);
  margin: 0 auto 48px;
  padding: 0 max(24px, (100% - 1280px) / 2 + 24px);
}

.faq-title {
  margin: 0 0 16px;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: clamp(2.4rem, 4.5vw, 3.8rem);
  font-weight: 700;
  letter-spacing: -0.06em;
  line-height: 1.04;
}

.faq-eyebrow {
  margin: 0;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.55;
  max-width: 480px;
}

/* ── Main grid ── */
.faq-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  width: min(100%, 1280px);
  margin: 0 auto;
  padding: 0 max(24px, (100% - 1280px) / 2 + 24px);
  gap: 64px;
  align-items: start;
}

/* ── Image slot ── */
.faq-image-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 420px;
  padding-top: 60px;
}

.faq-image {
  display: block;
  width: 100%;
  max-width: 520px;
  height: auto;
  object-fit: contain;
  filter: drop-shadow(0 4px 24px rgb(0 0 0 / 8%));
}

/* ── Accordion ── */
.faq-accordion {
  padding-top: 4px;
}

.faq-item {
  border-bottom: 1px solid var(--line);
}

.faq-trigger {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 24px 0;
  background: transparent;
  border: none;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 500;
  line-height: 1.4;
  text-align: left;
  cursor: pointer;
  transition: color 180ms ease;
}

.faq-trigger:hover {
  color: var(--accent);
}

.faq-icon {
  flex-shrink: 0;
  width: 24px;
  height: 24px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--muted);
  font-size: 26px;
  font-weight: 300;
  line-height: 1;
  transition: transform 220ms ease, color 180ms ease;
  user-select: none;
}

.faq-icon.is-open {
  transform: rotate(45deg);
  color: var(--ink);
}

.faq-answer {
  display: grid;
  grid-template-rows: 0fr;
  transition: grid-template-rows 220ms ease;
  visibility: hidden;
}

.faq-answer.is-open {
  grid-template-rows: 1fr;
  visibility: visible;
}

.faq-answer p {
  overflow: hidden;
  margin: 0;
  padding-bottom: 24px;
  color: var(--copy);
  font-size: 15px;
  line-height: 1.7;
}

/* ── CTA strip ── */
.faq-cta {
  width: 100%;
  margin-top: 80px;
  padding: 64px max(24px, (100vw - 1280px) / 2 + 24px);
  text-align: center;
  background: var(--surface);
  border-top: 1px solid var(--line);
}

.faq-cta-heading {
  margin: 0 0 10px;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: clamp(1.8rem, 3.5vw, 2.6rem);
  font-weight: 700;
  letter-spacing: -0.05em;
  line-height: 1.1;
}

.faq-cta-sub {
  margin: 0 0 28px;
  color: var(--muted);
  font-size: 14px;
  line-height: 1.5;
}

.faq-form {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}

.faq-form-label {
  font-size: 11px;
  color: var(--muted);
  letter-spacing: 0.04em;
  text-transform: uppercase;
  align-self: flex-start;
  margin-left: calc(50% - 195px);
}

.faq-form-row {
  display: flex;
  gap: 8px;
  width: 100%;
  max-width: 390px;
}

.faq-input {
  flex: 1;
  min-height: 44px;
  padding: 0 14px;
  border: 1.5px solid var(--line);
  border-radius: 6px;
  background: var(--background);
  color: var(--ink);
  font: inherit;
  font-size: 14px;
  outline: none;
  transition: border-color 180ms ease;
}

.faq-input::placeholder {
  color: var(--muted);
}

.faq-input:focus {
  border-color: var(--ink);
}

.faq-submit {
  min-height: 44px;
  padding: 0 22px;
  border: none;
  border-radius: 6px;
  background: var(--ink);
  color: var(--inverse-text);
  font: inherit;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: opacity 180ms ease;
}

.faq-submit:hover {
  opacity: 0.82;
}

.faq-form-note {
  margin: 0;
  color: var(--muted);
  font-size: 11px;
  line-height: 1.5;
}

.faq-form-link {
  color: var(--ink);
  text-decoration: underline;
  text-underline-offset: 2px;
}

/* ── Mobile ── */
@media (max-width: 768px) {
  .faq-section {
    padding-top: 56px;
  }

  .faq-header {
    padding: 0 20px;
    margin-bottom: 36px;
  }

  .faq-title {
    font-size: 2.2rem;
  }

  .faq-grid {
    grid-template-columns: 1fr;
    gap: 40px;
    padding: 0 20px;
  }

  .faq-image-wrap {
    min-height: 280px;
    padding-top: 0;
  }

  .faq-cta {
    margin-top: 56px;
    padding: 48px 20px;
  }

  .faq-form-label {
    margin-left: 0;
    align-self: flex-start;
  }

  .faq-form-row {
    flex-direction: column;
  }

  .faq-submit {
    width: 100%;
  }
}
</style>
