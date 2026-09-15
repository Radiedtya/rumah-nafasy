<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import {
  CheckCircleIcon,
  ShieldCheckIcon,
} from '@heroicons/vue/20/solid'

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
  <div class="max-w-3xl mx-auto space-y-6">
    <div>
      <h2 class="font-display text-2xl font-bold text-neutral-900 dark:text-neutral-100">
        Profil Saya
      </h2>
      <p class="text-neutral-500 text-sm mt-0.5">
        Kelola informasi akun dan status kredensial Anda di Rumah Natasy.
      </p>
    </div>

    <div v-if="message" class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-700 text-xs font-semibold flex items-center gap-2">
      <CheckCircleIcon class="w-4 h-4 text-emerald-500 shrink-0" />
      {{ message }}
    </div>

    <div v-if="error" class="p-3.5 rounded-2xl bg-rose-50 text-rose-700 text-xs font-semibold">
      {{ error }}
    </div>

    <!-- Profile card -->
    <div class="bg-white dark:bg-neutral-900 rounded-3xl border border-neutral-200/80 dark:border-neutral-800 p-6 shadow-xs space-y-6">
      <div class="flex items-center gap-4 pb-6 border-b border-neutral-100 dark:border-neutral-800">
        <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-rose-400 to-orange-400 text-white font-display text-2xl font-bold flex items-center justify-center shrink-0 shadow-sm">
          {{ name ? name.charAt(0).toUpperCase() : 'U' }}
        </div>
        <div>
          <h3 class="font-display font-bold text-neutral-900 dark:text-neutral-100 text-lg">
            {{ name || 'Nama Pengguna' }}
          </h3>
          <div class="flex items-center gap-2 mt-1">
            <span
              class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full"
              :class="auth.isPsikolog ? 'bg-rose-100 text-rose-700' : 'bg-sky-100 text-sky-700'"
            >
              {{ auth.isPsikolog ? 'Psikolog Berlisensi' : 'Pasien' }}
            </span>
            <span class="inline-flex items-center gap-1 text-[11px] text-emerald-600 font-medium">
              <ShieldCheckIcon class="w-3.5 h-3.5" />
              Terverifikasi
            </span>
          </div>
        </div>
      </div>

      <!-- Form -->
      <form class="space-y-4" @submit.prevent="handleSubmit">
        <div>
          <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
            Nama Lengkap
          </label>
          <input
            v-model="name"
            type="text"
            required
            class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs text-neutral-900 dark:text-neutral-100 outline-none focus:border-rose-500 transition-colors"
          />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
              Email
            </label>
            <input
              v-model="email"
              type="email"
              required
              class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs text-neutral-900 dark:text-neutral-100 outline-none focus:border-rose-500 transition-colors"
            />
          </div>

          <div>
            <label class="text-xs font-bold text-neutral-700 dark:text-neutral-300 mb-1.5 block">
              Nomor WhatsApp
            </label>
            <input
              v-model="phone"
              type="tel"
              required
              class="w-full bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 text-xs text-neutral-900 dark:text-neutral-100 outline-none focus:border-rose-500 transition-colors"
            />
          </div>
        </div>

        <div v-if="auth.isPsikolog && auth.user?.psikolog_profile" class="p-4 rounded-2xl bg-neutral-50 dark:bg-neutral-800/40 border border-neutral-100 dark:border-neutral-800 space-y-2 text-xs">
          <p class="font-bold text-neutral-900 dark:text-neutral-100">Kredensial Profesional Psikolog:</p>
          <div class="grid grid-cols-2 gap-2 text-neutral-600 dark:text-neutral-400 text-[11px]">
            <p>Nomor SIP: <strong class="text-neutral-900 dark:text-neutral-100">{{ auth.user.psikolog_profile.license_no }}</strong></p>
            <p>Pendidikan: <strong class="text-neutral-900 dark:text-neutral-100">{{ auth.user.psikolog_profile.education }}</strong></p>
            <p>Pengalaman: <strong class="text-neutral-900 dark:text-neutral-100">{{ auth.user.psikolog_profile.experience_years }} Tahun</strong></p>
            <p>Instansi: <strong class="text-neutral-900 dark:text-neutral-100">{{ auth.user.psikolog_profile.workplace }}</strong></p>
          </div>
        </div>

        <div class="pt-2">
          <button
            type="submit"
            class="px-5 py-2.5 rounded-xl bg-rose-500 text-white text-xs font-bold hover:bg-rose-600 active:scale-95 transition-all shadow-xs"
            :disabled="isSaving"
          >
            {{ isSaving ? 'Menyimpan...' : 'Simpan Perubahan' }}
          </button>
        </div>
      </form>
    </div>
  </div>
</template>
