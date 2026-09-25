<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useAuthStore } from '../../stores/auth'
import { CheckCircleIcon, ShieldCheckIcon, CameraIcon, PencilIcon, TrashIcon, XMarkIcon } from '@heroicons/vue/24/outline'
import BaseBadge from '../../components/ui/BaseBadge.vue'
import BaseButton from '../../components/ui/BaseButton.vue'
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
    flag: '/images/flags/id.svg',
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
    flag: '/images/flags/cn.svg',
    name: 'Tiongkok',
    dialCode: '+86',
    formats: [
      { placeholder: '138 0013 8000', regex: /^1[3-9][0-9]{9}$/ },
    ],
  },
  {
    code: 'MY',
    flag: '/images/flags/my.svg',
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
  <div class="profile-settings">
    <header class="profile-settings-header">
      <h1>Profil publik</h1>
      <p>Kelola informasi yang tampil pada akun dan profil Anda.</p>
    </header>

    <!-- Toast success -->
    <transition name="fade">
      <div
        v-if="message"
        class="profile-notice profile-notice--success"
      >
        <CheckCircleIcon class="h-4 w-4 shrink-0" />
        {{ message }}
        <button type="button" class="ml-auto" @click="message = ''"><XMarkIcon class="h-3.5 w-3.5" /></button>
      </div>
    </transition>

    <div v-if="error" class="profile-notice profile-notice--error">
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

    <div class="profile-layout">
      <main class="profile-main">
        <form @submit.prevent="handleSubmit" novalidate>
          <section class="profile-section">
            <div class="section-heading">
              <h2>Informasi dasar</h2>
              <p>Informasi ini digunakan untuk mengenali Anda di dalam Rumah Nafasy.</p>
            </div>

            <div class="profile-field">
              <label class="field-label" for="prof-name">Nama lengkap</label>
              <input id="prof-name" v-model="name" type="text" autocomplete="name" class="field-input" :class="{ 'field-input--error': nameError }" @blur="validateName()" />
              <p v-if="nameError" class="field-error">{{ nameError }}</p>
            </div>

            <div class="profile-field">
              <label class="field-label" for="prof-email">Email</label>
              <input id="prof-email" :value="email" type="email" readonly disabled class="field-input field-input--disabled" title="Email tidak dapat diubah" />
              <p class="field-help">Email tidak dapat diubah langsung. Hubungi dukungan jika diperlukan.</p>
            </div>

            <div class="profile-field">
              <label class="field-label" for="prof-phone">Nomor telepon <span>(opsional)</span></label>
              <div class="phone-field" :class="{ 'phone-field--error': phoneError }">
                <div class="relative">
                  <button type="button" class="country-btn" :aria-label="`Kode negara: ${currentCountry.name} ${currentCountry.dialCode}`" @click="countryOpen = !countryOpen">
                    <img :src="currentCountry.flag" :alt="`Bendera ${currentCountry.name}`" class="h-5 w-7 rounded-sm object-cover" />
                    <span class="text-xs font-medium text-[var(--text)]">{{ currentCountry.dialCode }}</span>
                    <svg class="h-3.5 w-3.5 text-[var(--muted)] transition-transform" :class="{ 'rotate-180': countryOpen }" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25 4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/>
                    </svg>
                  </button>
                  <transition name="dropdown">
                    <div v-if="countryOpen" class="country-dropdown">
                      <button v-for="c in COUNTRIES" :key="c.code" type="button" class="country-option" :class="{ 'country-option--active': selectedCountry === c.code }" @click="selectCountry(c.code)">
                        <img :src="c.flag" :alt="`Bendera ${c.name}`" class="h-5 w-7 rounded-sm object-cover" />
                        <span class="flex-1 text-left text-xs">{{ c.name }}</span>
                        <span class="text-xs text-[var(--muted)]">{{ c.dialCode }}</span>
                      </button>
                    </div>
                  </transition>
                </div>
                <div class="phone-divider"></div>
                <input id="prof-phone" :value="phoneLocal" type="tel" :placeholder="phonePlaceholder" class="phone-input" @input="onPhoneInput" @blur="validatePhone()" />
              </div>
              <p v-if="phoneError" class="field-error">{{ phoneError }}</p>
              <p v-else-if="phoneForStorage" class="field-help">Akan disimpan sebagai: <code class="font-mono">{{ phoneForStorage }}</code></p>
              <p v-else class="field-help">Tersedia untuk Indonesia, Tiongkok, dan Malaysia.</p>
            </div>
          </section>

          <section v-if="auth.isPsikolog && auth.user?.psikolog_profile" class="profile-section">
            <div class="section-heading">
              <h2>Kredensial profesional</h2>
              <p>Informasi ini hanya dapat dilihat dan dikelola oleh pihak berwenang.</p>
            </div>
            <div class="credential-grid">
              <p><span>Nomor SIP</span><strong>{{ auth.user.psikolog_profile.license_no }}</strong></p>
              <p><span>Pendidikan</span><strong>{{ auth.user.psikolog_profile.education }}</strong></p>
              <p><span>Pengalaman</span><strong>{{ auth.user.psikolog_profile.experience_years }} tahun</strong></p>
              <p><span>Instansi</span><strong>{{ auth.user.psikolog_profile.workplace }}</strong></p>
            </div>
          </section>

          <div class="profile-actions">
            <BaseButton type="submit" size="md" :disabled="isSaving">
              {{ isSaving ? 'Menyimpan…' : 'Simpan perubahan' }}
            </BaseButton>
          </div>
        </form>

        <AccountMethodsCard class="account-methods" />
      </main>

      <aside class="profile-sidebar">
        <section class="avatar-settings">
          <div class="section-heading">
            <h2>Foto profil</h2>
            <p>Foto ini akan muncul di akun Anda.</p>
          </div>
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
            <div class="avatar-image">
              <img v-if="currentAvatar" :src="currentAvatar" :alt="name" class="h-full w-full object-cover" />
              <span v-else class="avatar-initials">{{ userInitials }}</span>
            </div>
            <div class="avatar-overlay"><CameraIcon class="h-6 w-6 text-white" /></div>
          </div>
          <input ref="avatarInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onAvatarFileChange" />
          <div class="avatar-actions">
            <button type="button" class="edit-avatar-button" @click="avatarInput?.click()">
              <PencilIcon class="h-3.5 w-3.5" /> Edit foto
            </button>
            <button v-if="auth.user?.avatar && !avatarFile" type="button" class="delete-avatar-button" @click="showDeleteConfirm = true">
              <TrashIcon class="h-3.5 w-3.5" /> Hapus
            </button>
          </div>
          <div v-if="avatarFile" class="avatar-pending">
            <span>{{ avatarFile.name }}</span>
            <button type="button" class="avatar-save-button" @click="quickSaveAvatar" :disabled="isUploadingAvatar">{{ isUploadingAvatar ? 'Mengunggah...' : 'Simpan' }}</button>
            <button type="button" class="avatar-cancel-button" @click="cancelAvatarChange">Batal</button>
          </div>
          <p v-if="avatarError" class="field-error">{{ avatarError }}</p>
          <p v-else class="field-help avatar-help">JPG, PNG, atau WebP. Maksimal 2 MB.</p>
        </section>

        <section class="profile-summary">
          <div class="summary-role"><BaseBadge :tone="auth.isPsikolog ? 'accent' : 'info'">{{ auth.isPsikolog ? 'Psikolog berlisensi' : 'Pasien' }}</BaseBadge></div>
          <span class="verified-label"><ShieldCheckIcon class="h-3.5 w-3.5" /> Terverifikasi</span>
        </section>
      </aside>
    </div>

  </div>
</template>

<style scoped>
/* ── GitHub-style profile settings ──────────────────────────────────── */
.profile-settings {
  width: min(100%, 1050px);
  margin: 0 auto;
  color: var(--text);
}
.profile-settings-header {
  padding: 8px 0 18px;
  border-bottom: 1px solid var(--line);
}
.profile-settings-header h1 {
  margin: 0;
  font-size: 25px;
  font-weight: 500;
  letter-spacing: -0.01em;
}
.profile-settings-header p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 13px;
}
.profile-notice {
  display: flex;
  align-items: center;
  gap: 8px;
  margin-top: 18px;
  padding: 10px 12px;
  border: 1px solid color-mix(in srgb, var(--accent) 22%, transparent);
  border-radius: 6px;
  font-size: 12px;
  font-weight: 500;
}
.profile-notice--success { color: #198754; background: color-mix(in srgb, #198754 8%, transparent); }
.profile-notice--error { color: #d1242f; background: color-mix(in srgb, #d1242f 8%, transparent); }
.profile-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 48px;
}
.profile-main { min-width: 0; }
.profile-section {
  padding: 25px 0 30px;
  border-bottom: 1px solid var(--line);
}
.section-heading h2 {
  margin: 0;
  color: var(--text);
  font-size: 16px;
  font-weight: 600;
}
.section-heading p {
  margin: 5px 0 0;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.5;
}
.profile-field { margin-top: 22px; }
.profile-field:first-of-type { margin-top: 24px; }
.field-label {
  display: block;
  margin-bottom: 7px;
  color: var(--text);
  font-size: 13px;
  font-weight: 600;
}
.field-label span { color: var(--muted); font-weight: 400; }
.field-help {
  margin: 6px 0 0;
  color: var(--muted);
  font-size: 11.5px;
  line-height: 1.45;
}
.profile-actions {
  display: flex;
  justify-content: flex-end;
  padding: 22px 0;
  border-bottom: 1px solid var(--line);
}
.credential-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-top: 20px;
}
.credential-grid p {
  display: flex;
  flex-direction: column;
  gap: 3px;
  margin: 0;
  color: var(--muted);
  font-size: 11px;
}
.credential-grid strong { color: var(--text); font-size: 12px; font-weight: 500; }
.profile-sidebar {
  min-width: 0;
  padding-top: 25px;
}
.avatar-settings {
  padding-bottom: 24px;
  border-bottom: 1px solid var(--line);
}
.avatar-drop-zone {
  width: 280px;
  height: 280px;
  margin: 22px auto 0;
}
.avatar-settings .avatar-image { width: 100%; height: 100%; }
.avatar-settings .avatar-drop-zone { width: 280px; height: 280px; }
.avatar-settings .avatar-initials { font-size: 54px; }
.avatar-edit-indicator { width: 27px; height: 27px; }
.avatar-actions {
  display: flex;
  justify-content: center;
  gap: 8px;
  margin-top: 15px;
}
.edit-avatar-button,
.delete-avatar-button {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  min-height: 31px;
  padding: 0 11px;
  border: 1px solid var(--line);
  border-radius: 6px;
  background: var(--surface);
  color: var(--text);
  cursor: pointer;
  font: inherit;
  font-size: 11px;
  transition: border-color 140ms, background 140ms;
}
.edit-avatar-button:hover { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 7%, transparent); }
.delete-avatar-button { color: #d1242f; }
.delete-avatar-button:hover { border-color: #d1242f; background: color-mix(in srgb, #d1242f 7%, transparent); }
.avatar-pending {
  display: flex;
  flex-wrap: wrap;
  justify-content: center;
  gap: 6px 10px;
  margin-top: 12px;
  color: var(--muted);
  font-size: 11px;
  text-align: center;
}
.avatar-pending button {
  min-height: 30px;
  padding: 0 10px;
  border-radius: 6px;
  cursor: pointer;
  font-size: 11px;
  font-weight: 600;
  transition: background 140ms, border-color 140ms, opacity 140ms;
}
.avatar-save-button {
  border: 1px solid #16803c;
  background: #1f883d;
  color: #fff;
}
.avatar-save-button:hover { background: #1a7f37; }
.avatar-save-button:disabled { cursor: wait; opacity: 0.6; }
.avatar-cancel-button {
  border: 1px solid var(--line);
  background: var(--muted)/10;
  color: var(--text);
}
.avatar-cancel-button:hover { border-color: var(--muted); background: var(--muted)/20; }
.avatar-help { text-align: center; }
.profile-summary { padding-top: 20px; }
.summary-role { display: flex; justify-content: center; }
.verified-label {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  margin-top: 10px;
  color: #198754;
  font-size: 11px;
  font-weight: 500;
}
.account-methods { margin-top: 24px; }

/* ── Field ───────────────────────────────────────────────────────────── */
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
  overflow: visible;
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
  overflow: hidden;
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

@media (max-width: 760px) {
  .profile-settings-header { padding-top: 0; }
  .profile-layout { display: flex; flex-direction: column-reverse; gap: 0; }
  .profile-sidebar { padding-top: 24px; }
  .avatar-settings { padding-bottom: 24px; }
  .avatar-settings .avatar-drop-zone { width: 220px; height: 220px; }
  .profile-main { width: 100%; }
  .credential-grid { grid-template-columns: 1fr; }
  .profile-actions { justify-content: stretch; }
  .profile-actions :deep(button) { width: 100%; justify-content: center; }
}
</style>
