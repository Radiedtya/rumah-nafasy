<script setup lang="ts">
import { defineComponent, h } from 'vue'
import { CalendarDaysIcon, DevicePhoneMobileIcon, UserGroupIcon, CheckBadgeIcon } from '@heroicons/vue/24/outline'

// Ilustrasi inline ringan — pure HTML, zero extra dependencies
const IllustrasiPilih = defineComponent({
  name: 'IllustrasiPilih',
  render() {
    return h('div', { class: 'p-5 w-full' }, [
      h('p', { class: 'text-[10px] text-[var(--muted)] mb-3 uppercase tracking-widest' }, 'Temukan psikolog'),
      h('div', { class: 'flex flex-wrap gap-2 justify-center' },
        ['Kecemasan', 'Depresi', 'Hubungan', 'Trauma'].map((tag) =>
          h('span', { class: 'text-xs px-3 py-1.5 rounded-full border border-[var(--line)] text-[var(--copy)] bg-[var(--background)]' }, tag)
        )
      ),
      h('div', { class: 'mt-4 flex items-center gap-3 bg-[var(--background)] rounded-2xl p-3 border border-[var(--line)]' }, [
        h('div', { class: 'w-10 h-10 rounded-full bg-gradient-to-br from-rose-300 to-orange-200 shrink-0', 'aria-hidden': 'true' }),
        h('div', { class: 'min-w-0' }, [
          h('p', { class: 'text-xs font-semibold text-[var(--ink)] truncate' }, 'Dr. Sari Dewi, M.Psi'),
          h('p', { class: 'text-[10px] text-[var(--muted)]' }, 'Kecemasan · Trauma'),
        ]),
        h('span', { class: 'ml-auto text-[10px] text-emerald-600 font-semibold shrink-0' }, '✓ SIP'),
      ]),
    ])
  },
})

const IllustrasiJadwal = defineComponent({
  name: 'IllustrasiJadwal',
  render() {
    const days = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min']
    return h('div', { class: 'p-5 w-full' }, [
      h('p', { class: 'text-[10px] text-[var(--muted)] mb-3 uppercase tracking-widest' }, 'Pilih jadwal'),
      h('div', { class: 'grid grid-cols-7 gap-1 text-center mb-3' },
        days.map((d, i) =>
          h('div', {
            class: `text-[9px] py-1.5 rounded-lg font-medium ${i === 2 ? 'bg-[var(--accent)] text-white' : 'bg-[var(--line)]/40 text-[var(--muted)]'}`,
          }, d)
        )
      ),
      h('div', { class: 'flex flex-wrap gap-2 justify-center' },
        ['09.00', '10.00', '13.00', '15.00'].map((t, i) =>
          h('span', {
            class: `text-[11px] px-3 py-1.5 rounded-full border font-medium ${i === 1 ? 'border-[var(--accent)] text-[var(--accent)] bg-[var(--accent)]/5' : 'border-[var(--line)] text-[var(--copy)]'}`,
          }, t)
        )
      ),
    ])
  },
})

const IllustrasiSesi = defineComponent({
  name: 'IllustrasiSesi',
  render() {
    return h('div', { class: 'p-5 w-full' }, [
      h('div', { class: 'bg-[var(--background)] rounded-2xl border border-[var(--line)] overflow-hidden' }, [
        h('div', { class: 'bg-[var(--ink)] h-14 rounded-t-2xl flex items-center justify-center gap-3', 'aria-hidden': 'true' }, [
          h('div', { class: 'w-10 h-10 rounded-full bg-gradient-to-br from-rose-300 to-orange-200' }),
          h('div', { class: 'w-14 h-10 rounded-xl bg-gradient-to-br from-sky-300 to-blue-200' }),
        ]),
        h('div', { class: 'p-3' }, [
          h('div', { class: 'flex gap-2 mb-2' }, [
            h('div', { class: 'h-2 rounded bg-[var(--line)] flex-1' }),
            h('div', { class: 'h-2 rounded bg-[var(--accent)]/30 w-8' }),
          ]),
          h('p', { class: 'text-[10px] text-[var(--muted)]' }, '🎥 Sesi berlangsung · 45 menit'),
        ]),
      ]),
    ])
  },
})

const steps = [
  {
    number: '01',
    title: 'Temukan Psikolog Anda',
    description: 'Ceritakan kondisi Anda dan kami cocokkan dengan psikolog berlisensi yang paling sesuai. Semua tersertifikasi SIP dan HIMPsi.',
    icon: UserGroupIcon,
    iconBg: 'bg-rose-100',
    iconColor: 'text-[var(--accent)]',
    illustration: IllustrasiPilih,
  },
  {
    number: '02',
    title: 'Atur Jadwal Fleksibel',
    description: 'Pilih waktu yang nyaman. Reschedule mudah tanpa biaya tambahan, tersedia 7 hari seminggu termasuk malam hari.',
    icon: CalendarDaysIcon,
    iconBg: 'bg-sky-100',
    iconColor: 'text-sky-500',
    illustration: IllustrasiJadwal,
  },
  {
    number: '03',
    title: 'Mulai Sesi Konsultasi',
    description: 'Konsultasi via video call atau chat teks dari mana saja. Privasi terlindungi, tidak ada rekaman, sepenuhnya rahasia.',
    icon: DevicePhoneMobileIcon,
    iconBg: 'bg-emerald-100',
    iconColor: 'text-emerald-600',
    illustration: IllustrasiSesi,
  },
]
</script>

<template>
  <section id="cara-kerja" aria-labelledby="cara-kerja-heading" class="py-20 md:py-28">
    <div class="text-center mb-14">
      <p class="text-[var(--accent)] font-semibold tracking-widest text-xs uppercase mb-3">Proses Konsultasi</p>
      <h2 id="cara-kerja-heading" class="font-display text-3xl md:text-4xl font-semibold text-[var(--ink)] leading-tight">
        Bagaimana Cara Kerjanya?
      </h2>
      <p class="mt-4 text-[var(--copy)] text-base md:text-lg max-w-xl mx-auto leading-relaxed">
        Tiga langkah mudah menuju kesehatan mental yang lebih baik bersama psikolog berlisensi kami.
      </p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <article
        v-for="step in steps"
        :key="step.number"
        class="relative rounded-3xl bg-[var(--surface)] border border-[var(--line)] p-8 flex flex-col items-center text-center gap-5 overflow-hidden hover:border-[var(--accent)]/40 transition-colors duration-300"
      >
        <!-- Nomor besar di background -->
        <span
          class="absolute top-4 right-6 font-display text-7xl font-bold text-[var(--line)] select-none pointer-events-none leading-none"
          aria-hidden="true"
        >
          {{ step.number }}
        </span>

        <!-- Icon -->
        <div
          class="relative z-10 w-16 h-16 rounded-2xl flex items-center justify-center"
          :class="step.iconBg"
        >
          <component :is="step.icon" class="w-8 h-8" :class="step.iconColor" aria-hidden="true" />
        </div>

        <!-- Teks -->
        <div class="relative z-10">
          <h3 class="font-display text-xl font-semibold text-[var(--ink)] mb-3">{{ step.title }}</h3>
          <p class="text-[var(--copy)] text-sm leading-relaxed">{{ step.description }}</p>
        </div>

        <!-- Ilustrasi -->
        <div class="relative z-10 w-full rounded-2xl overflow-hidden bg-[var(--line)]/20 mt-2 min-h-[140px] flex items-center justify-center">
          <component :is="step.illustration" />
        </div>
      </article>
    </div>
  </section>
</template>
