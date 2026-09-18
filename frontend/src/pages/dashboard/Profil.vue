<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { CheckCircleIcon, ShieldCheckIcon } from '@heroicons/vue/24/outline'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import BaseAvatar from '../../components/ui/BaseAvatar.vue'

const auth = useAuthStore()

const name = ref('')
const phone = ref('')
const email = ref('')

const isSaving = ref(false)
const message = ref('')
const error = ref('')

onMounted(async () => {
  if (auth.user) {
    name.value = auth.user.name || ''
    phone.value = auth.user.phone || ''
    email.value = auth.user.email || ''
  } else {
    const user = await auth.fetchMe()
    if (user) {
      name.value = user.name || ''
      phone.value = user.phone || ''
      email.value = user.email || ''
    }
  }
})

async function handleSubmit() {
  isSaving.value = true
  message.value = ''
  error.value = ''

  try {
    await auth.updateProfile({
      name: name.value,
      phone: phone.value,
      email: email.value,
    })
    message.value = 'Profil berhasil diperbarui!'
  } catch (err: any) {
    error.value = err.message || 'Gagal memperbarui profil'
  } finally {
    isSaving.value = false
  }
}
</script>

<template>
  <div class="mx-auto max-w-3xl">
    <PageHeader
      title="Profil Saya"
      description="Kelola informasi akun dan status kredensial Anda di Rumah Natasy."
    />

    <div
      v-if="message"
      class="mb-4 flex items-center gap-2 rounded-xl bg-emerald-500/10 px-3.5 py-2.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
    >
      <CheckCircleIcon class="h-4 w-4 shrink-0" />
      {{ message }}
    </div>

    <div v-if="error" class="mb-4 rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <!-- Profile card -->
    <BaseCard class="space-y-6">
      <div class="flex items-center gap-4 border-b border-[var(--line)] pb-5">
        <BaseAvatar :name="name || 'U'" size="xl" />
        <div>
          <h3 class="text-base font-semibold text-[var(--text)]">
            {{ name || 'Nama Pengguna' }}
          </h3>
          <div class="mt-1.5 flex items-center gap-2">
            <BaseBadge :tone="auth.isPsikolog ? 'accent' : 'info'">
              {{ auth.isPsikolog ? 'Psikolog Berlisensi' : 'Pasien' }}
            </BaseBadge>
            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 dark:text-emerald-400">
              <ShieldCheckIcon class="h-3.5 w-3.5" />
              Terverifikasi
            </span>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div>
          <label class="field-label">Nama Lengkap</label>
          <input v-model="name" type="text" required class="field-input" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <label class="field-label">Email</label>
            <input v-model="email" type="email" required class="field-input" />
          </div>
          <div>
            <label class="field-label">Nomor WhatsApp</label>
            <input v-model="phone" type="tel" required class="field-input" />
          </div>
        </div>

        <div
          v-if="auth.isPsikolog && auth.user?.psikolog_profile"
          class="rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4"
        >
          <p class="text-xs font-semibold text-[var(--text)]">Kredensial Profesional Psikolog</p>
          <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-[var(--muted)]">
            <p>
              Nomor SIP:
              <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.license_no }}</strong>
            </p>
            <p>
              Pendidikan:
              <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.education }}</strong>
            </p>
            <p>
              Pengalaman:
              <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.experience_years }} Tahun</strong>
            </p>
            <p>
              Instansi:
              <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.workplace }}</strong>
            </p>
          </div>
        </div>

        <div class="pt-1">
          <BaseButton type="submit" size="md" :disabled="isSaving">
            {{ isSaving ? 'Menyimpan…' : 'Simpan Perubahan' }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>
  </div>
</template>
