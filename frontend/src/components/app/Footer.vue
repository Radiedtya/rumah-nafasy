<template>
  <footer id="kontak" class="site-footer" :class="theme === 'dark' ? 'site-footer--dark' : 'site-footer--light'">
    <div class="footer-background" aria-hidden="true">
      <ShapeWaves
        text="Nafasy"
        font-family='Geist, "Geist Sans", system-ui, sans-serif'
        :font-weight="500"
        :text-size="0.44"
        shapes="mixed"
        :cell-size="10"
        :dot-size="0.58"
        :color="theme === 'dark' ? '#171717' : '#d0d0cd'"
        :hover-color="theme === 'dark' ? '#279772' : '#279772'"
        :background-color="theme === 'dark' ? '#000000' : '#f5f5f2'"
        :speed="0.32"
        :scale="1.1"
        :contrast="0.85"
        :brightness="0.3"
        :flow="0"
        :direction="0"
        :fade="0.35"
        interactive
        :splash-radius="44"
        :splash-strength="0.35"
        :glow="0.08"
        intro
        :intro-duration="1.6"
      />
    </div>
    <div class="footer-wrap">

      <!-- ══ TOP: brand + kolom + newsletter ══ -->
      <div class="footer-top">

        <!-- Brand -->
        <div class="footer-brand-col">
          <RouterLink to="/" class="footer-brand" aria-label="Beranda Rumah Nafasy">
            <img src="/icons/64.png" alt="" aria-hidden="true" class="footer-brand-icon" />
            <span class="footer-brand-name">Rumah Nafasy</span>
          </RouterLink>
        </div>

        <!-- Perusahaan -->
        <div class="footer-col">
          <h3 class="footer-col-title">Perusahaan</h3>
          <ul class="footer-links" role="list" @pointerover="handleFooterPointerOver" @pointerout="handleFooterPointerOut" @animationend="handleFooterAnimationEnd">
            <li><a href="#">Harga layanan</a></li>
            <li><a href="#">Ulasan klien</a></li>
            <li><a href="#">Karier</a></li>
            <li><a href="#">Pers &amp; Media</a></li>
            <li><a href="#">Riset &amp; Data</a></li>
            <li><a href="#">Tentang kami</a></li>
            <li><a href="#">Keunggulan kami</a></li>
            <li><a href="#">Program afiliasi</a></li>
            <li><a href="#">Ajak teman</a></li>
            <li><a href="#">Program advokat kesehatan mental</a></li>
          </ul>
        </div>

        <!-- Sumber Daya -->
        <div class="footer-col">
          <h3 class="footer-col-title">Sumber Daya</h3>
          <ul class="footer-links" role="list" @pointerover="handleFooterPointerOver" @pointerout="handleFooterPointerOut" @animationend="handleFooterAnimationEnd">
            <li><a href="#">Pusat bantuan</a></li>
            <li><a href="#">Komunitas kesehatan mental</a></li>
            <li><a href="#">Blog</a></li>
            <li><a href="#">Kondisi kesehatan mental</a></li>
            <li><a href="#">Tes kesehatan mental gratis</a></li>
            <li><a href="#">Panduan mandiri</a></li>
          </ul>
        </div>

        <!-- Legal -->
        <div class="footer-col">
          <h3 class="footer-col-title">Legal</h3>
          <ul class="footer-links" role="list" @pointerover="handleFooterPointerOver" @pointerout="handleFooterPointerOut" @animationend="handleFooterAnimationEnd">
            <li><a href="#">Kebijakan privasi</a></li>
            <li><a href="#">Syarat penggunaan</a></li>
            <li><a href="#">Aksesibilitas</a></li>
            <li><a href="#">Pengaturan privasi</a></li>
            <li><a href="#">Hak privasi per wilayah</a></li>
            <li><a href="#">Saluran pengaduan</a></li>
          </ul>
        </div>

        <!-- Newsletter -->
        <div class="footer-col footer-col--newsletter">
          <h3 class="footer-col-title">Berlangganan newsletter</h3>
          <p class="footer-newsletter-desc">
            Dapatkan artikel kesehatan mental, promo, dan info terbaru langsung di inbox Anda.
          </p>
          <form class="footer-subscribe-form" @submit.prevent="submitEmail">
            <div class="footer-subscribe-row">
              <label for="footer-email" class="sr-only">Alamat email</label>
              <input
                id="footer-email"
                v-model="email"
                type="email"
                class="footer-subscribe-input"
                placeholder="you@domain.com"
                required
                autocomplete="email"
              />
              <button type="submit" class="footer-subscribe-btn">Berlangganan</button>
            </div>
          </form>
        </div>

      </div>

      <!-- ══ BOTTOM: copyright kiri, lisensi tengah, sosial + theme kanan ══ -->
      <div class="footer-bottom">
        <p class="footer-copyright">{{ copyright }}</p>

        <div class="footer-licenses" aria-label="Lisensi dan sertifikasi">
          <span v-for="license in licenses" :key="license" class="footer-license">
            <img :src="`/images/license/${license}.png`" :alt="`Logo lisensi ${license}`" />
          </span>
        </div>

        <div class="footer-bottom-right">
          <nav class="footer-social" aria-label="Media sosial Rumah Nafasy" @pointerover="handleFooterPointerOver" @pointerout="handleFooterPointerOut" @animationend="handleFooterAnimationEnd">
            <a v-for="s in socials" :key="s.label" :href="s.href" target="_blank" rel="noreferrer" :aria-label="s.label" class="footer-social-link">
              <component :is="s.icon" aria-hidden="true" />
            </a>
          </nav>
          <button
            type="button"
            class="footer-theme-btn"
            :aria-label="theme === 'light' ? 'Aktifkan dark mode' : 'Aktifkan light mode'"
            @click="toggleTheme"
          >
            <!-- Sun -->
            <svg v-if="theme === 'light'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true">
              <circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/>
            </svg>
            <!-- Moon -->
            <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16" aria-hidden="true">
              <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
            </svg>
          </button>
        </div>
      </div>

    </div>
  </footer>
</template>

<script setup lang="ts">
import { ref, defineComponent, h, markRaw } from 'vue'
import { useTheme } from '../../composables/useTheme'
import ShapeWaves from './ShapeWaves.vue'

type SocialLink = {
  label: string
  href: string
  icon: 'bluesky' | 'instagram' | 'tiktok' | 'x'
}

defineProps<{
  copyright: string
  socialLinks?: SocialLink[]
}>()

const { theme, toggleTheme } = useTheme()
const email = ref('')

function getFooterLink(target: EventTarget | null): HTMLAnchorElement | null {
  return target instanceof Element ? target.closest('a') : null
}

function setFooterHighlight(link: HTMLAnchorElement, state: 'entering' | 'exiting') {
  link.classList.add('footer-highlight')
  link.classList.remove('footer-highlight--hidden', 'footer-highlight--entering', 'footer-highlight--active', 'footer-highlight--exiting')
  link.classList.add(`footer-highlight--${state}`)
}

function handleFooterPointerOver(event: PointerEvent) {
  const link = getFooterLink(event.target)
  if (!link || (event.relatedTarget instanceof Node && link.contains(event.relatedTarget))) return
  setFooterHighlight(link, 'entering')
}

function handleFooterPointerOut(event: PointerEvent) {
  const link = getFooterLink(event.target)
  if (!link || (event.relatedTarget instanceof Node && link.contains(event.relatedTarget))) return
  setFooterHighlight(link, 'exiting')
}

function handleFooterAnimationEnd(event: AnimationEvent) {
  const link = getFooterLink(event.target)
  if (!link) return
  if (link.classList.contains('footer-highlight--entering')) {
    link.classList.remove('footer-highlight--entering')
    link.classList.add('footer-highlight--active')
  } else if (link.classList.contains('footer-highlight--exiting')) {
    link.classList.remove('footer-highlight--exiting')
    link.classList.add('footer-highlight--hidden')
  }
}

function submitEmail() {
  email.value = ''
}

const IconFacebook = defineComponent({ render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', width: 16, height: 16 }, [h('path', { d: 'M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z' })]) })
const IconX        = defineComponent({ render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', width: 16, height: 16 }, [h('path', { d: 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-4.714-6.231-5.401 6.231H2.744l7.73-8.835L1.254 2.25H8.08l4.253 5.622zm-1.161 17.52h1.833L7.084 4.126H5.117z' })]) })
const IconInsta    = defineComponent({ render: () => h('svg', { viewBox: '0 0 24 24', fill: 'none', stroke: 'currentColor', 'stroke-width': '2', 'stroke-linecap': 'round', 'stroke-linejoin': 'round', width: 16, height: 16 }, [h('rect', { x: '2', y: '2', width: '20', height: '20', rx: '5' }), h('path', { d: 'M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z' }), h('line', { x1: '17.5', y1: '6.5', x2: '17.51', y2: '6.5' })]) })
const IconLinkedin = defineComponent({ render: () => h('svg', { viewBox: '0 0 24 24', fill: 'currentColor', width: 16, height: 16 }, [h('path', { d: 'M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-4 0v7h-4V9h4v2a6 6 0 0 1 2-2zM2 9h4v12H2z' }), h('circle', { cx: '4', cy: '4', r: '2' })]) })

const socials = [
  { label: 'Facebook',  href: 'https://facebook.com',  icon: markRaw(IconFacebook) },
  { label: 'X',         href: 'https://x.com',         icon: markRaw(IconX)        },
  { label: 'Instagram', href: 'https://instagram.com', icon: markRaw(IconInsta)    },
  { label: 'LinkedIn',  href: 'https://linkedin.com',  icon: markRaw(IconLinkedin) },
]

const licenses = ['one', 'two', 'tree', 'four', 'five', 'six', 'seven']
</script>

<style scoped>
/* ══ Mengikuti tema — background & teks dari design token ══ */
.site-footer {
  position: relative;
  z-index: 2;
  isolation: isolate;
  width: 100%;
  min-height: 100vh;
  min-height: 100svh;
  display: flex;
  flex-direction: column;
  background: var(--background);
  color: var(--text);
}

.site-footer::before {
  position: absolute;
  z-index: 0;
  top: -220px;
  right: 0;
  left: 0;
  height: 260px;
  background: linear-gradient(to bottom, transparent, var(--background));
  content: '';
  pointer-events: none;
}

.site-footer--dark {
  --background: #080808;
  --surface: #101010;
  --text: #f4f4f4;
  --muted: #b0b0b0;
  --footer-link: #ffffff;
  --line: #242424;
  --ink: #ffffff;
  --inverse-text: #111111;
}

.site-footer--light {
  --background: #f5f5f2;
  --surface: #ffffff;
  --text: #242424;
  --muted: #686865;
  --footer-link: #111111;
  --line: #d9d9d4;
  --ink: #171717;
  --inverse-text: #f7f7f5;
}

.footer-background {
  position: absolute;
  z-index: -1;
  inset: 0;
  overflow: hidden;
  opacity: 1;
  pointer-events: none;
}

.footer-wrap {
  position: relative;
  z-index: 1;
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  width: 100%;
    max-width: none;
  margin: 0 auto;
    padding: 0 clamp(24px, 5vw, 80px);
}

/* ══ Top grid ══ */
.footer-top {
  display: grid;
  grid-template-columns: 1fr 1.2fr 1fr 0.8fr 1.4fr;
  gap: 56px;
  padding: clamp(96px, 11vh, 144px) 0 72px;
  flex: 1;
  align-items: start;
}

/* Brand */
.footer-brand-col {
  padding-right: 8px;
}

.footer-brand {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
}

.footer-brand-icon {
  width: 28px;
  height: 28px;
  object-fit: contain;
}

.footer-brand-name {
  color: var(--ink);
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.04em;
}

/* Columns */
.footer-col-title {
  margin: 0 0 18px;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: 18px;
  font-weight: 600;
  letter-spacing: -0.01em;
}

.footer-links {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.footer-links a {
  position: relative;
  isolation: isolate;
  display: inline-flex;
  width: fit-content;
  padding: 2px 4px;
  margin: -2px -4px;
  color: var(--footer-link);
  font-size: 17px;
  line-height: 1.45;
  text-decoration: none;
  transition: color 150ms ease;
}

.footer-links a:hover {
  color: #fff;
}

.footer-links a:focus-visible {
  color: #fff;
}

.footer-highlight::before {
  position: absolute;
  z-index: -1;
  inset: 0;
  background: #48008c;
  content: '';
}

.footer-links a::before,
.footer-social-link::before {
  clip-path: inset(100% 0 0 0);
}

.footer-highlight--hidden::before {
  clip-path: inset(100% 0 0 0);
}

.footer-highlight--entering::before {
  animation: footer-highlight-enter 280ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.footer-links a.footer-highlight--active::before,
.footer-social-link.footer-highlight--active::before,
.footer-links a:focus-visible::before,
.footer-social-link:focus-visible::before {
  clip-path: inset(0);
}

.footer-highlight--exiting::before {
  animation: footer-highlight-exit 280ms cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

@keyframes footer-highlight-enter {
  from { clip-path: inset(100% 0 0 0); }
  to { clip-path: inset(0); }
}

@keyframes footer-highlight-exit {
  from { clip-path: inset(0); }
  to { clip-path: inset(100% 0 0 0); }
}

/* Newsletter */
.footer-col--newsletter {
  padding-left: 8px;
}

.footer-newsletter-desc {
  margin: 0 0 16px;
  color: var(--muted);
  font-size: 16px;
  line-height: 1.6;
}

.footer-subscribe-form {
  width: 100%;
}

.footer-subscribe-row {
  display: flex;
  gap: 0;
  border: 1px solid var(--line);
  border-radius: 6px;
  overflow: hidden;
  transition: border-color 150ms ease;
}

.footer-subscribe-row:focus-within {
  border-color: var(--ink);
}

.footer-subscribe-input {
  flex: 1;
  min-height: 38px;
  padding: 0 12px;
  border: none;
  background: var(--background);
  color: var(--ink);
  font: inherit;
  font-size: 15px;
  outline: none;
}

.footer-subscribe-input::placeholder {
  color: var(--muted);
}

.footer-subscribe-btn {
  flex-shrink: 0;
  padding: 0 14px;
  border: none;
  border-left: 1px solid var(--line);
  background: var(--surface);
  color: var(--ink);
  font: inherit;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  white-space: nowrap;
  transition: background 150ms ease;
}

.footer-subscribe-btn:hover {
  background: color-mix(in srgb, var(--ink) 8%, var(--background));
}

/* Screen-reader only */
.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0,0,0,0);
  white-space: nowrap;
  border-width: 0;
}

/* ══ Bottom strip ══ */
.footer-bottom {
  display: grid;
  grid-template-columns: 1fr auto 1fr;
  align-items: center;
  padding: 20px 0 28px;
  border-top: 1px solid var(--line);
}

.footer-copyright {
  margin: 0;
  color: var(--muted);
  font-size: 14px;
}

.footer-licenses {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
}

.footer-license {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 28px;
}

.footer-license img {
  display: block;
  max-width: 100%;
  max-height: 100%;
  object-fit: contain;
}

.footer-bottom-right {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 18px;
}

.footer-social {
  display: flex;
  gap: 10px;
  align-items: center;
}

.footer-social-link {
  position: relative;
  isolation: isolate;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 30px;
  height: 30px;
  color: var(--footer-link);
  text-decoration: none;
  transition: color 150ms ease;
}

.footer-social-link:hover {
  color: #fff;
}

.footer-social-link:focus-visible {
  color: #fff;
}

@media (prefers-reduced-motion: reduce) {
  .footer-highlight--entering::before,
  .footer-highlight--exiting::before {
    animation-duration: 1ms;
  }
}

/* Theme toggle button */
.footer-theme-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  transition: border-color 150ms ease, color 150ms ease, background 150ms ease;
}

.footer-theme-btn:hover {
  border-color: var(--ink);
  color: var(--ink);
  background: color-mix(in srgb, var(--ink) 5%, transparent);
}

/* ══ Responsive ══ */
@media (max-width: 1024px) {
  .footer-top {
    grid-template-columns: 1fr 1fr 1fr;
    gap: 36px 28px;
  }

  .footer-brand-col {
    grid-column: 1 / -1;
  }

  .footer-col--newsletter {
    grid-column: 1 / -1;
    padding-left: 0;
    max-width: 420px;
  }
}

@media (max-width: 600px) {
  .footer-top {
    grid-template-columns: 1fr 1fr;
    padding: 64px 0 40px;
  }

  .footer-brand-col,
  .footer-col--newsletter {
    grid-column: 1 / -1;
  }

  .footer-bottom {
    grid-template-columns: 1fr;
    justify-items: start;
    gap: 16px;
  }

  .footer-licenses {
    flex-wrap: wrap;
    justify-content: flex-start;
    gap: 8px;
  }

  .footer-bottom-right {
    justify-content: flex-start;
  }
}
</style>
