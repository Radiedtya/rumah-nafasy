<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { CheckCircleIcon, ShieldCheckIcon, CameraIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import PageHeader from '../../components/dashboard/PageHeader.vue'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
import BaseCard from '../../components/ui/BaseCard.vue'
import AccountMethodsCard from '../../components/dashboard/AccountMethodsCard.vue'

const auth = useAuthStore()

// ── Fields ────────────────────────────────────────────────────────────────────
const name = ref('')
const email = ref('')

// ── Phone country picker ──────────────────────────────────────────────────────
type CountryCode = 'ID' | 'CN' | 'MY'

interface Country {
  code: CountryCode
  flag: string
  name: string
  dialCode: string
  // Each format entry: [mask, regex to validate raw digits (after dial code stripped)]
  formats: { placeholder: string; regex: RegExp }[]
}

const COUNTRIES: Country[] = [
  {
    code: 'ID',
    flag: '🇮🇩',
    name: 'Indonesia',
    dialCode: '+62',
    // Indonesia: old format 8-11 digits after 0/62, new (modern) 9-13 digits
    // We keep it flexible: 8-13 local digits
    formats: [
      { placeholder: '0812-3456-7890', regex: /^0[0-9]{7,12}$/ },       // legacy 08xxx (lama)
      { placeholder: '0813-4567-89012', regex: /^0[0-9]{9,13}$/ },      // modern 12 digits
    ],
  },
  {
    code: 'CN',
    flag: '🇨🇳',
    name: 'Tiongkok',
    dialCode: '+86',
    formats: [
      { placeholder: '138 0013 8000', regex: /^1[3-9][0-9]{9}$/ },
    ],
  },
  {
    code: 'MY',
    flag: '🇲🇾',
    name: 'Malaysia',
    dialCode: '+60',
    formats: [
      { placeholder: '011-2345 6789', regex: /^0(1[0-9]-?[0-9]{7,8}|[3-9][0-9]-?[0-9]{6,8})$/ },
    ],
  },
]

const selectedCountry = ref<CountryCode>('ID')
const phoneLocal = ref('')   // raw local number as user types
const countryOpen = ref(false)

const currentCountry = computed(() => COUNTRIES.find(c => c.code === selectedCountry.value)!)

// Placeholder based on first format of selected country
const phonePlaceholder = computed(() => currentCountry.value.formats[0].placeholder)

// Compose full phone string for storage: dialCode + local digits (strip leading 0 for ID/MY)
const phoneForStorage = computed(() => {
  const digits = phoneLocal.value.replace(/\D/g, '')
  if (!digits) return ''
  const c = currentCountry.value
  // CN numbers don't start with 0, ID and MY do — strip it before prepending dial code
  const stripped = c.code === 'CN' ? digits : digits.replace(/^0/, '')
  return `${c.dialCode}${stripped}`
})

// Auto-format as user types
function formatPhone(raw: string, country: CountryCode): string {
  const digits = raw.replace(/\D/g, '')
  if (country === 'ID') {
    // 0812-3456-7890 style
    if (digits.length <= 4) return digits
    if (digits.length <= 8) return `${digits.slice(0, 4)}-${digits.slice(4)}`
    return `${digits.slice(0, 4)}-${digits.slice(4, 8)}-${digits.slice(8, 13)}`
  }
  if (country === 'CN') {
    // 138 0013 8000
    if (digits.length <= 3) return digits
    if (digits.length <= 7) return `${digits.slice(0, 3)} ${digits.slice(3)}`
    return `${digits.slice(0, 3)} ${digits.slice(3, 7)} ${digits.slice(7, 11)}`
  }
  if (country === 'MY') {
    // 011-2345 6789
    if (digits.length <= 3) return digits
    if (digits.length <= 7) return `${digits.slice(0, 3)}-${digits.slice(3)}`
    return `${digits.slice(0, 3)}-${digits.slice(3, 7)} ${digits.slice(7, 11)}`
  }
  return raw
}

function onPhoneInput(e: Event) {
  const raw = (e.target as HTMLInputElement).value
  const digits = raw.replace(/\D/g, '')
  phoneLocal.value = formatPhone(digits, selectedCountry.value)
}

function selectCountry(code: CountryCode) {
  selectedCountry.value = code
  // Re-format with new country rules
  const digits = phoneLocal.value.replace(/\D/g, '')
  phoneLocal.value = formatPhone(digits, code)
  countryOpen.value = false
}

// Parse stored phone back into local + country
function parseStoredPhone(stored: string | null | undefined) {
  if (!stored) { phoneLocal.value = ''; return }
  for (const c of COUNTRIES) {
    if (stored.startsWith(c.dialCode)) {
      selectedCountry.value = c.code
      const afterDial = stored.slice(c.dialCode.length)
      // CN stores as +861380013800 → local 13800138000 (no leading 0)
      // ID/MY stores as +628xxx → local 08xxx (add leading 0 back)
      const local = c.code === 'CN' ? afterDial : '0' + afterDial
      phoneLocal.value = formatPhone(local.replace(/\D/g, ''), c.code)
      return
    }
  }
  // Fallback: treat as Indonesia local
  selectedCountry.value = 'ID'
  phoneLocal.value = stored
}

// Phone validation
const phoneError = ref<string | null>(null)
function validatePhone(): boolean {
  const digits = phoneLocal.value.replace(/\D/g, '')
  if (!digits) { phoneError.value = null; return true } // optional

  const c = currentCountry.value
  let valid = false

  if (c.code === 'ID') {
    // Indonesia: harus dimulai 0, 10–14 digit total (lama: 10-11, modern: 12-13)
    valid = /^0[0-9]{8,12}$/.test(digits)
  } else if (c.code === 'CN') {
    // Tiongkok: 11 digit, dimulai 1[3-9]
    valid = /^1[3-9][0-9]{9}$/.test(digits)
  } else if (c.code === 'MY') {
    // Malaysia: dimulai 01x (mobile) atau 0[3-9] (landline), 9-11 digit total
    valid = /^0(1[0-9][0-9]{7,8}|[3-9][0-9]{6,8})$/.test(digits)
  }

  if (!valid) {
    phoneError.value = `Format nomor ${c.name} tidak valid`
    return false
  }
  phoneError.value = null
  return true
}

// ── Name validation ───────────────────────────────────────────────────────────
const nameError = ref<string | null>(null)
function validateName(): boolean {
  const v = name.value.trim()
  if (!v) { nameError.value = 'Nama wajib diisi'; return false }
  if (v.length < 2) { nameError.value = 'Nama minimal 2 karakter'; return false }
  if (v.length > 100) { nameError.value = 'Nama maksimal 100 karakter'; return false }
  nameError.value = null
  return true
}

// ── Avatar ────────────────────────────────────────────────────────────────────
const avatarPreview = ref<string | null>(null)
const avatarFile = ref<File | null>(null)
const avatarInput = ref<HTMLInputElement | null>(null)
const isDragging = ref(false)
const avatarError = ref<string | null>(null)
const isUploadingAvatar = ref(false)
const isDeletingAvatar = ref(false)
const showDeleteConfirm = ref(false)

const currentAvatar = computed(() => avatarPreview.value || auth.user?.avatar || null)
const userInitials = computed(() => {
  const n = name.value.trim()
  if (!n) return '?'
  return n.split(/\s+/).slice(0, 2).map(w => w[0].toUpperCase()).join('')
})

function onAvatarFileChange(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (file) processAvatarFile(file)
}

function onDrop(e: DragEvent) {
  isDragging.value = false
  const file = e.dataTransfer?.files?.[0]
  if (file) processAvatarFile(file)
}

function processAvatarFile(file: File) {
  avatarError.value = null
  if (!file.type.startsWith('image/')) {
    avatarError.value = 'File harus berupa gambar (JPG, PNG, WebP)'
    return
  }
  if (file.size > 2 * 1024 * 1024) {
    avatarError.value = 'Ukuran gambar maksimal 2 MB'
    return
  }
  avatarFile.value = file
  avatarPreview.value = URL.createObjectURL(file)
}

function cancelAvatarChange() {
  avatarFile.value = null
  avatarPreview.value = null
  if (avatarInput.value) avatarInput.value.value = ''
}

async function quickSaveAvatar() {
  if (!avatarFile.value) return
  isUploadingAvatar.value = true
  avatarError.value = null
  try {
    await auth.uploadAvatar(avatarFile.value)
    avatarFile.value = null
    avatarPreview.value = null
    if (avatarInput.value) avatarInput.value.value = ''
    message.value = 'Foto profil berhasil diperbarui!'
  } catch (err: any) {
    avatarError.value = err.message || 'Gagal mengunggah foto'
  } finally {
    isUploadingAvatar.value = false
  }
}

async function confirmDeleteAvatar() {
  isDeletingAvatar.value = true
  avatarError.value = null
  try {
    await auth.deleteAvatar()
    showDeleteConfirm.value = false
    avatarPreview.value = null
    avatarFile.value = null
    message.value = 'Foto profil berhasil dihapus.'
  } catch (err: any) {
    avatarError.value = err.message || 'Gagal menghapus foto'
  } finally {
    isDeletingAvatar.value = false
  }
}

// ── Save profile ──────────────────────────────────────────────────────────────
const isSaving = ref(false)
const message = ref('')
const error = ref('')

onMounted(async () => {
  const u = auth.user ?? await auth.fetchMe()
  if (u) {
    name.value = u.name || ''
    email.value = u.email || ''
    parseStoredPhone(u.phone)
  }
})

async function handleSubmit() {
  const nameOk = validateName()
  const phoneOk = validatePhone()
  if (!nameOk || !phoneOk) return

  isSaving.value = true
  message.value = ''
  error.value = ''

  try {
    // Satu request: name + phone + avatar (jika ada) sekaligus
    await auth.updateProfile({
      name: name.value.trim(),
      phone: phoneForStorage.value || undefined,
      avatar: avatarFile.value ?? undefined,
    })

    // Bersihkan state preview setelah berhasil
    avatarFile.value = null
    avatarPreview.value = null
    if (avatarInput.value) avatarInput.value.value = ''

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

    <!-- Toast success -->
    <transition name="fade">
      <div
        v-if="message"
        class="mb-4 flex items-center gap-2 rounded-xl bg-emerald-500/10 px-3.5 py-2.5 text-xs font-medium text-emerald-600 dark:text-emerald-400"
      >
        <CheckCircleIcon class="h-4 w-4 shrink-0" />
        {{ message }}
        <button type="button" class="ml-auto" @click="message = ''"><XMarkIcon class="h-3.5 w-3.5" /></button>
      </div>
    </transition>

    <div v-if="error" class="mb-4 rounded-xl bg-rose-500/10 px-3.5 py-2.5 text-xs text-rose-600 dark:text-rose-400">
      {{ error }}
    </div>

    <!-- Delete avatar confirm modal -->
    <transition name="fade">
      <div v-if="showDeleteConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div class="w-full max-w-sm rounded-2xl bg-[var(--surface)] p-6 shadow-2xl border border-[var(--line)]">
          <h3 class="text-sm font-semibold text-[var(--text)] mb-2">Hapus foto profil?</h3>
          <p class="text-xs text-[var(--muted)] mb-5">Foto profil Anda akan dihapus dan tidak dapat dikembalikan.</p>
          <div class="flex gap-3">
            <button
              type="button"
              class="flex-1 h-9 rounded-lg border border-[var(--line)] text-xs font-medium text-[var(--text)] hover:bg-[var(--muted)]/10 transition-colors"
              @click="showDeleteConfirm = false"
            >Batal</button>
            <button
              type="button"
              class="flex-1 h-9 rounded-lg bg-rose-500 text-white text-xs font-medium hover:bg-rose-600 transition-colors disabled:opacity-50"
              :disabled="isDeletingAvatar"
              @click="confirmDeleteAvatar"
            >{{ isDeletingAvatar ? 'Menghapus...' : 'Hapus' }}</button>
          </div>
        </div>
      </div>
    </transition>

    <BaseCard class="space-y-6">
      <!-- Avatar section -->
      <div class="flex flex-col sm:flex-row sm:items-start gap-5 border-b border-[var(--line)] pb-6">
        <!-- Avatar display + upload zone -->
        <div class="shrink-0">
          <div
            class="avatar-drop-zone group"
            :class="{ 'is-dragging': isDragging }"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="onDrop"
            @click="avatarInput?.click()"
            role="button"
            tabindex="0"
            aria-label="Klik atau seret foto untuk mengganti avatar"
            @keydown.enter="avatarInput?.click()"
          >
            <!-- Current avatar or initials -->
            <div class="avatar-image">
              <img v-if="currentAvatar" :src="currentAvatar" :alt="name" class="h-full w-full object-cover" />
              <span v-else class="avatar-initials">{{ userInitials }}</span>
            </div>
            <!-- Hover overlay -->
            <div class="avatar-overlay">
              <CameraIcon class="h-6 w-6 text-white" />
            </div>
          </div>
          <input
            ref="avatarInput"
            type="file"
            accept="image/jpeg,image/png,image/webp"
            class="hidden"
            @change="onAvatarFileChange"
          />
        </div>

        <!-- Avatar info & actions -->
        <div class="flex-1 min-w-0">
          <div class="flex items-start justify-between gap-3">
            <div>
              <h3 class="text-base font-semibold text-[var(--text)]">
                {{ name || 'Nama Pengguna' }}
              </h3>
              <div class="mt-1.5 flex flex-wrap items-center gap-2">
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

          <!-- Avatar change pending -->
          <div v-if="avatarFile" class="mt-3 flex items-center gap-2 flex-wrap">
            <span class="text-xs text-[var(--muted)]">Foto baru dipilih: <strong class="text-[var(--text)]">{{ avatarFile.name }}</strong></span>
            <button type="button" class="text-xs text-[var(--accent)] hover:underline" @click="quickSaveAvatar" :disabled="isUploadingAvatar">
              {{ isUploadingAvatar ? 'Mengunggah...' : 'Simpan foto sekarang' }}
            </button>
            <button type="button" class="text-xs text-[var(--muted)] hover:text-rose-500" @click="cancelAvatarChange">Batal</button>
          </div>

          <!-- Upload hint -->
          <p v-else class="mt-2 text-xs text-[var(--muted)]">
            Klik foto atau seret gambar ke sini. JPG, PNG, atau WebP, maks. 2 MB.
          </p>

          <!-- Delete avatar button -->
          <button
            v-if="auth.user?.avatar && !avatarFile"
            type="button"
            class="mt-3 inline-flex items-center gap-1.5 text-xs text-rose-500 hover:text-rose-600 transition-colors"
            @click="showDeleteConfirm = true"
          >
            <TrashIcon class="h-3.5 w-3.5" />
            Hapus foto profil
          </button>

          <!-- Avatar error -->
          <p v-if="avatarError" class="mt-2 text-xs text-rose-500">{{ avatarError }}</p>
        </div>
      </div>

      <!-- Form -->
      <form class="space-y-5" @submit.prevent="handleSubmit" novalidate>

        <!-- Nama -->
        <div>
          <label class="field-label" for="prof-name">Nama Lengkap</label>
          <input
            id="prof-name"
            v-model="name"
            type="text"
            autocomplete="name"
            class="field-input"
            :class="{ 'field-input--error': nameError }"
            @blur="validateName()"
          />
          <p v-if="nameError" class="field-error">{{ nameError }}</p>
        </div>

        <!-- Email (read-only) -->
        <div>
          <label class="field-label" for="prof-email">Email</label>
          <input
            id="prof-email"
            :value="email"
            type="email"
            readonly
            disabled
            class="field-input field-input--disabled"
            title="Email tidak dapat diubah"
          />
          <p class="mt-1 text-[11px] text-[var(--muted)]">Email tidak dapat diubah langsung. Hubungi dukungan jika diperlukan.</p>
        </div>

        <!-- Phone dengan country picker -->
        <div>
          <label class="field-label" for="prof-phone">Nomor Telepon <span class="font-normal text-[var(--muted)]">(opsional)</span></label>
          <div class="phone-field" :class="{ 'phone-field--error': phoneError }">
            <!-- Country selector -->
            <div class="relative">
              <button
                type="button"
                class="country-btn"
                :aria-label="`Kode negara: ${currentCountry.name} ${currentCountry.dialCode}`"
                @click="countryOpen = !countryOpen"
              >
                <span class="text-lg leading-none">{{ currentCountry.flag }}</span>
                <span class="text-xs font-medium text-[var(--text)]">{{ currentCountry.dialCode }}</span>
                <svg class="h-3.5 w-3.5 text-[var(--muted)] transition-transform" :class="{ 'rotate-180': countryOpen }" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                </svg>
              </button>

              <!-- Dropdown -->
              <transition name="dropdown">
                <div v-if="countryOpen" class="country-dropdown">
                  <button
                    v-for="c in COUNTRIES"
                    :key="c.code"
                    type="button"
                    class="country-option"
                    :class="{ 'country-option--active': selectedCountry === c.code }"
                    @click="selectCountry(c.code)"
                  >
                    <span class="text-lg leading-none">{{ c.flag }}</span>
                    <span class="flex-1 text-left text-xs">{{ c.name }}</span>
                    <span class="text-xs text-[var(--muted)]">{{ c.dialCode }}</span>
                  </button>
                </div>
              </transition>
            </div>

            <!-- Divider -->
            <div class="phone-divider"></div>

            <!-- Input -->
            <input
              id="prof-phone"
              :value="phoneLocal"
              type="tel"
              :placeholder="phonePlaceholder"
              class="phone-input"
              @input="onPhoneInput"
              @blur="validatePhone()"
            />
          </div>
          <p v-if="phoneError" class="field-error">{{ phoneError }}</p>
          <p v-else-if="phoneForStorage" class="mt-1 text-[11px] text-[var(--muted)]">
            Akan disimpan sebagai: <code class="font-mono">{{ phoneForStorage }}</code>
          </p>
          <p v-else class="mt-1 text-[11px] text-[var(--muted)]">
            Hanya tersedia untuk Indonesia 🇮🇩, Tiongkok 🇨🇳, dan Malaysia 🇲🇾.
          </p>
        </div>

        <!-- Psikolog credentials (read-only) -->
        <div
          v-if="auth.isPsikolog && auth.user?.psikolog_profile"
          class="rounded-xl border border-[var(--line)] bg-[var(--muted)]/5 p-4"
        >
          <p class="text-xs font-semibold text-[var(--text)]">Kredensial Profesional Psikolog</p>
          <div class="mt-2 grid grid-cols-2 gap-2 text-[11px] text-[var(--muted)]">
            <p>Nomor SIP: <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.license_no }}</strong></p>
            <p>Pendidikan: <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.education }}</strong></p>
            <p>Pengalaman: <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.experience_years }} Tahun</strong></p>
            <p>Instansi: <strong class="font-medium text-[var(--text)]">{{ auth.user.psikolog_profile.workplace }}</strong></p>
          </div>
        </div>

        <div class="pt-1">
          <BaseButton type="submit" size="md" :disabled="isSaving">
            {{ isSaving ? 'Menyimpan…' : 'Simpan Perubahan' }}
          </BaseButton>
        </div>
      </form>
    </BaseCard>

    <!-- Metode Masuk: Google & password -->
    <AccountMethodsCard class="mt-5" />
  </div>
</template>

<style scoped>
/* ── Field ───────────────────────────────────────────────────────────── */
.field-label {
  display: block;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text);
  margin-bottom: 6px;
}
.field-input {
  width: 100%;
  height: 42px;
  padding: 0 13px;
  border: 1px solid var(--line);
  border-radius: 10px;
  outline: none;
  background: var(--surface);
  color: var(--text);
  font: inherit;
  font-size: 13.5px;
  box-sizing: border-box;
  transition: border-color 160ms, box-shadow 160ms;
}
.field-input::placeholder { color: var(--muted); opacity: 0.6; }
.field-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent); }
.field-input--error { border-color: #ef4444; }
.field-input--disabled { opacity: 0.55; cursor: not-allowed; background: var(--muted)/5; }
.field-error { margin-top: 4px; font-size: 11.5px; color: #ef4444; }

/* ── Avatar drop zone ────────────────────────────────────────────────── */
.avatar-drop-zone {
  position: relative;
  width: 88px;
  height: 88px;
  border-radius: 50%;
  cursor: pointer;
  overflow: hidden;
  flex-shrink: 0;
  transition: box-shadow 160ms;
}
.avatar-drop-zone:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
.avatar-drop-zone.is-dragging { box-shadow: 0 0 0 3px var(--accent); }
.avatar-image {
  width: 100%;
  height: 100%;
  background: color-mix(in srgb, var(--accent) 12%, transparent);
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  ring: 1px solid color-mix(in srgb, var(--accent) 15%, transparent);
}
.avatar-initials {
  font-size: 28px;
  font-weight: 700;
  color: var(--accent);
  line-height: 1;
}
.avatar-overlay {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  opacity: 0;
  transition: opacity 160ms;
}
.avatar-drop-zone:hover .avatar-overlay,
.avatar-drop-zone.is-dragging .avatar-overlay {
  opacity: 1;
}

/* ── Phone field ─────────────────────────────────────────────────────── */
.phone-field {
  display: flex;
  align-items: center;
  border: 1px solid var(--line);
  border-radius: 10px;
  background: var(--surface);
  overflow: visible;
  transition: border-color 160ms, box-shadow 160ms;
  position: relative;
}
.phone-field:focus-within {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
}
.phone-field--error { border-color: #ef4444; }
.phone-field--error:focus-within { box-shadow: 0 0 0 3px rgba(239,68,68,0.15); }

.country-btn {
  display: flex;
  align-items: center;
  gap: 5px;
  padding: 0 10px 0 12px;
  height: 42px;
  background: none;
  border: 0;
  cursor: pointer;
  white-space: nowrap;
  flex-shrink: 0;
}
.country-btn:hover { background: color-mix(in srgb, var(--accent) 6%, transparent); }

.phone-divider {
  width: 1px;
  height: 22px;
  background: var(--line);
  flex-shrink: 0;
}

.phone-input {
  flex: 1;
  height: 42px;
  padding: 0 13px;
  border: 0;
  outline: none;
  background: transparent;
  color: var(--text);
  font: inherit;
  font-size: 13.5px;
  min-width: 0;
}
.phone-input::placeholder { color: var(--muted); opacity: 0.6; }

.country-dropdown {
  position: absolute;
  top: calc(100% + 4px);
  left: 0;
  min-width: 220px;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 12px;
  box-shadow: 0 8px 24px rgba(0,0,0,0.12);
  z-index: 50;
  overflow: hidden;
  padding: 4px;
}
.country-option {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 8px 10px;
  border: 0;
  border-radius: 8px;
  background: none;
  cursor: pointer;
  transition: background 120ms;
  color: var(--text);
  font: inherit;
}
.country-option:hover { background: color-mix(in srgb, var(--accent) 8%, transparent); }
.country-option--active { background: color-mix(in srgb, var(--accent) 12%, transparent); font-weight: 600; }

/* ── Transitions ─────────────────────────────────────────────────────── */
.fade-enter-active, .fade-leave-active { transition: opacity 200ms; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.dropdown-enter-active, .dropdown-leave-active { transition: opacity 150ms, transform 150ms; }
.dropdown-enter-from, .dropdown-leave-to { opacity: 0; transform: translateY(-6px); }
</style>
