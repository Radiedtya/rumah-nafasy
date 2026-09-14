<script setup lang="ts">
const steps = [
  {
    title: 'Temukan Psikolog Anda',
    description: 'Ceritakan kondisi Anda dan kami cocokkan dengan psikolog berlisensi yang paling sesuai. Semua tersertifikasi SIP dan HIMPsi.',
  },
  {
    title: 'Atur Jadwal Fleksibel',
    description: 'Pilih waktu yang nyaman. Reschedule mudah tanpa biaya tambahan, tersedia 7 hari seminggu termasuk malam hari.',
  },
  {
    title: 'Mulai Sesi Konsultasi',
    description: 'Konsultasi via video call atau chat teks dari mana saja. Privasi terlindungi, tidak ada rekaman, sepenuhnya rahasia.',
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

    <div class="grid grid-cols-1 gap-12 md:grid-cols-3">
      <div
        v-for="(step, stepIndex) in steps"
        :key="step.title"
        class="step-item relative"
      >
        <article class="step-card relative z-[1] h-full rounded-3xl bg-[var(--surface)] border border-[var(--line)] p-8 flex flex-col items-center text-center gap-5 overflow-visible hover:border-[var(--accent)]/40 transition-colors duration-300">

        <!-- Teks -->
        <div class="relative z-10">
          <h3 class="font-display text-xl font-semibold text-[var(--ink)] mb-3">{{ step.title }}</h3>
          <p class="text-[var(--copy)] text-sm leading-relaxed">{{ step.description }}</p>
        </div>

        <!-- Ganti src placeholder ini dengan ilustrasi asli berukuran 640 x 280 px. -->
        <div class="relative z-10 mt-2 aspect-[640/280] w-full overflow-hidden rounded-2xl bg-[var(--line)]/20">
          <img
            src="https://placehold.co/640x280/e8edf2/697586?text=Ilustrasi+640+x+280"
            alt="Placeholder ilustrasi langkah konsultasi"
            class="block h-full w-full object-cover"
          />
        </div>
        </article>
        <span
          v-if="stepIndex < steps.length - 1"
          class="step-connector"
          :class="{ 'step-connector-top': stepIndex === 1 }"
          aria-hidden="true"
        />
      </div>
    </div>
  </section>
</template>

<style scoped>
.step-connector {
  position: absolute;
  z-index: 0;
  right: -48px;
  bottom: 56px;
  width: 48px;
  height: 2px;
  background: var(--muted);
  pointer-events: none;
}

.step-connector::before,
.step-connector::after {
  position: absolute;
  top: 50%;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: var(--muted);
  content: '';
  transform: translateY(-50%);
}

.step-connector::before {
  left: -17px;
}

.step-connector::after {
  right: -17px;
}

.step-connector-top {
  top: 56px;
  bottom: auto;
}

@media (max-width: 767px) {
  .step-connector {
    display: none;
  }
}
</style>
