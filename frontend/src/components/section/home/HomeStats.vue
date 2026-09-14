<template>
  <section class="stats-section w-screen border-y border-[var(--line)]" aria-label="Statistik Rumah Natasy">
    <div class="mx-auto grid w-full max-w-[1200px] grid-cols-1 divide-y divide-[var(--line)] sm:grid-cols-3 sm:divide-x sm:divide-y-0">

      <!-- Item 1 -->
      <div class="flex flex-col items-center px-8 py-10 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
          <CurrencyDollarIcon class="w-5 h-5 text-[var(--text)] shrink-0" aria-hidden="true" />
          <strong class="text-lg font-semibold tracking-tight text-[var(--text)]">Rp 2M+ Dihemat</strong>
        </div>
        <p class="text-[var(--muted)] text-sm leading-normal max-w-[200px]">
          Klien kami hemat rata-rata 60% dibanding klinik konvensional.
        </p>
      </div>

      <!-- Item 2 -->
      <div class="flex flex-col items-center px-8 py-10 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
          <HandThumbUpIcon class="w-5 h-5 text-[var(--text)] shrink-0" aria-hidden="true" />
          <strong class="text-lg font-semibold tracking-tight text-[var(--text)]">97% Kepuasan</strong>
        </div>
        <p class="text-[var(--muted)] text-sm leading-normal max-w-[200px]">
          Lebih dari 10.000 sesi sukses dengan rating rata-rata 4.9/5.
        </p>
      </div>

      <!-- Item 3 -->
      <div class="flex flex-col items-center px-8 py-10 text-center">
        <div class="flex items-center justify-center gap-2 mb-2">
          <HeartIcon class="w-5 h-5 text-[var(--text)] shrink-0" aria-hidden="true" />
          <strong class="text-lg font-semibold tracking-tight text-[var(--text)]">150+ Psikolog</strong>
        </div>
        <p class="text-[var(--muted)] text-sm leading-normal max-w-[200px]">
          Semua tersertifikasi SIP dan anggota aktif HIMPsi Indonesia.
        </p>
      </div>

    </div>
  </section>
</template>

<script setup lang="ts">
import { CurrencyDollarIcon, HeartIcon, HandThumbUpIcon } from '@heroicons/vue/24/outline'
</script>

<style scoped>
.stats-section {
  margin-left: calc(50% - 50vw);
}
</style>
