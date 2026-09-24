<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { apiFetch } from '../../../lib/api'
import PageHeader from '../../../components/dashboard/PageHeader.vue'
import BaseCard from '../../../components/ui/BaseCard.vue'
import BaseButton from '../../../components/ui/BaseButton.vue'
import BaseBadge from '../../../components/ui/BaseBadge.vue'
import {
  PlusIcon, PencilSquareIcon, TrashIcon, CheckCircleIcon,
  NoSymbolIcon, ArrowPathIcon, XMarkIcon, MagnifyingGlassIcon,
  EyeIcon,
} from '@heroicons/vue/24/outline'

// ── Types ──────────────────────────────────────────────────────────────────────
interface PsikologProfile {
  id: number; slug: string; bio: string | null; experience_years: number
  license_no: string; education: string; workplace: string; custom_rate: number | null
  status: 'pending' | 'verified' | 'suspended'; is_available: boolean
  specialization?: string; verified_at?: string
  rating_avg: number; total_reviews: number; total_consultations: number
}
interface Psikolog {
  id: number; name: string; email: string; phone: string | null
  avatar: string | null; roles: string[]; psikolog_profile: PsikologProfile | null
  created_at: string
}
interface Specialization { id: number; name: string; slug: string }
interface Meta { current_page: number; last_page: number; total: number; per_page: number }

// ── State ─────────────────────────────────────────────────────────────────────
const list = ref<Psikolog[]>([])
const meta = ref<Meta>({ current_page: 1, last_page: 1, total: 0, per_page: 10 })
const specializations = ref<Specialization[]>([])
const isLoading = ref(false)
const search = ref('')
const filterStatus = ref('')

// ── Modal state ───────────────────────────────────────────────────────────────
type ModalMode = 'create' | 'edit' | 'detail' | 'delete' | 'suspend' | null
const modalMode = ref<ModalMode>(null)
const selected = ref<Psikolog | null>(null)
const isSaving = ref(false)
const modalError = ref('')
const modalSuccess = ref('')
const suspendReason = ref('')

// ── Form ──────────────────────────────────────────────────────────────────────
const form = ref({
  name: '', email: '', phone: '', password: '', password_confirmation: '',
  specialization_id: '' as number | '',
  bio: '', experience_years: 0, license_no: '', education: '', workplace: '',
  custom_rate: '' as number | '', is_available: true,
})
const formErrors = ref<Record<string, string>>({})

// ── Helpers ───────────────────────────────────────────────────────────────────
function statusTone(s: string): 'success' | 'warning' | 'danger' {
  return s === 'verified' ? 'success' : s === 'suspended' ? 'danger' : 'warning'
}
function statusLabel(s: string) {
  return s === 'verified' ? 'Terverifikasi' : s === 'suspended' ? 'Ditangguhkan' : 'Menunggu'
}

// ── Fetch ─────────────────────────────────────────────────────────────────────
async function fetchList(page = 1) {
  isLoading.value = true
  try {
    const params = new URLSearchParams({ page: String(page), per_page: '10' })
    if (search.value) params.set('search', search.value)
    if (filterStatus.value) params.set('status', filterStatus.value)
    const res = await apiFetch(`admin/psikolog?${params}`)
    // paginateResponse returns: { success, message, data: [...], meta: {...} } at root
    list.value = res.data ?? []
    meta.value = res.meta ?? { current_page: 1, last_page: 1, total: 0, per_page: 10 }
  } catch { } finally { isLoading.value = false }
}

async function fetchSpecializations() {
  try {
    const res = await apiFetch('admin/specializations?per_page=50')
    // may be paginated or plain array
    specializations.value = res.data?.data ?? res.data
  } catch { }
}

onMounted(() => { fetchList(); fetchSpecializations() })

// ── Validasi frontend ─────────────────────────────────────────────────────────
function validateForm(isEdit: boolean): boolean {
  const e: Record<string, string> = {}
  if (!form.value.name.trim()) e.name = 'Nama wajib diisi'
  if (!form.value.email.trim()) e.email = 'Email wajib diisi'
  else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.value.email)) e.email = 'Format email tidak valid'
  if (!isEdit || form.value.password) {
    if (!isEdit && !form.value.password) e.password = 'Password wajib diisi'
    if (form.value.password && form.value.password.length < 8) e.password = 'Minimal 8 karakter'
    if (form.value.password && form.value.password !== form.value.password_confirmation)
      e.password_confirmation = 'Konfirmasi tidak cocok'
  }
  if (!form.value.license_no.trim()) e.license_no = 'Nomor SIP wajib diisi'
  if (!form.value.education.trim()) e.education = 'Pendidikan wajib diisi'
  if (!form.value.workplace.trim()) e.workplace = 'Tempat praktik wajib diisi'
  if (form.value.experience_years < 0) e.experience_years = 'Tidak boleh negatif'
  formErrors.value = e
  return Object.keys(e).length === 0
}

// ── Open modals ───────────────────────────────────────────────────────────────
function openCreate() {
  form.value = {
    name: '', email: '', phone: '', password: '', password_confirmation: '',
    specialization_id: '', bio: '', experience_years: 0,
    license_no: '', education: '', workplace: '', custom_rate: '', is_available: true,
  }
  formErrors.value = {}; modalError.value = ''; modalSuccess.value = ''
  selected.value = null; modalMode.value = 'create'
}

function openEdit(p: Psikolog) {
  selected.value = p
  form.value = {
    name: p.name, email: p.email, phone: p.phone ?? '',
    password: '', password_confirmation: '',
    specialization_id: p.psikolog_profile?.specialization
      ? (specializations.value.find(s => s.name === p.psikolog_profile?.specialization)?.id ?? '')
      : '',
    bio: p.psikolog_profile?.bio ?? '',
    experience_years: p.psikolog_profile?.experience_years ?? 0,
    license_no: p.psikolog_profile?.license_no ?? '',
    education: p.psikolog_profile?.education ?? '',
    workplace: p.psikolog_profile?.workplace ?? '',
    custom_rate: p.psikolog_profile?.custom_rate ?? '',
    is_available: p.psikolog_profile?.is_available ?? true,
  }
  formErrors.value = {}; modalError.value = ''; modalSuccess.value = ''
  modalMode.value = 'edit'
}

function openDetail(p: Psikolog) { selected.value = p; modalMode.value = 'detail' }
function openDelete(p: Psikolog) { selected.value = p; modalMode.value = 'delete' }
function openSuspend(p: Psikolog) { selected.value = p; suspendReason.value = ''; modalMode.value = 'suspend' }
function closeModal() { modalMode.value = null; selected.value = null; modalSuccess.value = ''; modalError.value = '' }

// ── Actions ───────────────────────────────────────────────────────────────────
async function submitCreate() {
  if (!validateForm(false)) return
  isSaving.value = true; modalError.value = ''
  try {
    await apiFetch('admin/psikolog', {
      method: 'POST',
      body: JSON.stringify({
        ...form.value,
        specialization_id: form.value.specialization_id || null,
        custom_rate: form.value.custom_rate || null,
        phone: form.value.phone || null,
      }),
    })
    modalSuccess.value = 'Psikolog berhasil ditambahkan!'
    await fetchList()
    setTimeout(closeModal, 1200)
  } catch (e: any) {
    modalError.value = e.message || 'Gagal menyimpan'
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, v]: any) => { formErrors.value[k] = v[0] })
    }
  } finally { isSaving.value = false }
}

async function submitEdit() {
  if (!validateForm(true) || !selected.value) return
  isSaving.value = true; modalError.value = ''
  try {
    const payload: Record<string, any> = {
      name: form.value.name, email: form.value.email,
      phone: form.value.phone || null,
      specialization_id: form.value.specialization_id || null,
      bio: form.value.bio, experience_years: form.value.experience_years,
      license_no: form.value.license_no, education: form.value.education,
      workplace: form.value.workplace, custom_rate: form.value.custom_rate || null,
      is_available: form.value.is_available,
    }
    if (form.value.password) {
      payload.password = form.value.password
      payload.password_confirmation = form.value.password_confirmation
    }
    await apiFetch(`admin/psikolog/${selected.value.id}`, { method: 'PUT', body: JSON.stringify(payload) })
    modalSuccess.value = 'Data berhasil diperbarui!'
    await fetchList()
    setTimeout(closeModal, 1200)
  } catch (e: any) {
    modalError.value = e.message || 'Gagal menyimpan'
    if (e.errors) {
      Object.entries(e.errors).forEach(([k, v]: any) => { formErrors.value[k] = v[0] })
    }
  } finally { isSaving.value = false }
}

async function confirmDelete() {
  if (!selected.value) return
  isSaving.value = true; modalError.value = ''
  try {
    await apiFetch(`admin/psikolog/${selected.value.id}`, { method: 'DELETE' })
    await fetchList(); closeModal()
  } catch (e: any) { modalError.value = e.message || 'Gagal menghapus' } finally { isSaving.value = false }
}

async function confirmSuspend() {
  if (!selected.value) return
  isSaving.value = true; modalError.value = ''
  try {
    await apiFetch(`admin/psikolog/${selected.value.id}/suspend`, {
      method: 'PUT', body: JSON.stringify({ reason: suspendReason.value }),
    })
    await fetchList(); closeModal()
  } catch (e: any) { modalError.value = e.message || 'Gagal' } finally { isSaving.value = false }
}

async function quickAction(id: number, action: 'verify' | 'activate' | 'suspend') {
  try {
    await apiFetch(`admin/psikolog/${id}/${action}`, { method: 'PUT' })
    await fetchList()
  } catch { }
}
</script>

<template>
  <div class="mx-auto max-w-6xl">
    <PageHeader
      title="Kelola Psikolog"
      description="Tambah, ubah, dan pantau status semua psikolog yang terdaftar."
    />

    <!-- Toolbar -->
    <div class="mb-4 flex flex-wrap items-center gap-3">
      <div class="relative flex-1 min-w-[200px]">
        <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-[var(--muted)]" />
        <input
          v-model="search"
          type="search" placeholder="Cari nama psikolog..."
          class="w-full h-9 pl-9 pr-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-sm text-[var(--text)] outline-none focus:border-[var(--accent)] focus:ring-1 focus:ring-[var(--accent)]/20"
          @input="fetchList(1)"
        />
      </div>
      <select
        v-model="filterStatus"
        class="h-9 px-3 rounded-lg border border-[var(--line)] bg-[var(--surface)] text-xs text-[var(--text)] outline-none focus:border-[var(--accent)]"
        @change="fetchList(1)"
      >
        <option value="">Semua Status</option>
        <option value="pending">Menunggu</option>
        <option value="verified">Terverifikasi</option>
        <option value="suspended">Ditangguhkan</option>
      </select>
      <BaseButton variant="primary" size="sm" @click="openCreate">
        <PlusIcon class="h-4 w-4" /> Tambah Psikolog
      </BaseButton>
    </div>

    <!-- Table -->
    <BaseCard :padded="false">
      <div class="overflow-x-auto">
        <table class="w-full text-xs">
          <thead>
            <tr class="border-b border-[var(--line)] text-[var(--muted)]">
              <th class="px-4 py-3 text-left font-semibold">Psikolog</th>
              <th class="px-4 py-3 text-left font-semibold">Spesialisasi</th>
              <th class="px-4 py-3 text-left font-semibold">Nomor SIP</th>
              <th class="px-4 py-3 text-center font-semibold">Status</th>
              <th class="px-4 py-3 text-center font-semibold">Sesi</th>
              <th class="px-4 py-3 text-right font-semibold">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="isLoading">
              <td colspan="6" class="px-4 py-10 text-center text-[var(--muted)]">Memuat...</td>
            </tr>
            <tr v-else-if="list.length === 0">
              <td colspan="6" class="px-4 py-10 text-center text-[var(--muted)]">Tidak ada data</td>
            </tr>
            <tr
              v-for="p in list" :key="p.id"
              class="border-b border-[var(--line)] last:border-0 hover:bg-[var(--muted)]/4 transition-colors"
            >
              <!-- Psikolog -->
              <td class="px-4 py-3">
                <div class="flex items-center gap-3">
                  <div class="avatar-cell">
                    <img v-if="p.avatar" :src="p.avatar" :alt="p.name" class="h-full w-full object-cover" />
                    <span v-else class="text-[var(--accent)] font-bold text-xs">
                      {{ p.name.split(' ').slice(0,2).map(w=>w[0]).join('') }}
                    </span>
                  </div>
                  <div>
                    <div class="font-semibold text-[var(--text)]">{{ p.name }}</div>
                    <div class="text-[var(--muted)]">{{ p.email }}</div>
                  </div>
                </div>
              </td>
              <!-- Spesialisasi -->
              <td class="px-4 py-3 text-[var(--text)]">
                {{ p.psikolog_profile?.specialization ?? '—' }}
              </td>
              <!-- SIP -->
              <td class="px-4 py-3 font-mono text-[var(--muted)]">
                {{ p.psikolog_profile?.license_no ?? '—' }}
              </td>
              <!-- Status -->
              <td class="px-4 py-3 text-center">
                <BaseBadge :tone="statusTone(p.psikolog_profile?.status ?? 'pending')">
                  {{ statusLabel(p.psikolog_profile?.status ?? 'pending') }}
                </BaseBadge>
              </td>
              <!-- Sesi -->
              <td class="px-4 py-3 text-center text-[var(--text)]">
                {{ p.psikolog_profile?.total_consultations ?? 0 }}
              </td>
              <!-- Aksi -->
              <td class="px-4 py-3">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button" title="Detail"
                    class="icon-btn"
                    @click="openDetail(p)"
                  ><EyeIcon class="h-4 w-4" /></button>
                  <button
                    type="button" title="Edit"
                    class="icon-btn"
                    @click="openEdit(p)"
                  ><PencilSquareIcon class="h-4 w-4" /></button>
                  <button
                    v-if="p.psikolog_profile?.status !== 'verified'"
                    type="button" title="Verifikasi"
                    class="icon-btn text-emerald-600"
                    @click="quickAction(p.id, 'verify')"
                  ><CheckCircleIcon class="h-4 w-4" /></button>
                  <button
                    v-if="p.psikolog_profile?.status === 'suspended'"
                    type="button" title="Aktifkan"
                    class="icon-btn text-sky-600"
                    @click="quickAction(p.id, 'activate')"
                  ><ArrowPathIcon class="h-4 w-4" /></button>
                  <button
                    v-if="p.psikolog_profile?.status === 'verified'"
                    type="button" title="Tangguhkan"
                    class="icon-btn text-amber-600"
                    @click="openSuspend(p)"
                  ><NoSymbolIcon class="h-4 w-4" /></button>
                  <button
                    type="button" title="Hapus"
                    class="icon-btn text-rose-500"
                    @click="openDelete(p)"
                  ><TrashIcon class="h-4 w-4" /></button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta.last_page > 1" class="flex items-center justify-between border-t border-[var(--line)] px-4 py-3">
        <span class="text-xs text-[var(--muted)]">Total {{ meta.total }} psikolog</span>
        <div class="flex gap-1">
          <button
            v-for="p in meta.last_page" :key="p"
            type="button"
            class="h-7 w-7 rounded text-xs transition-colors"
            :class="p === meta.current_page
              ? 'bg-[var(--accent)] text-white'
              : 'text-[var(--muted)] hover:bg-[var(--muted)]/10'"
            @click="fetchList(p)"
          >{{ p }}</button>
        </div>
      </div>
    </BaseCard>

    <!-- ── MODAL OVERLAY ── -->
    <teleport to="body">
      <transition name="fade">
        <div
          v-if="modalMode"
          class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4"
          @mousedown.self="closeModal"
        >
          <!-- ── DETAIL ── -->
          <div v-if="modalMode === 'detail' && selected" class="modal-box max-w-lg w-full">
            <div class="modal-header">
              <h3>Detail Psikolog</h3>
              <button type="button" class="icon-btn" @click="closeModal"><XMarkIcon class="h-4 w-4" /></button>
            </div>
            <div class="p-5 space-y-3 text-sm">
              <div class="flex items-center gap-4">
                <div class="avatar-lg">
                  <img v-if="selected.avatar" :src="selected.avatar" :alt="selected.name" class="h-full w-full object-cover" />
                  <span v-else class="text-[var(--accent)] font-bold text-lg">
                    {{ selected.name.split(' ').slice(0,2).map(w=>w[0]).join('') }}
                  </span>
                </div>
                <div>
                  <div class="font-semibold text-[var(--text)] text-base">{{ selected.name }}</div>
                  <div class="text-[var(--muted)] text-xs">{{ selected.email }}</div>
                  <BaseBadge :tone="statusTone(selected.psikolog_profile?.status ?? 'pending')" class="mt-1">
                    {{ statusLabel(selected.psikolog_profile?.status ?? 'pending') }}
                  </BaseBadge>
                </div>
              </div>
              <div class="grid grid-cols-2 gap-2 text-xs">
                <div><span class="text-[var(--muted)]">Nomor SIP</span><br><strong>{{ selected.psikolog_profile?.license_no ?? '—' }}</strong></div>
                <div><span class="text-[var(--muted)]">Pengalaman</span><br><strong>{{ selected.psikolog_profile?.experience_years ?? 0 }} tahun</strong></div>
                <div><span class="text-[var(--muted)]">Spesialisasi</span><br><strong>{{ selected.psikolog_profile?.specialization ?? '—' }}</strong></div>
                <div><span class="text-[var(--muted)]">Total Sesi</span><br><strong>{{ selected.psikolog_profile?.total_consultations ?? 0 }}</strong></div>
                <div><span class="text-[var(--muted)]">Pendidikan</span><br><strong>{{ selected.psikolog_profile?.education ?? '—' }}</strong></div>
                <div><span class="text-[var(--muted)]">Tempat Praktik</span><br><strong>{{ selected.psikolog_profile?.workplace ?? '—' }}</strong></div>
              </div>
              <div v-if="selected.psikolog_profile?.bio" class="text-xs text-[var(--muted)] bg-[var(--muted)]/5 rounded-lg p-3">
                {{ selected.psikolog_profile.bio }}
              </div>
            </div>
            <div class="modal-footer">
              <BaseButton variant="secondary" size="sm" @click="closeModal">Tutup</BaseButton>
              <BaseButton variant="primary" size="sm" @click="openEdit(selected)">Edit Data</BaseButton>
            </div>
          </div>

          <!-- ── CREATE / EDIT FORM ── -->
          <div v-else-if="modalMode === 'create' || modalMode === 'edit'" class="modal-box max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div class="modal-header sticky top-0 bg-[var(--surface)] z-10">
              <h3>{{ modalMode === 'create' ? 'Tambah Psikolog Baru' : 'Edit Data Psikolog' }}</h3>
              <button type="button" class="icon-btn" @click="closeModal"><XMarkIcon class="h-4 w-4" /></button>
            </div>

            <div v-if="modalError" class="mx-5 mt-4 p-3 rounded-lg bg-rose-500/10 text-rose-600 text-xs border border-rose-500/20">{{ modalError }}</div>
            <div v-if="modalSuccess" class="mx-5 mt-4 p-3 rounded-lg bg-emerald-500/10 text-emerald-600 text-xs border border-emerald-500/20">{{ modalSuccess }}</div>

            <form class="p-5 space-y-5" @submit.prevent="modalMode === 'create' ? submitCreate() : submitEdit()" novalidate>
              <!-- Informasi Akun -->
              <div>
                <h4 class="text-xs font-semibold text-[var(--muted)] uppercase tracking-wide mb-3">Informasi Akun</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input v-model="form.name" type="text" placeholder="dr. Nama, M.Psi" :class="{ 'is-error': formErrors.name }" />
                    <span v-if="formErrors.name" class="form-error">{{ formErrors.name }}</span>
                  </div>
                  <div class="form-group">
                    <label>Email *</label>
                    <input v-model="form.email" type="email" placeholder="nama@rumah-nafasy.id" :class="{ 'is-error': formErrors.email }" />
                    <span v-if="formErrors.email" class="form-error">{{ formErrors.email }}</span>
                  </div>
                  <div class="form-group">
                    <label>Nomor Telepon</label>
                    <input v-model="form.phone" type="tel" placeholder="+628xxx" />
                  </div>
                  <div></div>
                  <div class="form-group">
                    <label>{{ modalMode === 'create' ? 'Password *' : 'Password Baru (kosongkan jika tidak diubah)' }}</label>
                    <input v-model="form.password" type="password" placeholder="••••••••" :class="{ 'is-error': formErrors.password }" />
                    <span v-if="formErrors.password" class="form-error">{{ formErrors.password }}</span>
                  </div>
                  <div class="form-group">
                    <label>Konfirmasi Password {{ modalMode === 'create' ? '*' : '' }}</label>
                    <input v-model="form.password_confirmation" type="password" placeholder="••••••••" :class="{ 'is-error': formErrors.password_confirmation }" />
                    <span v-if="formErrors.password_confirmation" class="form-error">{{ formErrors.password_confirmation }}</span>
                  </div>
                </div>
              </div>

              <!-- Profil Profesional -->
              <div>
                <h4 class="text-xs font-semibold text-[var(--muted)] uppercase tracking-wide mb-3">Profil Profesional</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div class="form-group">
                    <label>Nomor SIP *</label>
                    <input v-model="form.license_no" type="text" placeholder="SIP-12345" :class="{ 'is-error': formErrors.license_no }" />
                    <span v-if="formErrors.license_no" class="form-error">{{ formErrors.license_no }}</span>
                  </div>
                  <div class="form-group">
                    <label>Spesialisasi</label>
                    <select v-model="form.specialization_id">
                      <option value="">Tidak ada</option>
                      <option v-for="s in specializations" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </select>
                  </div>
                  <div class="form-group">
                    <label>Pendidikan Terakhir *</label>
                    <input v-model="form.education" type="text" placeholder="S2 Psikologi Klinis, UI" :class="{ 'is-error': formErrors.education }" />
                    <span v-if="formErrors.education" class="form-error">{{ formErrors.education }}</span>
                  </div>
                  <div class="form-group">
                    <label>Tempat Praktik *</label>
                    <input v-model="form.workplace" type="text" placeholder="Klinik / RS / Praktik Pribadi" :class="{ 'is-error': formErrors.workplace }" />
                    <span v-if="formErrors.workplace" class="form-error">{{ formErrors.workplace }}</span>
                  </div>
                  <div class="form-group">
                    <label>Pengalaman (tahun) *</label>
                    <input v-model.number="form.experience_years" type="number" min="0" max="60" :class="{ 'is-error': formErrors.experience_years }" />
                    <span v-if="formErrors.experience_years" class="form-error">{{ formErrors.experience_years }}</span>
                  </div>
                  <div class="form-group">
                    <label>Tarif Kustom (Rp)</label>
                    <input v-model.number="form.custom_rate" type="number" min="0" placeholder="Biarkan kosong = tarif default" />
                  </div>
                  <div v-if="modalMode === 'edit'" class="form-group sm:col-span-2">
                    <label class="flex items-center gap-2 cursor-pointer">
                      <input v-model="form.is_available" type="checkbox" class="rounded border-[var(--line)]" />
                      <span>Tersedia untuk booking</span>
                    </label>
                  </div>
                  <div class="form-group sm:col-span-2">
                    <label>Bio</label>
                    <textarea v-model="form.bio" rows="3" placeholder="Deskripsi singkat tentang psikolog..." class="resize-none"></textarea>
                  </div>
                </div>
              </div>

              <div class="modal-footer -mx-5 -mb-5 px-5 pb-5 pt-4 border-t border-[var(--line)]">
                <BaseButton type="button" variant="secondary" size="sm" @click="closeModal">Batal</BaseButton>
                <BaseButton type="submit" variant="primary" size="sm" :disabled="isSaving">
                  {{ isSaving ? 'Menyimpan...' : (modalMode === 'create' ? 'Tambahkan Psikolog' : 'Simpan Perubahan') }}
                </BaseButton>
              </div>
            </form>
          </div>

          <!-- ── DELETE CONFIRM ── -->
          <div v-else-if="modalMode === 'delete' && selected" class="modal-box max-w-sm w-full">
            <div class="modal-header">
              <h3>Hapus Psikolog</h3>
              <button type="button" class="icon-btn" @click="closeModal"><XMarkIcon class="h-4 w-4" /></button>
            </div>
            <div class="p-5">
              <p class="text-sm text-[var(--text)] mb-1">Hapus akun <strong>{{ selected.name }}</strong>?</p>
              <p class="text-xs text-[var(--muted)]">Akun dan profil akan dihapus permanen. Tindakan ini tidak dapat dibatalkan.</p>
              <div v-if="modalError" class="mt-3 text-xs text-rose-600">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <BaseButton variant="secondary" size="sm" @click="closeModal">Batal</BaseButton>
              <BaseButton variant="danger" size="sm" :disabled="isSaving" @click="confirmDelete">
                {{ isSaving ? 'Menghapus...' : 'Hapus Permanen' }}
              </BaseButton>
            </div>
          </div>

          <!-- ── SUSPEND ── -->
          <div v-else-if="modalMode === 'suspend' && selected" class="modal-box max-w-sm w-full">
            <div class="modal-header">
              <h3>Tangguhkan Psikolog</h3>
              <button type="button" class="icon-btn" @click="closeModal"><XMarkIcon class="h-4 w-4" /></button>
            </div>
            <div class="p-5 space-y-3">
              <p class="text-sm text-[var(--text)]">Tangguhkan <strong>{{ selected.name }}</strong>?</p>
              <div class="form-group">
                <label>Alasan (opsional)</label>
                <textarea v-model="suspendReason" rows="3" placeholder="Alasan penangguhan..." class="resize-none"></textarea>
              </div>
              <div v-if="modalError" class="text-xs text-rose-600">{{ modalError }}</div>
            </div>
            <div class="modal-footer">
              <BaseButton variant="secondary" size="sm" @click="closeModal">Batal</BaseButton>
              <BaseButton variant="danger" size="sm" :disabled="isSaving" @click="confirmSuspend">
                {{ isSaving ? 'Memproses...' : 'Tangguhkan' }}
              </BaseButton>
            </div>
          </div>
        </div>
      </transition>
    </teleport>
  </div>
</template>

<style scoped>
.avatar-cell {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  background: color-mix(in srgb, var(--accent) 12%, transparent);
  display: flex; align-items: center; justify-content: center;
  overflow: hidden;
}
.avatar-lg {
  width: 60px; height: 60px; border-radius: 50%; flex-shrink: 0;
  background: color-mix(in srgb, var(--accent) 12%, transparent);
  display: flex; align-items: center; justify-content: center; overflow: hidden;
}
.icon-btn {
  display: inline-flex; align-items: center; justify-content: center;
  width: 28px; height: 28px; border-radius: 6px; border: 0;
  background: transparent; color: var(--muted); cursor: pointer;
  transition: background 150ms, color 150ms;
}
.icon-btn:hover { background: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--text); }

.modal-box {
  background: var(--surface); border: 1px solid var(--line);
  border-radius: 16px; box-shadow: 0 24px 64px rgba(0,0,0,0.2);
}
.modal-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 16px 20px; border-bottom: 1px solid var(--line);
}
.modal-header h3 { font-size: 14px; font-weight: 600; color: var(--text); }
.modal-footer {
  display: flex; justify-content: flex-end; gap: 8px;
  padding: 12px 20px; border-top: 1px solid var(--line);
}

.form-group { display: grid; gap: 5px; }
.form-group label { font-size: 11.5px; font-weight: 600; color: var(--text); }
.form-group input,
.form-group select,
.form-group textarea {
  width: 100%; padding: 0 12px; height: 38px;
  border: 1px solid var(--line); border-radius: 8px;
  background: var(--surface); color: var(--text);
  font: inherit; font-size: 13px; outline: none;
  transition: border-color 150ms, box-shadow 150ms; box-sizing: border-box;
}
.form-group textarea { height: auto; padding: 8px 12px; }
.form-group input::placeholder, .form-group textarea::placeholder { color: var(--muted); opacity: 0.6; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--accent) 15%, transparent);
}
.form-group input.is-error, .form-group select.is-error { border-color: #ef4444; }
.form-error { font-size: 11px; color: #ef4444; }

.fade-enter-active, .fade-leave-active { transition: opacity 200ms; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
