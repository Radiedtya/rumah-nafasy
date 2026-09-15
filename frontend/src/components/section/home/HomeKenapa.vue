<script setup lang="ts">
import { useTheme } from '../../../composables/useTheme'

const { theme } = useTheme()

const benefits = [
  {
    eyebrow: '01 / MATCHING',
    title: 'Psikolog yang tepat untuk cerita Anda.',
    description: 'Mulai dari kebutuhan Anda, bukan dari daftar pilihan yang membingungkan.',
    link: 'Temukan psikolog',
    preview: 'matching',
  },
  {
    eyebrow: '02 / SCHEDULING',
    title: 'Waktu konsultasi yang mengikuti hidup Anda.',
    description: 'Pilih jadwal yang terasa mungkin. Reschedule tetap sederhana.',
    link: 'Lihat jadwal tersedia',
    preview: 'schedule',
  },
  {
    eyebrow: '03 / PRIVACY',
    title: 'Ruang bicara yang benar-benar pribadi.',
    description: 'Percakapan Anda hanya untuk Anda dan psikolog yang mendampingi.',
    link: 'Baca komitmen privasi',
    preview: 'privacy',
  },
]
</script>

<template>
  <section
    id="kenapa"
    aria-labelledby="kenapa-heading"
    class="kenapa-section"
    :class="theme === 'dark' ? 'is-dark' : 'is-light'"
  >
    <div class="kenapa-inner">
      <div class="kenapa-grid">
        <article v-for="item in benefits" :key="item.eyebrow" class="kenapa-column">
          <div class="kenapa-copy">
            <p class="kenapa-eyebrow">{{ item.eyebrow }}</p>
            <h2 v-if="item.preview === 'matching'" id="kenapa-heading">{{ item.title }}</h2>
            <h3 v-else>{{ item.title }}</h3>
            <p class="kenapa-description">{{ item.description }}</p>
            <a href="#mulai" class="kenapa-link">{{ item.link }} <span aria-hidden="true">→</span></a>
          </div>

          <div class="kenapa-preview" :class="`kenapa-preview--${item.preview}`" aria-hidden="true">
            <template v-if="item.preview === 'matching'">
              <div class="preview-heading">Pilih berdasarkan kebutuhan</div>
              <div class="preview-rule"></div>
              <div class="preview-row preview-row--active"><span>Rasa cemas</span><b>12 psikolog</b></div>
              <div class="preview-row"><span>Relasi & keluarga</span><b>8 psikolog</b></div>
              <div class="preview-row"><span>Burnout kerja</span><b>6 psikolog</b></div>
              <div class="preview-footer">Cocokkan dengan kebutuhan Anda <span>→</span></div>
            </template>

            <template v-else-if="item.preview === 'schedule'">
              <div class="preview-heading">Jadwal minggu ini</div>
              <div class="preview-calendar">
                <span>Sen</span><span>Sel</span><span class="preview-day--active">Rab</span><span>Kam</span><span>Jum</span>
              </div>
              <div class="preview-slots"><span>09.00</span><span class="preview-slot--active">19.30</span><span>20.30</span></div>
              <div class="preview-footer">Sesi tersedia malam ini <span>→</span></div>
            </template>

            <template v-else>
              <div class="preview-heading">Sesi Anda terlindungi</div>
              <div class="preview-rule"></div>
              <div class="preview-lock-row"><span class="preview-lock-line"></span><strong>Private session</strong></div>
              <div class="preview-muted">Tidak direkam · Tidak dibagikan</div>
              <div class="preview-footer">Pelajari perlindungan data <span>→</span></div>
            </template>
          </div>
        </article>
      </div>

      <div class="kenapa-stats">
        <div class="kenapa-stat">
          <strong>10.000+</strong>
          <span>sesi konsultasi telah berlangsung</span>
        </div>
        <div class="kenapa-stat">
          <strong>4.9/5</strong>
          <span>rating rata-rata dari klien kami</span>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.kenapa-section {
  position: relative;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-top: -1px;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  background: var(--kenapa-bg);
  color: var(--kenapa-text);
  z-index: 1;
  --kenapa-bg: var(--background);
  --kenapa-panel: var(--surface);
  --kenapa-text: #171717;
  --kenapa-copy: #5f5f5a;
  --kenapa-muted: #85857f;
  --kenapa-line: #d8d8d2;
  --kenapa-panel-line: #e2e2dc;
  --kenapa-accent: #d84b43;
}

.kenapa-section.is-dark {
  --kenapa-bg: #000000;
  --kenapa-panel: #0c0c0c;
  --kenapa-text: #f4f4ef;
  --kenapa-copy: #a8a8a2;
  --kenapa-muted: #878787;
  --kenapa-line: #292929;
  --kenapa-panel-line: #242424;
  --kenapa-accent: #ff6a60;
}

.kenapa-inner {
  width: min(100%, 1280px);
  margin: 0 auto;
}

.kenapa-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: stretch;
  border-left: 1px solid var(--kenapa-line);
}

.kenapa-column {
  min-width: 0;
  display: flex;
  flex-direction: column;
  border-right: 1px solid var(--kenapa-line);
}

.kenapa-copy {
  min-height: 15.75rem;
  padding: 3.5rem 2.25rem 2rem;
}

.kenapa-eyebrow {
  margin: 0 0 1.5rem;
  color: var(--kenapa-muted);
  font-family: var(--font-display);
  font-size: 0.65rem;
  font-weight: 500;
  letter-spacing: 0.1em;
}

.kenapa-copy h2,
.kenapa-copy h3 {
  max-width: 19rem;
  margin: 0;
  color: var(--kenapa-text);
  font-family: var(--font-display);
  font-size: 1.05rem;
  font-weight: 500;
  letter-spacing: -0.015em;
  line-height: 1.35;
}

.kenapa-description {
  max-width: 19rem;
  margin: 0.85rem 0 0;
  color: var(--kenapa-copy);
  font-size: 0.8rem;
  line-height: 1.55;
}

.kenapa-link {
  display: inline-flex;
  gap: 0.45rem;
  align-items: center;
  margin-top: 1.35rem;
  color: var(--kenapa-text);
  font-size: 0.75rem;
  text-decoration: none;
  transition: color 180ms ease;
}

.kenapa-link span,
.preview-footer span {
  color: var(--kenapa-accent);
  font-size: 1rem;
  line-height: 0.8;
  transition: transform 180ms ease;
}

.kenapa-link:hover {
  color: var(--kenapa-accent);
}

.kenapa-link:hover span,
.preview-footer:hover span {
  transform: translateX(3px);
}

.kenapa-preview {
  height: 17.5rem;
  min-height: 17.5rem;
  box-sizing: border-box;
  margin-top: auto;
  padding: 1.25rem 1.25rem 0;
  overflow: hidden;
  border-top: 1px solid var(--kenapa-line);
  background: var(--kenapa-panel);
  color: var(--kenapa-text);
  font-family: var(--font-display);
  font-size: 0.65rem;
}

.preview-heading {
  color: var(--kenapa-text);
  font-size: 0.75rem;
  font-weight: 500;
}

.preview-rule {
  height: 1px;
  margin: 1rem 0 0.25rem;
  background: var(--kenapa-line);
}

.preview-row,
.preview-lock-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  min-height: 2.8rem;
  border-bottom: 1px solid var(--kenapa-panel-line);
  color: var(--kenapa-copy);
}

.preview-row b {
  color: var(--kenapa-muted);
  font-size: 0.6rem;
  font-weight: 400;
}

.preview-row--active {
  color: var(--kenapa-text);
}

.preview-row--active::before {
  width: 0.3rem;
  height: 0.3rem;
  margin-right: 0.55rem;
  background: var(--kenapa-accent);
  content: '';
}

.preview-row--active span {
  margin-right: auto;
}

.preview-calendar {
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0.3rem;
  margin-top: 1.15rem;
  color: var(--kenapa-muted);
  text-align: center;
}

.preview-calendar span {
  padding: 0.65rem 0.1rem;
  border-bottom: 1px solid var(--kenapa-line);
}

.preview-calendar .preview-day--active {
  border-bottom-color: var(--kenapa-accent);
  color: var(--kenapa-text);
}

.preview-slots {
  display: flex;
  gap: 0.4rem;
  margin-top: 1rem;
}

.preview-slots span {
  padding: 0.55rem 0.65rem;
  border: 1px solid var(--kenapa-line);
  color: var(--kenapa-muted);
}

.preview-slots .preview-slot--active {
  border-color: var(--kenapa-accent);
  color: var(--kenapa-text);
}

.preview-lock-row {
  justify-content: flex-start;
  gap: 0.75rem;
  margin-top: 1.15rem;
  color: var(--kenapa-text);
}

.preview-lock-line {
  width: 1.4rem;
  height: 0.85rem;
  border: 1px solid var(--kenapa-accent);
}

.preview-lock-row strong {
  font-weight: 400;
}

.preview-muted {
  margin-top: 0.9rem;
  color: var(--kenapa-muted);
}

.preview-footer {
  display: flex;
  justify-content: space-between;
  margin-top: 1.4rem;
  color: var(--kenapa-muted);
  font-size: 0.65rem;
}

.kenapa-stats {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  border-top: 1px solid var(--kenapa-line);
  border-left: 1px solid var(--kenapa-line);
}

.kenapa-stat {
  min-height: 10rem;
  padding: 3.25rem 2.25rem;
  border-right: 1px solid var(--kenapa-line);
}

.kenapa-stat strong {
  display: block;
  color: var(--kenapa-text);
  font-family: var(--font-display);
  font-size: clamp(2rem, 3.8vw, 3.5rem);
  font-weight: 500;
  letter-spacing: -0.06em;
  line-height: 1;
}

.kenapa-stat span {
  display: block;
  margin-top: 0.6rem;
  color: var(--kenapa-copy);
  font-size: 0.8rem;
}

@media (max-width: 767px) {
  .kenapa-grid {
    grid-template-columns: 1fr;
  }

  .kenapa-copy {
    min-height: auto;
    padding: 2.5rem 1.25rem 1.75rem;
  }

  .kenapa-preview {
    height: 13rem;
  }

  .kenapa-stats {
    grid-template-columns: 1fr;
  }

  .kenapa-stat {
    min-height: auto;
    padding: 2.5rem 1.25rem;
  }
}
</style>
