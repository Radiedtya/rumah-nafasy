<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '../../lib/api'
import {
  ClockIcon,
  PlusIcon,
  TrashIcon,
  CheckCircleIcon,
} from '@heroicons/vue/24/outline'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseSkeleton from '../../components/ui/BaseSkeleton.vue'
import BaseEmpty from '../../components/ui/BaseEmpty.vue'
import BaseModal from '../../components/ui/BaseModal.vue'
import BaseSwitch from '../../components/ui/BaseSwitch.vue'

const schedules = ref<any[]>([])
const loading = ref(true)
const modalOpen = ref(false)
const isSubmitting = ref(false)
const error = ref('')
const message = ref('')

const dayNames = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']

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
  <div>
    <PageHeader
      title="Jadwal Praktek Mingguan"
      description="Atur hari dan jam praktek Anda agar pasien dapat mereservasi slot waktu dengan tepat."
    >
      <template #actions>
        <BaseButton size="sm" @click="modalOpen = true">
          <PlusIcon class="h-3.5 w-3.5" />
          Tambah Jadwal
        </BaseButton>
      </template>
    </PageHeader>

    <div
      v-if="message"
      class="mb-4 flex items-center gap-2 rounded-xl bg-emerald-500/10 px-3.5 py-2.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
    >
      <CheckCircleIcon class="h-4 w-4 shrink-0" />
      {{ message }}
    </div>

    <!-- Schedules list -->
    <div v-if="loading" class="space-y-3">
      <BaseSkeleton v-for="i in 4" :key="i" class="h-16" />
    </div>

    <BaseCard v-else-if="schedules.length === 0" :padded="false">
      <BaseEmpty
        icon="⏰"
        title="Belum ada jadwal praktek"
        description="Tambahkan jadwal praktek hari dan jam Anda."
      />
    </BaseCard>

    <div v-else class="space-y-2.5">
      <BaseCard
        v-for="sched in schedules"
        :key="sched.id"
        class="flex items-center justify-between gap-4 !p-4"
      >
        <div class="flex min-w-0 items-center gap-4">
          <div
            class="flex h-11 w-14 shrink-0 flex-col items-center justify-center rounded-lg bg-[var(--muted)]/8 text-[10px] font-semibold uppercase tracking-wide text-[var(--muted)]"
          >
            {{ dayNames[sched.day_of_week]?.slice(0, 3) }}
            <span class="mt-0.5 text-[9px] font-medium opacity-70">
              {{ sched.is_available ? 'Aktif' : 'Libur' }}
            </span>
          </div>
          <div class="min-w-0">
            <h4 class="text-sm font-semibold text-[var(--text)]">
              Hari {{ dayNames[sched.day_of_week] }}
            </h4>
            <p class="mt-0.5 flex items-center gap-1 text-[11px] tabular-nums text-[var(--muted)]">
              <ClockIcon class="h-3.5 w-3.5" />
              {{ sched.start_time }}–{{ sched.end_time }} WIB
            </p>
          </div>
        </div>

        <div class="flex shrink-0 items-center gap-3">
          <BaseSwitch :model-value="sched.is_available" @update:model-value="toggleAvailable(sched)" />
          <button
            type="button"
            class="rounded-lg p-2 text-[var(--muted)] transition-colors hover:bg-rose-500/10 hover:text-rose-600"
            title="Hapus jadwal"
            @click="deleteSchedule(sched.id)"
          >
            <TrashIcon class="h-4 w-4" />
          </button>
        </div>
      </BaseCard>
    </div>

    <!-- Modal Add Schedule -->
    <BaseModal v-model:open="modalOpen" title="Tambah Jadwal Praktek" max-width="max-w-sm">
      <form class="space-y-4" @submit.prevent="addSchedule">
        <div v-if="error" class="rounded-lg bg-rose-500/10 px-3 py-2 text-xs text-rose-600 dark:text-rose-400">
          {{ error }}
        </div>

        <div>
          <label class="field-label">Hari Praktek</label>
          <select v-model.number="form.day_of_week" class="field-input">
            <option v-for="(day, idx) in dayNames" :key="idx" :value="idx">
              {{ day }}
            </option>
          </select>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="field-label">Jam Mulai</label>
            <input v-model="form.start_time" type="time" required class="field-input" />
          </div>
          <div>
            <label class="field-label">Jam Selesai</label>
            <input v-model="form.end_time" type="time" required class="field-input" />
          </div>
        </div>

        <BaseButton type="submit" size="md" class="w-full" :disabled="isSubmitting">
          {{ isSubmitting ? 'Menyimpan…' : 'Simpan Jadwal' }}
        </BaseButton>
      </form>
    </BaseModal>
  </div>
</template>
