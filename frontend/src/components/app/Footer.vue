<template>
  <footer class="w-full bg-[var(--ink)] text-[var(--inverse-text)] mt-auto" id="kontak">
    <div class="max-w-6xl mx-auto px-6 md:px-10 pt-16 pb-8">
      <!-- Grid atas -->
      <div class="grid grid-cols-2 md:grid-cols-5 gap-10 pb-12 border-b border-white/10">
        <!-- Brand kolom -->
        <div class="col-span-2 md:col-span-2 flex flex-col gap-5 pr-0 md:pr-8">
          <a href="/" class="inline-flex items-center gap-2.5 group" :aria-label="'Beranda Rumah Natasy'">
            <img src="/icon.svg" alt="" aria-hidden="true" class="w-9 h-9 brightness-200 object-contain" />
            <span class="font-display font-semibold text-xl text-white tracking-tight">Rumah Natasy</span>
          </a>
          <p class="text-white/60 text-sm leading-relaxed max-w-xs">
            Platform konsultasi psikologi online terpercaya. Bersama psikolog berlisensi, kapan saja, di mana saja.
          </p>
          <!-- Sosial -->
          <nav class="flex items-center gap-3 mt-1" aria-label="Media sosial Rumah Natasy">
            <a
              v-for="social in socialLinks"
              :key="social.icon"
              :href="social.href"
              target="_blank"
              rel="noreferrer"
              :aria-label="social.label"
              class="w-8 h-8 rounded-full bg-white/10 hover:bg-[var(--accent)] flex items-center justify-center text-white/70 hover:text-white transition-all duration-200"
            >
              <SimpleBlueskyIcon v-if="social.icon === 'bluesky'" :size="14" />
              <SimpleInstagramIcon v-else-if="social.icon === 'instagram'" :size="14" />
              <SimpleTiktokIcon v-else-if="social.icon === 'tiktok'" :size="14" />
              <SimpleXIcon v-else :size="14" />
            </a>
          </nav>
        </div>

        <!-- Kolom link -->
        <div v-for="group in linkGroups" :key="group.title" class="col-span-1 flex flex-col gap-4">
          <h3 class="text-white font-semibold text-xs uppercase tracking-widest">{{ group.title }}</h3>
          <ul class="flex flex-col gap-2.5" role="list">
            <li v-for="link in group.links" :key="link.label">
              <a
                :href="link.href"
                class="text-white/55 text-sm hover:text-white transition-colors duration-200"
              >
                {{ link.label }}
              </a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Bawah: copyright + legal -->
      <div class="pt-8 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 text-white/40 text-xs">
        <p>{{ copyright }}</p>
        <div class="flex flex-wrap gap-4">
          <a href="#" class="hover:text-white transition-colors">Kebijakan Privasi</a>
          <a href="#" class="hover:text-white transition-colors">Syarat Penggunaan</a>
          <a href="#" class="hover:text-white transition-colors">Aksesibilitas</a>
        </div>
      </div>
    </div>
  </footer>
</template>

<script setup lang="ts">
type SocialLink = {
  label: string
  href: string
  icon: 'bluesky' | 'instagram' | 'tiktok' | 'x'
}

defineProps<{
  copyright: string
  socialLinks: SocialLink[]
}>()

const linkGroups = [
  {
    title: 'Layanan',
    links: [
      { label: 'Konsultasi Individual', href: '#' },
      { label: 'Konsultasi Pasangan', href: '#' },
      { label: 'Konsultasi Anak & Remaja', href: '#' },
      { label: 'Semua Psikolog', href: '#' },
    ],
  },
  {
    title: 'Perusahaan',
    links: [
      { label: 'Tentang Kami', href: '#' },
      { label: 'Bergabung sebagai Psikolog', href: '#' },
      { label: 'Blog Kesehatan Mental', href: '#' },
      { label: 'Karier', href: '#' },
    ],
  },
  {
    title: 'Bantuan',
    links: [
      { label: 'Pusat Bantuan', href: '#' },
      { label: 'Kontak Kami', href: '#kontak' },
      { label: 'Cara Pembayaran', href: '#' },
      { label: 'Kebijakan Refund', href: '#' },
    ],
  },
]
</script>
