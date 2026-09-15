<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '../../lib/api'
import {
  ClockIcon,
  PlusIcon,
  TrashIcon,
  CheckCircleIcon,
  XMarkIcon,
} from '@heroicons/vue/20/solid'

const schedules = ref<any[]>([])
const loading = ref(true)
const modalOpen = ref(false)
const isSubmitting = ref(false)
const error = ref('')
const message = ref('')

const dayNames = [
  'Minggu',
  'Senin',
  'Selasa',
  'Rabu',
  'Kamis',
  'Jumat',
  'Sabtu',
]

const form = ref({
  day_of_week: 1,
  start_time: '09:00',
  end_time: '12:00',
})

async function fetchSchedules() {
  loading.value = true
  try {
    const res = await apiFetch('psikolog/schedules')
    schedules.value = res.data || []
  } catch (e) {
    console.error('Failed fetching schedules', e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchSchedules()
})

async function toggleAvailable(sched: any) {
  try {
    await apiFetch(`psikolog/schedules/${sched.id}`, {
      method: 'PUT',
      body: JSON.stringify({
        is_available: !sched.is_available,
      }),
    })
    sched.is_available = !sched.is_available
  } catch (e) {
    console.error('Failed toggling schedule', e)
  }
}

async function addSchedule() {
  isSubmitting.value = true
  error.value = ''
  try {
    await apiFetch('psikolog/schedules', {
      method: 'POST',
      body: JSON.stringify(form.value),
    })
    modalOpen.value = false
    message.value = 'Jadwal praktek berhasil ditambahkan!'
    await fetchSchedules()
  } catch (err: any) {
    error.value = err.message || 'Gagal menambahkan jadwal'
  } finally {
    isSubmitting.value = false
  }
}

async function deleteSchedule(id: number) {
  if (!confirm('Yakin ingin menghapus jadwal ini?')) return
  try {
    await apiFetch(`psikolog/schedules/${id}`, {
      method: 'DELETE',
    })
    await fetchSchedules()
  } catch (e) {
    console.error('Failed deleting schedule', e)
  }
}
</script>

<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
      <div>
        <h2 class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
          Jadwal Praktek Mingguan
        </h2>
        <p class="text-neutral-500 text-sm mt-0.5">
          Atur hari dan jam praktek Anda agar pasien dapat mereservasi slot waktu dengan tepat.
        </p>
      </div>

      <button
        type="button"
        class="inline-flex items-center gap-1.5 px-4 py-2.5 rounded-2xl bg-rose-500 text-white text-xs font-semibold hover:bg-rose-600 transition-colors shadow-xs self-start sm:self-auto"
        @click="modalOpen = true"
      >
        <PlusIcon class="w-4 h-4" />
        Tambah Jadwal
      </button>
    </div>

    <div v-if="message" class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-700 text-xs font-semibold flex items-center gap-2">
      <CheckCircleIcon class="w-4 h-4 text-emerald-500 shrink-0" />
      {{ message }}
    </div>

    <!-- Schedules table / cards -->
    <div v-if="loading" class="space-y-3">
      <div v-for="i in 4" :key="i" class="h-20 bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 animate-pulse" />
    </div>

    <div v-else-if="schedules.length === 0" class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200 dark:border-neutral-800 p-12 text-center text-neutral-400">
      <p class="text-3xl mb-2">⏰</p>
      <p class="font-medium text-neutral-700 dark:text-neutral-200 text-sm">Belum ada jadwal praktek</p>
      <p class="text-xs text-neutral-500 mt-1">Tambahkan jadwal praktek hari dan jam Anda di atas.</p>
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="sched in schedules"
        :key="sched.id"
        class="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200/80 dark:border-neutral-800 p-4 shadow-xs flex items-center justify-between gap-4"
      >
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 dark:bg-rose-950/40 dark:text-rose-400 font-bold flex items-center justify-center text-xs shrink-0">
            {{ dayNames[sched.day_of_week] }}
          </div>
          <div>
            <h4 class="font-bold text-neutral-900 dark:text-neutral-100 text-sm">
              Hari {{ dayNames[sched.day_of_week] }}
            </h4>
            <p class="text-xs text-neutral-500 flex items-center gap-1 mt-0.5">
              <ClockIcon class="w-3.5 h-3.5 text-neutral-400" />
              Pukul {{ sched.start_time }} - {{ sched.end_time }} WIB
            </p>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Toggle switch -->
          <button
            type="button"
            class="text-xs font-semibold px-3 py-1.5 rounded-lg border transition-colors"
            :class="sched.is_available
              ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-900'
              : 'bg-neutral-100 border-neutral-200 text-neutral-500'"
            @click="toggleAvailable(sched)"
          >
            {{ sched.is_available ? 'Aktif' : 'Libur' }}
          </button>

          <!-- Delete -->
          <button
            type="button"
            class="p-2 rounded-lg text-neutral-400 hover:text-rose-600 hover:bg-rose-50 transition-colors"
            title="Hapus jadwal"
            @click="deleteSchedule(sched.id)"
          >
            <TrashIcon class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- Modal Add Schedule -->
    <div
      v-if="modalOpen"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs"
    >
      <div class="bg-white dark:bg-neutral-900 rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-neutral-200 dark:border-neutral-800 space-y-4 animate-in fade-in zoom-in-95 duration-150">
        <div class="flex items-center justify-between border-b border-neutral-100 dark:border-neutral-800 pb-3">
          <h3 class="font-display text-base font-bold text-neutral-900 dark:text-neutral-100">
            Tambah Jadwal Praktek
          </h3>
          <button type="button" class="p-1 text-neutral-400 hover:text-neutral-600" @click="modalOpen = false">
            <XMarkIcon class="w-5 h-5" />
          </button>
        </div>

        <div v-if="error" class="p-3 bg-rose-50 text-rose-700 text-xs rounded-xl">
          {{ error }}
        </div>

        <form class="space-y-4" @submit.prevent="addSchedule">
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
              Hari Praktek:
            </label>
            <select
              v-model.number="form.day_of_week"
              class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs outline-none"
            >
              <option v-for="(day, idx) in dayNames" :key="idx" :value="idx">
                {{ day }}
              </option>
            </select>
          </div>

          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
                Jam Mulai:
              </label>
              <input
                v-model="form.start_time"
                type="time"
                required
                class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs outline-none"
              />
            </div>

            <div>
              <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1 block">
                Jam Selesai:
              </label>
              <input
                v-model="form.end_time"
                type="time"
                required
                class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3 py-2 text-xs outline-none"
              />
            </div>
          </div>

          <button
            type="submit"
            class="w-full py-2.5 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 transition-colors shadow-xs"
            :disabled="isSubmitting"
          >
            {{ isSubmitting ? 'Menyimpan...' : 'Simpan Jadwal' }}
          </button>
        </form>
      </div>
    </div>
  </div>
</template>
