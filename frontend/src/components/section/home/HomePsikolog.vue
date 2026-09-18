<script setup lang="ts">
import { computed, ref } from 'vue'
import { ArrowLeftIcon, ArrowRightIcon, CheckBadgeIcon, ChevronDownIcon } from '@heroicons/vue/24/outline'

type Psychologist = {
  name: string
  specialty: string
  rating: string
  reviews: string
  avatar: string
  quote: string
  price: string
  regularPrice: string
  available: boolean
  highlyRated?: boolean
  slots: string[]
}

const onlyAvailable = ref(true)
const selectedSlots = ref<Record<string, string>>({})
const psychologistGrid = ref<HTMLElement | null>(null)

const psychologists: Psychologist[] = [
  {
    name: 'Dra. Sari Dewi, M.Psi.', specialty: 'Psikolog klinis', rating: '4.9', reviews: '312',
    avatar: 'https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=300&h=300&q=85',
    quote: 'Pendekatan yang hangat dan praktis untuk membantu Anda memahami diri.', price: 'Rp150k', regularPrice: 'Rp200k', available: true, highlyRated: true,
    slots: ['09.00', '10.30', '13.00', '15.30'],
  },
  {
    name: 'Dr. Bima Arya, M.Psi.', specialty: 'Kecemasan & hubungan', rating: '4.8', reviews: '198',
    avatar: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?auto=format&fit=crop&w=300&h=300&q=85',
    quote: 'Ruang aman untuk bercerita, bertumbuh, dan menemukan langkah berikutnya.', price: 'Rp130k', regularPrice: 'Rp175k', available: true,
    slots: ['08.30', '10.00', '14.00', '16.30'],
  },
  {
    name: 'Nadia Putri, M.Psi.', specialty: 'Anak & remaja', rating: '5.0', reviews: '147',
    avatar: 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?auto=format&fit=crop&w=300&h=300&q=85',
    quote: 'Mendampingi keluarga dengan komunikasi yang lebih sehat dan penuh empati.', price: 'Rp140k', regularPrice: 'Rp185k', available: true, highlyRated: true,
    slots: ['09.00', '11.00', '13.30', '15.00'],
  },
  {
    name: 'Rian Kusuma, M.Psi.', specialty: 'Burnout & karier', rating: '4.7', reviews: '256',
    avatar: 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&h=300&q=85',
    quote: 'Temukan kembali energi dan arah saat pekerjaan terasa terlalu berat.', price: 'Rp120k', regularPrice: 'Rp165k', available: true,
    slots: ['10.00', '12.30', '15.00', '17.30'],
  },
]

const visiblePsychologists = computed(() => {
  return onlyAvailable.value ? psychologists.filter((psychologist) => psychologist.available) : psychologists
})

function selectSlot(name: string, slot: string) {
  selectedSlots.value[name] = slot
}

function scrollPsychologists(direction: number) {
  psychologistGrid.value?.scrollBy({ left: direction * 378, behavior: 'smooth' })
}
</script>

<template>
  <section id="ahli" aria-labelledby="psikolog-heading" class="psychologist-section">
    <div class="w-full max-w-[1200px] mx-auto px-4 sm:px-6">
    <div class="psychologist-header">
      <div>
        <h2 id="psikolog-heading">Psikolog pilihan untuk Anda</h2>
        <label class="availability-filter">
          <input v-model="onlyAvailable" type="checkbox" />
          <span class="filter-switch" aria-hidden="true"><span /></span>
          <span>Jadwal tersedia</span>
        </label>
      </div>
      <a href="#kategori" class="see-all">Lihat semua <ArrowRightIcon aria-hidden="true" /></a>
    </div>

    <div ref="psychologistGrid" class="psychologist-grid">
      <article v-for="psychologist in visiblePsychologists" :key="psychologist.name" class="psychologist-card">
        <div class="doctor-intro">
          <img :src="psychologist.avatar" :alt="`Foto ${psychologist.name}`" class="doctor-avatar" />
          <div class="doctor-name-block">
            <h3>{{ psychologist.name }}</h3>
            <p>{{ psychologist.specialty }}</p>
            <div class="doctor-rating"><span class="stars" aria-label="Rating lima bintang">★★★★★</span><strong>{{ psychologist.rating }}</strong><span>({{ psychologist.reviews }})</span></div>
          </div>
        </div>

        <div class="doctor-badges">
          <span v-if="psychologist.available" class="badge badge-green">Tersedia hari ini</span>
          <span v-if="psychologist.highlyRated" class="badge badge-blue">Pilihan terbaik</span>
        </div>

        <blockquote>“{{ psychologist.quote }}”</blockquote>

        <div class="doctor-price">
          <div><span>Sesi berikutnya</span><strong>Senin, 14 Sep</strong></div>
          <div class="price-value"><strong>{{ psychologist.price }}</strong><span>Harga normal {{ psychologist.regularPrice }}</span></div>
        </div>

        <div class="slot-list">
          <button v-for="slot in psychologist.slots" :key="slot" type="button" :class="{ selected: selectedSlots[psychologist.name] === slot }" @click="selectSlot(psychologist.name, slot)">{{ slot }}</button>
          <button type="button" class="more-slot"><ChevronDownIcon aria-hidden="true" /> Lainnya</button>
        </div>

        <button type="button" class="book-button">Pilih psikolog <ArrowRightIcon aria-hidden="true" /></button>
      </article>
    </div>

    <div class="carousel-footer">
      <div class="carousel-arrows">
        <button type="button" aria-label="Psikolog sebelumnya" @click="scrollPsychologists(-1)"><ArrowLeftIcon aria-hidden="true" /></button>
        <button type="button" aria-label="Psikolog berikutnya" @click="scrollPsychologists(1)"><ArrowRightIcon aria-hidden="true" /></button>
      </div>
    </div>

    <p class="verification-note"><CheckBadgeIcon aria-hidden="true" /> Seluruh psikolog telah terverifikasi SIP dan HIMPsi.</p>
    </div>
  </section>
</template>

<style scoped>
.psychologist-section {
  position: relative;
  /* Keluar dari batas main container agar menutupi rail kiri/kanan */
  width: 100vw;
  margin-left: calc(50% - 50vw);
  padding: 88px 0;
  /* Background menutupi rail pseudo-element dari main */
  background: var(--background);
  z-index: 1;
  /* Override global section border, pasang sendiri full-width */
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
  /* Hapus border yang mungkin datang dari global rule */
  margin-top: -1px;
  color: var(--text);
}
.psychologist-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 24px; margin-bottom: 28px; }
.psychologist-header h2 { margin: 0; color: var(--ink); font-family: var(--font-display); font-size: clamp(1.7rem, 3vw, 2.25rem); font-weight: 700; letter-spacing: -0.055em; }
.availability-filter { display: inline-flex; align-items: center; gap: 9px; margin-top: 22px; color: var(--copy); font-size: 14px; cursor: pointer; }
.availability-filter input { position: absolute; opacity: 0; }
.filter-switch { display: inline-flex; width: 42px; height: 25px; align-items: center; padding: 3px; border: 1.5px solid var(--accent); border-radius: 999px; }
.filter-switch span { width: 17px; height: 17px; border-radius: 50%; background: var(--accent); transition: transform 180ms ease, background-color 180ms ease; }
.availability-filter input:not(:checked) + .filter-switch span { background: var(--muted); transform: translateX(15px); }
.see-all { display: inline-flex; align-items: center; gap: 7px; color: var(--accent); font-size: 14px; font-weight: 700; text-decoration: none; }
.see-all svg, .book-button svg { width: 17px; height: 17px; }
.psychologist-grid { display: flex; width: calc(100vw - max(24px, (100vw - 1280px) / 2 + 24px)); gap: 18px; overflow-x: auto; padding: 0 24px 10px 2px; scroll-behavior: smooth; scroll-snap-type: x proximity; scrollbar-width: thin; scrollbar-color: var(--line) transparent; }
.psychologist-grid::-webkit-scrollbar { height: 6px; }
.psychologist-grid::-webkit-scrollbar-track { background: transparent; }
.psychologist-grid::-webkit-scrollbar-thumb { background: var(--line); border-radius: 999px; }
.psychologist-card { display: flex; flex: 0 0 360px; min-width: 0; flex-direction: column; padding: 16px; border: 1px solid color-mix(in srgb, var(--line) 72%, transparent); border-radius: 10px; background: var(--surface); box-shadow: 0 8px 24px rgb(22 24 35 / 4%); scroll-snap-align: start; transition: background-color 180ms ease, border-color 180ms ease, box-shadow 180ms ease; }
.doctor-intro { display: flex; align-items: flex-start; gap: 14px; }
.doctor-avatar { width: 76px; height: 76px; flex: 0 0 76px; border-radius: 50%; object-fit: cover; background: var(--line); }
.doctor-name-block { min-width: 0; }
.doctor-name-block h3 { margin: 2px 0 5px; color: var(--ink); font-family: var(--font-display); font-size: 20px; font-weight: 700; line-height: 1.08; }
.doctor-name-block p, .doctor-rating, .doctor-price span { color: var(--muted); font-size: 13px; }
.doctor-name-block p { margin: 0 0 7px; }
.doctor-rating { display: flex; align-items: center; gap: 4px; white-space: nowrap; }
.doctor-rating strong { color: var(--ink); }
.stars { color: #f4b400; letter-spacing: 1px; }
.doctor-badges { display: flex; min-height: 29px; flex-wrap: wrap; align-items: center; gap: 6px; margin-top: 9px; }
.badge { padding: 5px 8px; border-radius: 4px; font-size: 11px; line-height: 1; }
.badge-green { background: #d9f3e6; color: #177348; }
.badge-blue { background: #dceeff; color: #256294; }
blockquote { min-height: 65px; margin: 8px 0 14px; padding: 13px 12px; border-radius: 8px; background: color-mix(in srgb, var(--line) 32%, transparent); color: var(--muted); font-size: 14px; line-height: 1.5; }
.doctor-price { display: flex; align-items: flex-end; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
.doctor-price div { display: flex; flex-direction: column; gap: 3px; }
.doctor-price strong { color: var(--ink); font-size: 13px; }
.price-value { align-items: flex-end; text-align: right; }
.price-value strong { color: #18834d; font-size: 21px; }
.price-value span { font-size: 11px; }
.slot-list { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 7px; margin-top: 2px; }
.slot-list button { min-height: 37px; padding: 0 6px; border: 1px solid var(--line); border-radius: 999px; background: transparent; color: var(--text); font: inherit; font-size: 13px; cursor: pointer; transition: border-color 180ms ease, background-color 180ms ease, color 180ms ease; }
.slot-list button:hover, .slot-list button.selected { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 11%, transparent); color: var(--accent); }
.slot-list .more-slot { display: inline-flex; align-items: center; justify-content: center; gap: 4px; }
.more-slot svg { width: 13px; height: 13px; }
.book-button { display: inline-flex; align-items: center; justify-content: center; gap: 8px; width: 100%; min-height: 49px; margin-top: 12px; border: 0; border-radius: 999px; background: var(--accent); color: white; font: inherit; font-size: 15px; font-weight: 700; cursor: pointer; transition: opacity 180ms ease, transform 180ms ease; }
.book-button:hover { opacity: .88; transform: translateY(-1px); }
.carousel-footer { display: flex; align-items: center; justify-content: flex-end; margin-top: 18px; }
.carousel-dots { display: flex; align-items: center; gap: 8px; }
.carousel-dots button { width: 9px; height: 9px; padding: 0; border: 0; border-radius: 50%; background: var(--muted); opacity: .65; cursor: pointer; }
.carousel-dots button.active { width: 18px; border-radius: 999px; background: var(--ink); opacity: 1; }
.carousel-arrows { display: flex; gap: 12px; }
.carousel-arrows button { display: inline-flex; width: 44px; height: 44px; align-items: center; justify-content: center; border: 1.5px solid var(--muted); border-radius: 50%; background: transparent; color: var(--muted); cursor: pointer; }
.carousel-arrows button:last-child { border-color: var(--accent); color: var(--accent); }
.carousel-arrows svg { width: 20px; height: 20px; }
.verification-note { display: flex; align-items: center; justify-content: center; gap: 6px; margin: 24px 0 0; color: var(--muted); font-size: 11px; }
.verification-note svg { width: 15px; height: 15px; color: #18834d; }
@media (max-width: 640px) {
  .psychologist-section { padding: 64px 0; }
  .psychologist-header { align-items: flex-start; }
  .psychologist-header h2 { font-size: 1.65rem; }
  .see-all { margin-top: 4px; font-size: 12px; }
  .psychologist-grid { width: calc(100vw - 24px); gap: 12px; padding: 0 16px 5px 2px; scrollbar-width: none; }
  .psychologist-grid::-webkit-scrollbar { display: none; }
  .psychologist-card { flex-basis: min(88vw, 360px); }
  .carousel-footer { margin-top: 18px; }
  .verification-note { justify-content: flex-start; line-height: 1.4; }
}
</style>
