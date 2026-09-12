<script setup lang="ts">
import {
  CurrencyDollarIcon,
  HeartIcon,
  HandThumbUpIcon,
} from '@heroicons/vue/24/outline'

const statistics = [
  {
    title: 'Lebih dari $50 juta dihemat pasien',
    description: 'Tanpa biaya tambahan, tanpa tagihan tak terduga, tanpa masalah.',
    icon: CurrencyDollarIcon,
  },
  {
    title: '95% kepuasan pasien',
    description: 'Perawatan nyata tanpa birokrasi. Lebih dari 1 juta pasien setuju.',
    icon: HandThumbUpIcon,
  },
  {
    title: '10 ribu+ penyedia bersertifikat',
    description: 'Apa pun kebutuhan Anda, ada dokter yang siap membantu.',
    icon: HeartIcon,
  },
]
</script>

<template>
  <section class="stats-bar" aria-label="Statistik OurRitual">
    <article v-for="statistic in statistics" :key="statistic.title" class="statistic-item">
      <div class="statistic-title">
        <component :is="statistic.icon" aria-hidden="true" />
        <strong>{{ statistic.title }}</strong>
      </div>
      <p>{{ statistic.description }}</p>
    </article>
  </section>
</template>

<style scoped>
.stats-bar {
  display: grid;
  width: 100vw;
  min-height: 203px;
  margin-left: calc((100% - 100vw) / 2);
  grid-template-columns: repeat(3, minmax(0, 1fr));
  align-items: center;
  border-top: 1px solid var(--line);
  background: transparent;
  color: var(--text);
}

.statistic-item {
  display: flex;
  min-width: 0;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 24px 28px;
  text-align: center;
}

.statistic-title {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 20px;
  line-height: 1.25;
}

.statistic-title svg {
  width: 22px;
  height: 22px;
  flex-shrink: 0;
  stroke-width: 2;
}

.statistic-title strong {
  font-weight: 700;
}

.statistic-item p {
  margin: 12px 0 0;
  color: var(--muted);
  font-size: 17px;
  line-height: 1.4;
}

:global(:root[data-theme='dark']) .stats-bar {
  background: transparent;
}

@media (max-width: 760px) {
  .stats-bar {
    width: 100%;
    margin-left: 0;
    grid-template-columns: 1fr;
  }

  .statistic-item {
    padding: 26px 16px;
  }

  .statistic-title {
    gap: 6px;
    font-size: 15px;
  }

  .statistic-title svg {
    width: 18px;
    height: 18px;
  }

  .statistic-item p {
    margin-top: 8px;
    font-size: 13px;
  }
}
</style>
