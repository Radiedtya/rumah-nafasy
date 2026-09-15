<template>
  <section id="kategori" aria-labelledby="kategori-heading" class="category-section">
    <div class="w-full max-w-[1200px] mx-auto">
    <div class="category-heading">
      <h2 id="kategori-heading">Temukan dukungan untuk setiap kebutuhan.</h2>

      <div class="category-tabs" role="tablist" aria-label="Filter kategori layanan">
        <button
          v-for="tab in tabs"
          :key="tab"
          type="button"
          role="tab"
          :aria-selected="activeTab === tab"
          :class="{ active: activeTab === tab }"
          @click="activeTab = tab"
        >
          {{ tab }}
        </button>
      </div>
    </div>

    <div class="category-grid">
      <a
        v-for="item in filteredCategories"
        :key="item.title"
        href="#"
        class="category-card"
        :aria-label="`Konsultasi ${item.title}, ${item.price}`"
      >
        <div class="category-copy">
          <h3>{{ item.title }}</h3>
          <p>{{ item.price }}</p>
        </div>
        <img
          :src="item.image"
          :alt="`Placeholder ilustrasi ${item.title}`"
          class="category-image"
          :class="{ 'category-image-depression': item.title === 'Depresi' }"
        />
        <ArrowRightIcon class="category-arrow" aria-hidden="true" />
      </a>
    </div>

    <a href="#" class="category-cta">
      Lihat semua {{ totalCount }} layanan
      <ArrowRightIcon aria-hidden="true" />
    </a>
    </div>
  </section>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { ArrowRightIcon } from '@heroicons/vue/24/outline'

const tabs = ['Populer', 'Kecemasan', 'Hubungan', 'Diri sendiri', 'Kondisi klinis']
const activeTab = ref('Populer')

const allCategories = [
  { title: 'Kecemasan & Panik', price: 'Mulai Rp 120.000/sesi', tag: 'Kecemasan', image: '/images/category/panik.png' },
  { title: 'Depresi', price: 'Mulai Rp 130.000/sesi', tag: 'Kondisi klinis', image: '/images/category/depresibaru.png' },
  { title: 'Trauma & PTSD', price: 'Mulai Rp 150.000/sesi', tag: 'Kondisi klinis', image: '/images/category/ptsd.png' },
  { title: 'Hubungan & Pasangan', price: 'Mulai Rp 140.000/sesi', tag: 'Hubungan', image: 'https://placehold.co/800x420/f7f1f5/687384?text=Ilustrasi+Hubungan' },
  { title: 'Masalah Keluarga', price: 'Mulai Rp 130.000/sesi', tag: 'Hubungan', image: 'https://placehold.co/800x420/f1f6f2/687384?text=Ilustrasi+Keluarga' },
  { title: 'Self-Esteem & Kepercayaan Diri', price: 'Mulai Rp 110.000/sesi', tag: 'Diri sendiri', image: 'https://placehold.co/800x420/f3f4ed/687384?text=Ilustrasi+Kepercayaan+Diri' },
  { title: 'Burnout & Stres Kerja', price: 'Mulai Rp 120.000/sesi', tag: 'Populer', image: '/images/category/stress.png' },
  { title: 'Grief & Kehilangan', price: 'Mulai Rp 130.000/sesi', tag: 'Kondisi klinis', image: 'https://placehold.co/800x420/f1f3f5/687384?text=Ilustrasi+Kehilangan' },
  { title: 'Masalah Tidur', price: 'Mulai Rp 110.000/sesi', tag: 'Populer', image: '/images/category/kurangtidur.png' },
  { title: 'Fobia', price: 'Mulai Rp 120.000/sesi', tag: 'Kecemasan', image: 'https://placehold.co/800x420/f7f6e8/687384?text=Ilustrasi+Fobia' },
  { title: 'Kesehatan Mental Remaja', price: 'Mulai Rp 120.000/sesi', tag: 'Populer', image: '/images/category/mentalremaja.png' },
  { title: 'Pengembangan Diri', price: 'Mulai Rp 100.000/sesi', tag: 'Diri sendiri', image: 'https://placehold.co/800x420/f7f1f8/687384?text=Ilustrasi+Pengembangan+Diri' },
]

const filteredCategories = computed(() => {
  if (activeTab.value === 'Populer') return allCategories.filter((c) => c.tag === 'Populer').concat(allCategories.filter((c) => c.tag !== 'Populer')).slice(0, 6)
  return allCategories.filter((c) => c.tag === activeTab.value)
})

const totalCount = allCategories.length
</script>

<style scoped>
.category-section {
  padding: 92px 0 96px;
  color: var(--text);
}

.category-heading {
  text-align: center;
}

.category-heading h2 {
  max-width: 760px;
  margin: 0 auto;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: clamp(2rem, 4vw, 3rem);
  font-weight: 700;
  letter-spacing: -0.065em;
  line-height: 1.04;
}

.category-tabs {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 5px;
  margin-top: 34px;
}

.category-tabs button {
  min-height: 43px;
  padding: 0 16px;
  border: 1px solid var(--line);
  border-radius: 999px;
  background: transparent;
  color: var(--text);
  font: inherit;
  font-size: 14px;
  cursor: pointer;
  transition: border-color 180ms ease, background-color 180ms ease, color 180ms ease;
}

.category-tabs button:hover,
.category-tabs button.active {
  border-color: #9b72ff;
  background: #f1eaff;
  color: #7140dc;
}

.category-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-top: 36px;
}

.category-card {
  position: relative;
  display: flex;
  min-height: 178px;
  align-items: center;
  overflow: hidden;
  padding: 26px 50% 26px 20px;
  border-radius: 8px;
  background: color-mix(in srgb, var(--line) 24%, var(--surface));
  color: var(--ink);
  text-decoration: none;
  transition: background-color 180ms ease, transform 180ms ease;
}

.category-card:hover {
  background: color-mix(in srgb, var(--line) 34%, var(--surface));
  transform: translateY(-2px);
}

.category-copy {
  position: relative;
  z-index: 1;
}

.category-copy h3 {
  margin: 0;
  color: var(--ink);
  font-family: var(--font-display);
  font-size: 20px;
  font-weight: 700;
  letter-spacing: -0.045em;
  line-height: 1.08;
}

.category-copy p {
  margin: 8px 0 0;
  color: var(--copy);
  font-size: 15px;
  line-height: 1.25;
}

.category-image {
  position: absolute;
  right: 24px;
  bottom: 0;
  width: 53%;
  height: 100%;
  object-fit: contain;
  object-position: right bottom;
  pointer-events: none;
}

.category-image-depression {
  transform: scale(1.35);
  transform-origin: right bottom;
}

.category-arrow {
  position: absolute;
  z-index: 2;
  top: 50%;
  right: 16px;
  width: 21px;
  height: 21px;
  color: var(--muted);
  transform: translateY(-50%);
  transition: color 180ms ease, transform 180ms ease;
}

.category-card:hover .category-arrow {
  color: var(--ink);
  transform: translate(2px, -50%);
}

.category-cta {
  display: flex;
  width: fit-content;
  align-items: center;
  gap: 8px;
  margin: 48px auto 0;
  color: #7140dc;
  font-size: 16px;
  font-weight: 700;
  text-decoration: none;
}

.category-cta svg {
  width: 21px;
  height: 21px;
  transition: transform 180ms ease;
}

.category-cta:hover svg {
  transform: translateX(3px);
}

@media (max-width: 900px) {
  .category-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 640px) {
  .category-section {
    padding: 68px 0 76px;
  }

  .category-heading h2 {
    font-size: 2rem;
  }

  .category-tabs {
    justify-content: flex-start;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding-bottom: 4px;
    scrollbar-width: none;
  }

  .category-tabs::-webkit-scrollbar {
    display: none;
  }

  .category-tabs button {
    flex: 0 0 auto;
  }

  .category-grid {
    grid-template-columns: 1fr;
    gap: 12px;
  }

  .category-card {
    min-height: 156px;
    padding-left: 18px;
  }
}
</style>
