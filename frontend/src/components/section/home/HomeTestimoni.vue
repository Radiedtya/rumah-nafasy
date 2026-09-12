<template>
  <section id="ulasan" aria-labelledby="testimoni-heading" class="py-20 md:py-28">
    <div class="text-center mb-14">
      <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Kata Mereka</p>
      <h2 id="testimoni-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight">
        Klien Mencintai Rumah Natasy
      </h2>
      <p class="mt-4 text-[var(--copy)] text-sm">Lebih dari <strong class="text-[var(--ink)]">10.000 sesi</strong> berhasil terlaksana dengan kepuasan 97%.</p>
    </div>

    <!-- Grid testimoni -->
    <div class="columns-1 sm:columns-2 lg:columns-3 gap-5 space-y-5">
      <blockquote
        v-for="t in testimoni"
        :key="t.name"
        class="break-inside-avoid rounded-3xl border border-[var(--line)] bg-[var(--background)] p-6 flex flex-col gap-4"
      >
        <!-- Bintang -->
        <div class="flex gap-1" :aria-label="`Rating ${t.stars} dari 5 bintang`">
          <span
            v-for="n in 5"
            :key="n"
            class="text-lg leading-none"
            :class="n <= t.stars ? 'text-amber-400' : 'text-[var(--line)]'"
            aria-hidden="true"
          >★</span>
        </div>

        <!-- Kutipan -->
        <p class="text-[var(--copy)] text-sm leading-relaxed flex-1">
          "{{ t.quote }}"
        </p>

        <!-- Identitas -->
        <footer class="flex items-center gap-3 pt-2 border-t border-[var(--line)]">
          <div
            class="w-9 h-9 rounded-full shrink-0 flex items-center justify-center text-sm font-bold text-white"
            :style="{ background: t.avatarColor }"
            aria-hidden="true"
          >
            {{ t.name[0] }}
          </div>
          <div class="min-w-0">
            <p class="font-semibold text-[var(--ink)] text-xs leading-tight">{{ t.name }}</p>
            <p class="text-[var(--muted)] text-[10px]">{{ t.location }} · {{ t.issue }}</p>
          </div>
        </footer>
      </blockquote>
    </div>

    <!-- Rating agregat -->
    <div class="mt-12 flex flex-col sm:flex-row items-center justify-center gap-8 py-8 border-y border-[var(--line)]">
      <div class="text-center">
        <p class="font-display text-5xl font-bold text-[var(--ink)]">4.9</p>
        <p class="text-[var(--muted)] text-xs mt-1">Rating rata-rata</p>
        <div class="flex gap-0.5 justify-center mt-1" aria-hidden="true">
          <span v-for="n in 5" :key="n" class="text-amber-400 text-sm">★</span>
        </div>
      </div>
      <div class="h-12 w-px bg-[var(--line)] hidden sm:block" aria-hidden="true" />
      <div class="text-center">
        <p class="font-display text-5xl font-bold text-[var(--ink)]">97%</p>
        <p class="text-[var(--muted)] text-xs mt-1">Kepuasan klien</p>
      </div>
      <div class="h-12 w-px bg-[var(--line)] hidden sm:block" aria-hidden="true" />
      <div class="text-center">
        <p class="font-display text-5xl font-bold text-[var(--ink)]">10K+</p>
        <p class="text-[var(--muted)] text-xs mt-1">Sesi berhasil</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
const testimoni = [
  {
    name: 'Anisa R.',
    location: 'Jakarta',
    issue: 'Kecemasan',
    stars: 5,
    quote: 'Saya tidak pernah menyangka bisa berbicara dengan psikolog seenyaman ini. Prosesnya mudah dan psikolognya sangat memahami kondisi saya. Sangat membantu!',
    avatarColor: 'linear-gradient(135deg, #f9a8d4, #fda4af)',
  },
  {
    name: 'Dito S.',
    location: 'Surabaya',
    issue: 'Burnout',
    stars: 5,
    quote: 'Setelah burnout parah, saya coba Rumah Natasy. Dalam 4 sesi saja saya sudah merasakan perbedaan besar. Harganya juga sangat terjangkau.',
    avatarColor: 'linear-gradient(135deg, #93c5fd, #6ee7b7)',
  },
  {
    name: 'Maya K.',
    location: 'Bandung',
    issue: 'Hubungan',
    stars: 5,
    quote: 'Psikolognya sabar sekali mendengarkan masalah saya. Tidak menghakimi, sangat profesional. Sekarang hubungan saya dengan pasangan jauh lebih baik.',
    avatarColor: 'linear-gradient(135deg, #a78bfa, #c084fc)',
  },
  {
    name: 'Farid A.',
    location: 'Medan',
    issue: 'Depresi',
    stars: 5,
    quote: 'Platform yang benar-benar mengutamakan privasi. Saya merasa aman bercerita. Notifikasi WhatsApp-nya juga sangat membantu agar tidak lupa jadwal.',
    avatarColor: 'linear-gradient(135deg, #6ee7b7, #34d399)',
  },
  {
    name: 'Rina L.',
    location: 'Yogyakarta',
    issue: 'Trauma',
    stars: 5,
    quote: 'Saya sudah coba beberapa platform konsultasi, Rumah Natasy yang terbaik. Psikolognya beneran berlisensi dan pendekatan terapinya terasa sangat klinis dan terstruktur.',
    avatarColor: 'linear-gradient(135deg, #fbbf24, #fb923c)',
  },
  {
    name: 'Bagas P.',
    location: 'Semarang',
    issue: 'Self-esteem',
    stars: 5,
    quote: 'Jadwal yang fleksibel sangat membantu saya yang sibuk kerja. Bisa konsultasi malam hari setelah pulang kantor. Benar-benar game changer!',
    avatarColor: 'linear-gradient(135deg, #f87171, #fb923c)',
  },
]
</script>
