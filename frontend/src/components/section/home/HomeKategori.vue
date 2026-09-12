<template>
  <section id="kategori" aria-labelledby="kategori-heading" class="py-20 md:py-28">
    <div class="text-center mb-12">
      <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Layanan Konsultasi</p>
      <h2 id="kategori-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight">
        Konsultasi untuk Hampir Semua Kondisi
      </h2>
      <p class="mt-4 text-[var(--copy)] text-base max-w-lg mx-auto leading-relaxed">
        Dari kecemasan sehari-hari hingga kondisi klinis—kami punya psikolog yang tepat untuk Anda.
      </p>
    </div>

    <!-- Filter tabs -->
    <div class="flex items-center justify-center gap-2 flex-wrap mb-10" role="tablist" aria-label="Filter kategori layanan">
      <button
        v-for="tab in tabs"
        :key="tab"
        type="button"
        role="tab"
        :aria-selected="activeTab === tab"
        @click="activeTab = tab"
        class="px-4 py-2 rounded-full text-sm font-medium transition-all duration-200 border"
        :class="activeTab === tab
          ? 'bg-[var(--accent)] text-white border-[var(--accent)]'
          : 'bg-transparent text-[var(--copy)] border-[var(--line)] hover:border-[var(--accent)]/50'"
      >
        {{ tab }}
      </button>
    </div>

    <!-- Grid kategori -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
      <a
        v-for="item in filteredCategories"
        :key="item.title"
        href="#"
        class="group flex items-center justify-between gap-4 p-5 rounded-2xl border border-[var(--line)] bg-[var(--background)] hover:border-[var(--accent)]/40 hover:bg-[var(--accent)]/3 transition-all duration-200"
        :aria-label="`Konsultasi ${item.title}, ${item.price}`"
      >
        <div class="flex items-center gap-4 min-w-0">
          <div class="w-12 h-12 rounded-xl shrink-0 flex items-center justify-center text-2xl" :style="{ background: item.iconBg }">
            {{ item.emoji }}
          </div>
          <div class="min-w-0">
            <h3 class="font-semibold text-[var(--ink)] text-sm leading-snug">{{ item.title }}</h3>
            <p class="text-[var(--muted)] text-xs mt-0.5">{{ item.price }}</p>
          </div>
        </div>
        <ArrowUpRightIcon
          class="w-4 h-4 text-[var(--muted)] shrink-0 group-hover:text-[var(--accent)] group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-all"
          aria-hidden="true"
        />
      </a>
    </div>

    <!-- Lihat semua -->
    <div class="mt-10 text-center">
      <a
        href="#"
        class="inline-flex items-center gap-1.5 text-[var(--accent)] font-semibold text-sm hover:opacity-70 transition-opacity group"
      >
        Lihat semua {{ totalCount }} layanan
        <ArrowUpRightIcon class="w-4 h-4 group-hover:translate-x-0.5 group-hover:-translate-y-0.5 transition-transform" aria-hidden="true" />
      </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ArrowUpRightIcon } from '@heroicons/vue/24/outline'

const tabs = ['Populer', 'Kecemasan', 'Hubungan', 'Diri Sendiri', 'Kondisi Klinis']
const activeTab = ref('Populer')

const allCategories = [
  { title: 'Kecemasan & Panik', price: 'Mulai Rp 120.000/sesi', emoji: '😰', tag: 'Kecemasan', iconBg: '#fef3c7' },
  { title: 'Depresi', price: 'Mulai Rp 130.000/sesi', emoji: '🌧️', tag: 'Kondisi Klinis', iconBg: '#e0f2fe' },
  { title: 'Trauma & PTSD', price: 'Mulai Rp 150.000/sesi', emoji: '💔', tag: 'Kondisi Klinis', iconBg: '#ffe4e6' },
  { title: 'Hubungan & Pasangan', price: 'Mulai Rp 140.000/sesi', emoji: '💑', tag: 'Hubungan', iconBg: '#fce7f3' },
  { title: 'Masalah Keluarga', price: 'Mulai Rp 130.000/sesi', emoji: '🏠', tag: 'Hubungan', iconBg: '#f0fdf4' },
  { title: 'Self-Esteem & Kepercayaan Diri', price: 'Mulai Rp 110.000/sesi', emoji: '🌱', tag: 'Diri Sendiri', iconBg: '#f0fdf4' },
  { title: 'Burnout & Stres Kerja', price: 'Mulai Rp 120.000/sesi', emoji: '🔥', tag: 'Populer', iconBg: '#fff7ed' },
  { title: 'Grief & Kehilangan', price: 'Mulai Rp 130.000/sesi', emoji: '🕊️', tag: 'Kondisi Klinis', iconBg: '#f1f5f9' },
  { title: 'Masalah Tidur', price: 'Mulai Rp 110.000/sesi', emoji: '😴', tag: 'Populer', iconBg: '#ede9fe' },
  { title: 'Fobia', price: 'Mulai Rp 120.000/sesi', emoji: '😨', tag: 'Kecemasan', iconBg: '#fef9c3' },
  { title: 'Kesehatan Mental Remaja', price: 'Mulai Rp 120.000/sesi', emoji: '🧒', tag: 'Populer', iconBg: '#dcfce7' },
  { title: 'Pengembangan Diri', price: 'Mulai Rp 100.000/sesi', emoji: '✨', tag: 'Diri Sendiri', iconBg: '#fdf4ff' },
]

const filteredCategories = computed(() => {
  if (activeTab.value === 'Populer') return allCategories.filter((c) => c.tag === 'Populer').concat(allCategories.filter((c) => c.tag !== 'Populer')).slice(0, 6)
  return allCategories.filter((c) => c.tag === activeTab.value)
})

const totalCount = allCategories.length
</script>
