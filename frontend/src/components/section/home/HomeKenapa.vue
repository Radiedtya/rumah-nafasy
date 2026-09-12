<template>
  <section id="kenapa" aria-labelledby="kenapa-heading" class="py-20 md:py-28">
    <!-- Header -->
    <div class="text-center mb-14">
      <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Mengapa Kami Berbeda</p>
      <h2 id="kenapa-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight">
        Kenapa Pilih Rumah Natasy?
      </h2>
      <p class="mt-4 text-[var(--copy)] text-base max-w-xl mx-auto leading-relaxed">
        Layanan kesehatan mental pertama di Indonesia yang menggabungkan kepercayaan, kemudahan, dan keahlian klinis.
      </p>
    </div>

    <!-- Grid keunggulan 3×2 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
      <article
        v-for="item in keunggulan"
        :key="item.title"
        class="relative rounded-3xl p-7 flex flex-col gap-4 overflow-hidden border transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
        :class="item.featured
          ? 'bg-[var(--ink)] border-transparent text-white'
          : 'bg-[var(--background)] border-[var(--line)] hover:border-[var(--accent)]/30'"
      >
        <!-- Icon -->
        <div
          class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0"
          :class="item.featured ? 'bg-white/10' : item.iconBg"
        >
          <component
            :is="item.icon"
            class="w-6 h-6"
            :class="item.featured ? 'text-white' : item.iconColor"
            aria-hidden="true"
          />
        </div>

        <!-- Konten -->
        <div>
          <h3 class="font-display font-semibold text-lg leading-snug mb-2" :class="item.featured ? 'text-white' : 'text-[var(--ink)]'">
            {{ item.title }}
          </h3>
          <p class="text-sm leading-relaxed" :class="item.featured ? 'text-white/70' : 'text-[var(--copy)]'">
            {{ item.description }}
          </p>
        </div>

        <!-- Badge detail kecil -->
        <div class="mt-auto">
          <span
            class="inline-block text-[10px] font-semibold px-3 py-1 rounded-full"
            :class="item.featured ? 'bg-white/15 text-white' : 'bg-[var(--line)]/50 text-[var(--muted)]'"
          >
            {{ item.badge }}
          </span>
        </div>

        <!-- Dekorasi abstrak sudut -->
        <div
          class="absolute -bottom-8 -right-8 w-32 h-32 rounded-full opacity-10"
          :style="{ background: item.featured ? '#fff' : 'var(--accent)' }"
          aria-hidden="true"
        />
      </article>
    </div>

    <!-- CTA strip -->
    <div class="mt-14 rounded-3xl bg-gradient-to-br from-[var(--accent)] to-[#ff8c85] p-8 md:p-10 flex flex-col md:flex-row items-center justify-between gap-6 text-white">
      <div>
        <h3 class="font-display text-2xl md:text-3xl font-semibold leading-snug">
          Siap mulai perjalanan<br class="hidden md:block" /> kesehatan mental Anda?
        </h3>
        <p class="mt-2 text-white/80 text-sm leading-relaxed max-w-md">
          Konsultasi pertama dengan diskon 50% untuk pengguna baru. Tidak ada komitmen jangka panjang.
        </p>
      </div>
      <div class="flex flex-col sm:flex-row gap-3 shrink-0">
        <a
          href="#mulai"
          class="inline-flex items-center justify-center px-6 py-3 rounded-full bg-white text-[var(--accent)] font-bold text-sm hover:opacity-90 active:scale-95 transition-all whitespace-nowrap"
        >
          Mulai Konsultasi
        </a>
        <a
          href="#ahli"
          class="inline-flex items-center justify-center px-6 py-3 rounded-full border border-white/40 text-white font-semibold text-sm hover:bg-white/10 active:scale-95 transition-all whitespace-nowrap"
        >
          Lihat Psikolog
        </a>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import {
  ShieldCheckIcon,
  CurrencyDollarIcon,
  CalendarDaysIcon,
  ChatBubbleLeftRightIcon,
  BellAlertIcon,
  CheckBadgeIcon,
} from '@heroicons/vue/24/outline'

const keunggulan = [
  {
    title: 'Psikolog Berlisensi',
    description: 'Semua psikolog kami memegang SIP (Surat Izin Praktik) resmi dan terdaftar aktif sebagai anggota HIMPsi. Tidak ada kompromi soal kredensial.',
    icon: CheckBadgeIcon,
    iconBg: 'bg-emerald-100',
    iconColor: 'text-emerald-600',
    badge: 'SIP · HIMPsi Terverifikasi',
    featured: false,
  },
  {
    title: 'Rahasia Terjamin',
    description: 'Data dan percakapan Anda dilindungi penuh oleh UU Perlindungan Data Pribadi (UU PDP). Tidak ada rekaman, tidak ada pihak ketiga.',
    icon: ShieldCheckIcon,
    iconBg: 'bg-sky-100',
    iconColor: 'text-sky-600',
    badge: 'Dilindungi UU PDP',
    featured: false,
  },
  {
    title: 'Harga Transparan',
    description: 'Tidak ada biaya tersembunyi. Harga disesuaikan dengan kategori kebutuhan klien—mulai dari Rp 100.000 per sesi.',
    icon: CurrencyDollarIcon,
    iconBg: 'bg-amber-100',
    iconColor: 'text-amber-600',
    badge: 'Mulai Rp 100.000/sesi',
    featured: true,
  },
  {
    title: 'Jadwal Fleksibel',
    description: 'Pilih jadwal sendiri kapan saja, reschedule tanpa kerumitan. Psikolog kami tersedia 7 hari seminggu termasuk malam hari.',
    icon: CalendarDaysIcon,
    iconBg: 'bg-violet-100',
    iconColor: 'text-violet-600',
    badge: 'Tersedia 7 hari/minggu',
    featured: false,
  },
  {
    title: 'Konsultasi Online',
    description: 'Video call atau chat teks—pilih sesuai kenyamanan Anda. Tidak perlu antre, tidak perlu keluar rumah.',
    icon: ChatBubbleLeftRightIcon,
    iconBg: 'bg-rose-100',
    iconColor: 'text-[var(--accent)]',
    badge: 'Video Call · Chat Teks',
    featured: false,
  },
  {
    title: 'Reminder Otomatis',
    description: 'Notifikasi sesi via WhatsApp sebelum jadwal tiba. Tidak ada sesi yang terlewat, tidak ada kata lupa.',
    icon: BellAlertIcon,
    iconBg: 'bg-orange-100',
    iconColor: 'text-orange-500',
    badge: 'Notifikasi WhatsApp',
    featured: false,
  },
]
</script>
