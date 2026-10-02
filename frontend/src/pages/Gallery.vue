<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'

type GalleryPhoto = { src: string; alt: string; title: string; description: string; category: string }

const filenames = [
  'WhatsApp Image 2026-09-23 at 11.04.51 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.51 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.51.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.52 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.52 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.52.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.53 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.53 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.53.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.54 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.54 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.54.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.55 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.55 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.55.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.56 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.56 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.56.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.57 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.57 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.57.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.58 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.58 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.58.jpeg',
  'WhatsApp Image 2026-09-23 at 11.04.59 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.59 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.59 (3).jpeg', 'WhatsApp Image 2026-09-23 at 11.04.59.jpeg',
  'WhatsApp Image 2026-09-23 at 11.05.00 (1).jpeg', 'WhatsApp Image 2026-09-23 at 11.05.00 (2).jpeg', 'WhatsApp Image 2026-09-23 at 11.05.00.jpeg',
]

const moments = [
  ['Ruang untuk saling mendengar', 'Setiap cerita punya tempat. Kami hadir untuk mendengarkan dengan utuh dan tanpa menghakimi.', 'Cerita'],
  ['Bertumbuh bersama', 'Langkah kecil terasa lebih ringan ketika kita menjalaninya bersama.', 'Komunitas'],
  ['Satu ruang, banyak cerita', 'Pertemuan sederhana bisa menjadi awal dari pemahaman yang lebih dalam.', 'Kegiatan'],
  ['Menemukan jeda', 'Merawat diri juga berarti memberi ruang untuk berhenti sejenak.', 'Keseharian'],
  ['Dukungan yang terasa dekat', 'Kami percaya dukungan yang baik dimulai dari kehadiran yang tulus.', 'Komunitas'],
  ['Belajar memahami diri', 'Mengenali apa yang kita rasakan adalah bagian dari perjalanan bertumbuh.', 'Cerita'],
]

const photos: GalleryPhoto[] = filenames.map((filename, index) => {
  const moment = moments[index % moments.length]
  return {
    src: `/images/album/${encodeURIComponent(filename)}`,
    alt: `${moment[0]} — dokumentasi Rumah Nafasy`,
    title: moment[0],
    description: moment[1],
    category: moment[2],
  }
})

const activePhoto = ref<GalleryPhoto | null>(null)
const selectedCategory = ref('Semua')
const categories = ['Semua', 'Cerita', 'Komunitas', 'Kegiatan', 'Keseharian']
const visiblePhotos = computed(() => selectedCategory.value === 'Semua'
  ? photos
  : photos.filter(photo => photo.category === selectedCategory.value))

function closeModal() { activePhoto.value = null }
function onKeydown(event: KeyboardEvent) { if (event.key === 'Escape') closeModal() }
onMounted(() => window.addEventListener('keydown', onKeydown))
onUnmounted(() => window.removeEventListener('keydown', onKeydown))
</script>

<template>
  <div class="gallery-page">
    <section class="profile" aria-labelledby="profile-name">
      <div class="profile__cover" aria-hidden="true">
        <div class="profile__cover-shade"></div>
        <span class="profile__cover-mark">Ruang Untuk Kembali Pulih</span>
      </div>
      <div class="profile__body">
        <div class="profile__identity">
          <div class="profile__avatar"><img src="/icons/512.png" alt="Logo Rumah Nafasy"></div>
          <div class="profile__copy">
            <div class="profile__name-row">
              <h1 id="profile-name">Rumah Nafasy</h1>
              <svg class="verified" viewBox="0 0 24 24" aria-label="Akun terverifikasi"><path d="M12 2.2 14.5 4l3.1-.1 1.1 2.9 2.6 1.8-.9 3 1 2.9-2.5 2-.9 3-3.1-.1-2.4 1.9-2.5-1.8-3.1.1-1.1-2.9-2.6-1.8.9-3-1-2.9 2.5-2 .9-3 3.1.1L12 2.2Zm-1.1 13.9 6-6-1.4-1.4-4.6 4.6-2.3-2.3-1.4 1.4 3.7 3.7Z" fill="currentColor"/></svg>
            </div>
            <p class="profile__handle">@rumahnafasy</p>
            <p class="profile__bio">Ruang aman untuk memahami diri, menemukan dukungan, dan bertumbuh bersama. <a href="/about">Kenali kami <span aria-hidden="true">↗</span></a></p>
          </div>
          <a class="profile__cta" href="/form">Mulai ceritamu <span aria-hidden="true">↗</span></a>
        </div>
        <div class="profile__meta">
          <span><strong>{{ photos.length }}</strong> momen</span><i></i><span><strong>Ruang aman</strong> untuk bertumbuh</span><i></i><span class="profile__location">Indonesia</span>
        </div>
      </div>
    </section>

    <section class="gallery-section" aria-labelledby="gallery-title">
      <div class="gallery-heading">
        <div>
          <p class="eyebrow"><span></span> Catatan perjalanan kami</p>
          <h2 id="gallery-title">Cerita dalam gambar.</h2>
          <p class="gallery-heading__sub">Momen kecil dari ruang yang kami bangun bersama.</p>
        </div>
        <div class="gallery-count"><span class="gallery-count__dot"></span>{{ visiblePhotos.length }} momen</div>
      </div>
      <div class="filters" role="group" aria-label="Filter galeri">
        <button v-for="category in categories" :key="category" type="button" :class="{ 'is-active': selectedCategory === category }" @click="selectedCategory = category">{{ category }}</button>
      </div>
      <div class="photo-grid">
        <button v-for="(photo, index) in visiblePhotos" :key="photo.src" class="photo-tile" type="button" :aria-label="`Lihat cerita: ${photo.title}`" @click="activePhoto = photo">
          <img :src="photo.src" :alt="photo.alt" :loading="index < 6 ? 'eager' : 'lazy'">
          <span class="photo-tile__veil"><span class="photo-tile__detail">Lihat cerita <span aria-hidden="true">↗</span></span></span>
        </button>
      </div>
      <div class="gallery-note"><span class="gallery-note__line"></span><p>Setiap perjalanan dimulai dari satu langkah kecil.</p><span class="gallery-note__line"></span></div>
    </section>

    <Teleport to="body">
      <div v-if="activePhoto" class="photo-modal" role="dialog" aria-modal="true" :aria-label="activePhoto.title" @click.self="closeModal">
        <button class="photo-modal__close" type="button" aria-label="Tutup" @click="closeModal">×</button>
        <article class="photo-modal__card">
          <div class="photo-modal__image"><img :src="activePhoto.src" :alt="activePhoto.alt"></div>
          <div class="photo-modal__caption">
            <div class="photo-modal__brand"><span class="photo-modal__avatar">n</span><span><strong>Rumah Nafasy</strong><small>@rumahnafasy · {{ activePhoto.category }}</small></span></div>
            <h2>{{ activePhoto.title }}</h2>
            <p>{{ activePhoto.description }}</p>
            <span class="photo-modal__date">23 September 2026</span>
          </div>
        </article>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.gallery-page { --gallery-green: #287b61; width: 100%; padding-bottom: 54px; color: var(--text); }
.profile { margin: 30px 0 72px; padding-bottom: 22px; overflow: hidden; border-bottom: 1px solid var(--line); }
.profile__cover { position: relative; display: flex; align-items: flex-end; min-height: clamp(165px, 23vw, 250px); padding: 22px 28px; overflow: hidden; border-radius: 14px; background: #b9e6ee url('/images/bg/image.png') center 48% / cover no-repeat; }
.profile__cover-shade { position: absolute; inset: 0; background: linear-gradient(90deg, rgb(12 49 42 / 55%), transparent 80%), linear-gradient(0deg, rgb(12 49 42 / 28%), transparent 58%); }
.profile__cover-mark { position: relative; z-index: 1; color: #fff; font-family: var(--font-display); font-size: clamp(18px, 2.4vw, 27px); font-weight: 500; letter-spacing: -.04em; text-shadow: 0 1px 12px rgb(0 0 0 / 20%); }
.profile__body { padding: 0 18px; }
.profile__identity { display: grid; grid-template-columns: 88px minmax(0,1fr) auto; align-items: center; gap: 20px; margin-top: 25px; }
.profile__avatar { position: relative; z-index: 2; display: grid; width: 88px; height: 88px; place-items: center; overflow: hidden; border: 4px solid var(--background); border-radius: 50%; background: #fff; box-shadow: 0 4px 18px rgb(18 38 30 / 12%); }
.profile__avatar img { width: 100%; height: 100%; object-fit: cover; }
.profile__copy { min-width: 0; padding: 0; }
.profile__name-row { display: flex; align-items: center; gap: 8px; }
.profile__name-row h1 { margin: 0; color: var(--ink); font-family: var(--font-display); font-size: clamp(23px,3vw,30px); font-weight: 600; letter-spacing: -.055em; }
.verified { width: 20px; height: 20px; flex: 0 0 20px; color: #0000f8; }
.profile__handle { margin: 2px 0 9px; color: var(--muted); font-size: 13px; }
.profile__bio { max-width: 620px; margin: 0; color: var(--text); font-size: 14px; line-height: 1.65; }
.profile__bio a { color: var(--gallery-green); font-weight: 600; text-decoration: none; white-space: nowrap; }
.profile__cta { display: inline-flex; align-items: center; justify-content: center; gap: 14px; min-height: 42px; margin-top: 0; padding: 0 17px; border: 1px solid var(--line); border-radius: 999px; color: var(--ink); font-size: 12px; font-weight: 600; text-decoration: none; transition: border-color .2s, background .2s; }
.profile__cta:hover { border-color: var(--gallery-green); background: color-mix(in srgb, var(--gallery-green) 7%, transparent); }
.profile__cta span { font-size: 16px; }
.profile__meta { display: flex; align-items: center; gap: 14px; margin: 20px 0 0 108px; color: var(--muted); font-size: 12px; }
.profile__meta strong { color: var(--ink); font-weight: 600; }
.profile__meta i { width: 3px; height: 3px; border-radius: 50%; background: var(--muted); opacity: .55; }
.profile__location { display: inline-flex; align-items: center; gap: 5px; }
.gallery-section { padding: 0; }
.gallery-heading { display: flex; align-items: end; justify-content: space-between; gap: 20px; margin-bottom: 25px; }
.eyebrow { display: flex; align-items: center; gap: 9px; margin: 0 0 12px; color: var(--muted); font-size: 10px; font-weight: 600; letter-spacing: .12em; text-transform: uppercase; }
.eyebrow span,.gallery-count__dot { width: 7px; height: 7px; border-radius: 50%; background: var(--gallery-green); box-shadow: 0 0 0 3px color-mix(in srgb, var(--gallery-green) 12%, transparent); }
.gallery-heading h2 { margin: 0; color: var(--ink); font-family: var(--font-display); font-size: clamp(28px,4vw,42px); font-weight: 500; letter-spacing: -.065em; line-height: 1.1; }
.gallery-heading__sub { margin: 9px 0 0; color: var(--muted); font-size: 13px; }
.gallery-count { display: flex; align-items: center; gap: 9px; padding: 9px 12px; border: 1px solid var(--line); border-radius: 999px; color: var(--muted); font-size: 11px; white-space: nowrap; }
.gallery-count__dot { width: 6px; height: 6px; box-shadow: none; }
.filters { display: flex; gap: 6px; margin: 0 0 18px; overflow-x: auto; scrollbar-width: none; }
.filters::-webkit-scrollbar { display: none; }
.filters button { flex: 0 0 auto; min-height: 32px; padding: 0 12px; border: 1px solid transparent; border-radius: 999px; background: transparent; color: var(--muted); font: inherit; font-size: 11px; cursor: pointer; transition: background .18s,color .18s,border-color .18s; }
.filters button:hover { color: var(--ink); }
.filters button.is-active { border-color: var(--line); background: var(--surface); color: var(--ink); font-weight: 600; }
.photo-grid { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 0; }
.photo-tile { position: relative; display: block; aspect-ratio: 1; min-width: 0; padding: 0; overflow: hidden; border: 0; border-radius: 0; background: var(--surface); cursor: pointer; isolation: isolate; }
.photo-tile img { display: block; width: 100%; height: 100%; object-fit: cover; transition: transform .5s cubic-bezier(.2,.7,.2,1), filter .3s; }
.photo-tile__veil { position: absolute; inset: 0; display: grid; place-items: center; background: linear-gradient(180deg,transparent 35%,rgb(10 25 18 / 50%)); opacity: 0; transition: opacity .25s; }
.photo-tile__detail { display: inline-flex; align-items: center; gap: 9px; padding: 10px 14px; border: 1px solid rgb(255 255 255 / 48%); border-radius: 999px; background: rgb(255 255 255 / 17%); color: #fff; font-size: 11px; font-weight: 600; backdrop-filter: blur(12px); }
.photo-tile:hover img,.photo-tile:focus-visible img { transform: scale(1.045); }
.photo-tile:hover .photo-tile__veil,.photo-tile:focus-visible .photo-tile__veil { opacity: 1; }
.photo-tile:focus-visible { outline: 2px solid var(--gallery-green); outline-offset: 3px; z-index: 1; }
.gallery-note { display: flex; align-items: center; justify-content: center; gap: 16px; padding: 50px 0 14px; }
.gallery-note p { margin: 0; color: var(--muted); font-family: var(--font-display); font-size: 12px; letter-spacing: -.01em; }
.gallery-note__line { width: clamp(20px,8vw,90px); height: 1px; background: var(--line); }
.photo-modal { position: fixed; z-index: 10000; inset: 0; display: grid; place-items: center; padding: 24px; background: rgb(9 16 13 / 72%); backdrop-filter: blur(10px); animation: modal-in .18s ease-out; }
.photo-modal__close { position: absolute; top: 20px; right: 24px; display: grid; width: 42px; height: 42px; place-items: center; border: 1px solid rgb(255 255 255 / 26%); border-radius: 50%; background: rgb(255 255 255 / 10%); color: #fff; font-size: 28px; font-weight: 300; cursor: pointer; }
.photo-modal__card { display: grid; grid-template-columns: minmax(0,1.15fr) minmax(270px,.75fr); width: min(960px,100%); max-height: min(680px,88svh); overflow: hidden; border: 1px solid rgb(255 255 255 / 14%); border-radius: 14px; background: var(--background); box-shadow: 0 24px 90px rgb(0 0 0 / 36%); }
.photo-modal__image { min-height: 320px; background: #101b16; }
.photo-modal__image img { display: block; width: 100%; height: 100%; max-height: min(680px,88svh); object-fit: cover; }
.photo-modal__caption { display: flex; flex-direction: column; justify-content: center; padding: clamp(24px,4vw,42px); }
.photo-modal__brand { display: flex; align-items: center; gap: 11px; padding-bottom: 22px; border-bottom: 1px solid var(--line); }
.photo-modal__avatar { display: grid; width: 37px; height: 37px; place-items: center; border-radius: 50%; background: #1c513e; color: #eef8ef; font-size: 22px; }
.photo-modal__brand strong,.photo-modal__brand small { display: block; }
.photo-modal__brand strong { color: var(--ink); font-size: 12px; }
.photo-modal__brand small { margin-top: 3px; color: var(--muted); font-size: 10px; }
.photo-modal__caption h2 { margin: 24px 0 10px; color: var(--ink); font-family: var(--font-display); font-size: 25px; font-weight: 500; letter-spacing: -.05em; }
.photo-modal__caption p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.8; }
.photo-modal__date { margin-top: 28px; color: var(--muted); font-size: 10px; letter-spacing: .08em; text-transform: uppercase; }
@keyframes modal-in { from { opacity: 0; } to { opacity: 1; } }
@media (max-width: 700px) {
  .profile { margin: 20px 0 52px; padding-bottom: 19px; }
  .profile__cover { min-height: 144px; padding: 16px; border-radius: 11px; }
  .profile__body { padding: 0 8px; }
  .profile__identity { grid-template-columns: 68px minmax(0,1fr); align-items: center; gap: 12px; margin-top: 19px; }
  .profile__avatar { width: 68px; height: 68px; border-width: 3px; }
  .profile__copy { padding-top: 0; }
  .profile__name-row h1 { font-size: 23px; }
  .verified { width: 17px; height: 17px; flex-basis: 17px; }
  .profile__bio { font-size: 12px; }
  .profile__cta { grid-column: 2; justify-self: start; min-height: 36px; margin: 0; padding: 0 13px; font-size: 11px; }
  .profile__meta { flex-wrap: wrap; gap: 8px; margin: 17px 0 0; font-size: 10px; }
  .gallery-heading { align-items: start; }
  .gallery-count { margin-top: 2px; padding: 8px 9px; font-size: 10px; }
  .gallery-heading__sub { max-width: 280px; font-size: 12px; }
  .photo-grid { gap: 0; }
  .photo-tile { border-radius: 0; }
  .photo-tile__veil { display: none; }
  .photo-modal { padding: 16px; }
  .photo-modal__card { grid-template-columns: 1fr; width: min(480px,100%); max-height: 90svh; overflow: auto; }
  .photo-modal__image { min-height: 0; height: min(52svh,440px); }
  .photo-modal__image img { max-height: 100%; }
  .photo-modal__caption { padding: 20px 22px 24px; }
  .photo-modal__brand { padding-bottom: 15px; }
  .photo-modal__caption h2 { margin-top: 18px; font-size: 21px; }
  .photo-modal__date { margin-top: 17px; }
  .photo-modal__close { top: 10px; right: 10px; z-index: 2; }
}
@media (prefers-reduced-motion: reduce) { *,*::before,*::after { animation-duration: .01ms !important; transition-duration: .01ms !important; } }
</style>
