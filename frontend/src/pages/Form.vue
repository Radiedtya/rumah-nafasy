<script setup lang="ts">
import { computed, h, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import type { FunctionalComponent } from 'vue'
import { useRouter } from 'vue-router'
import { parseDate } from '@internationalized/date'
import type { DateValue } from '@internationalized/date'
import {
  CalendarCell, CalendarCellTrigger, CalendarGrid, CalendarGridBody, CalendarGridHead,
  CalendarGridRow, CalendarHeadCell, CalendarHeader, CalendarHeading, CalendarNext,
  CalendarPrev, CalendarRoot,
} from 'reka-ui'
import { apiFetch } from '../lib/api'
import { formatRupiah } from '../stores/booking'
import Navbar from '../components/app/Navbar.vue'

/* ───────────────────────── Types & constants ───────────────────────── */

interface Category { id: number | string; name: string; base_price: number | string }
type CountryCode = 'ID' | 'MY' | 'CN'
interface Country {
  code: CountryCode; name: string; dial: string
  lead: string; min: number; max: number; digits: string; example: string; groups: number[]
}
type DurationChoice = '30' | '60' | '90' | 'custom'
type Shape = [string, Record<string, string | number>]

const WA_NUMBER = '6288222150964'
const DRAFT_KEY = 'rumah-nafasy:consultation-draft:v2'
const TZ = 'Asia/Jakarta'
const NAME_RE = /^[\p{L}][\p{L}\p{M} .'’-]*$/u

// Panjang & awalan nomor seluler (setelah kode negara, tanpa angka 0 di depan).
const countries: Country[] = [
  { code: 'ID', name: 'Indonesia', dial: '+62', lead: '8', min: 9, max: 12, digits: '9–12', example: '812 3456 7890', groups: [3, 4, 5] },
  { code: 'MY', name: 'Malaysia', dial: '+60', lead: '1', min: 9, max: 10, digits: '9–10', example: '12 345 6789', groups: [2, 3, 4] },
  { code: 'CN', name: 'China', dial: '+86', lead: '1', min: 11, max: 11, digits: '11', example: '138 0013 8000', groups: [3, 4, 4] },
]
const consultationKinds = [
  { value: 'Video Call', icon: 'video', note: 'Online, dari mana saja' },
  { value: 'Tatap Muka', icon: 'pin', note: 'Bertemu langsung dengan psikolog' },
]
const durationOptions: { value: DurationChoice; title: string; unit?: string; note: string }[] = [
  { value: '30', title: '30', unit: 'menit', note: 'Singkat' },
  { value: '60', title: '60', unit: 'menit', note: 'Standar' },
  { value: '90', title: '90', unit: 'menit', note: 'Mendalam' },
  { value: 'custom', title: 'Lainnya', note: 'Atur sendiri' },
]
const topicOptions = [
  'Cemas atau panik', 'Sulit tidur', 'Stres kerja atau kuliah', 'Hubungan dan keluarga',
  'Rasa sedih atau kehilangan', 'Trauma', 'Anak dan remaja', 'Belum yakin',
]
const timeGroups = [
  { label: 'Pagi', slots: ['08:00', '09:00', '10:00', '11:00'] },
  { label: 'Siang', slots: ['12:00', '13:00', '14:00', '15:00'] },
  { label: 'Sore', slots: ['16:00', '17:00', '18:00'] },
  { label: 'Malam', slots: ['19:00', '20:00', '21:00'] },
]
const stepTitles = ['Pilih layanan', 'Ceritakan kebutuhan', 'Atur jadwal', 'Tinjau pengajuan']
const stepLabels = ['Layanan', 'Kebutuhan', 'Jadwal', 'Tinjau']
const stepDescriptions = [
  'Pilih cara konsultasi, kategori, dan durasi yang sesuai.',
  'Bagikan hal yang ingin Anda bicarakan. Bagian ini boleh dilewati.',
  'Isi kontak dan waktu yang paling nyaman untuk Anda.',
  'Pastikan semuanya benar sebelum membuka WhatsApp.',
]
const stepFields: Record<number, string[]> = {
  1: ['category', 'duration'],
  2: [],
  3: ['name', 'phone', 'date', 'time'],
}
const FIELD_IDS: Record<string, string> = {
  category: 'category-group', duration: 'custom-minutes', name: 'name',
  phone: 'phone', date: 'date-group', time: 'time-group',
}

/* ───────────────────────── Icons (inline, no extra dependency) ───────────────────────── */

const ICONS: Record<string, Shape[]> = {
  check: [['path', { d: 'M20 6 9 17l-5-5' }]],
  'arrow-right': [['path', { d: 'M5 12h14' }], ['path', { d: 'm13 6 6 6-6 6' }]],
  'arrow-left': [['path', { d: 'M19 12H5' }], ['path', { d: 'm11 6-6 6 6 6' }]],
  video: [['rect', { x: 2, y: 6, width: 14, height: 12, rx: 2 }], ['path', { d: 'm16 10 6-3v10l-6-3' }]],
  pin: [['path', { d: 'M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z' }], ['circle', { cx: 12, cy: 10, r: 3 }]],
  edit: [['path', { d: 'M4 20h4L19 9l-4-4L4 16v4Z' }], ['path', { d: 'm13.5 6.5 4 4' }]],
  lock: [['rect', { x: 4, y: 11, width: 16, height: 10, rx: 2 }], ['path', { d: 'M8 11V7a4 4 0 0 1 8 0v4' }]],
  copy: [['rect', { x: 9, y: 9, width: 12, height: 12, rx: 2 }], ['path', { d: 'M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1' }]],
  heart: [['path', { d: 'M19.5 12.6 12 20l-7.5-7.4a5 5 0 0 1 7.1-7.1l.4.4.4-.4a5 5 0 0 1 7.1 7.1Z' }]],
  whatsapp: [['path', { d: 'M3 21l1.6-4.8A8.5 8.5 0 1 1 8 19.6L3 21Z' }], ['path', { d: 'M9 9.5c.5 2 2.5 4 5 5l1.2-1.2-1.8-1-.8.7c-.8-.3-1.7-1.2-2-2l.7-.8-1-1.8Z' }]],
  refresh: [['path', { d: 'M21 12a9 9 0 1 1-3-6.7' }], ['path', { d: 'M21 4v5h-5' }]],
  shield: [['path', { d: 'M12 3 4 6v6c0 4.6 3.2 8 8 9 4.8-1 8-4.4 8-9V6l-8-3Z' }], ['path', { d: 'm9 12 2 2 4-4' }]],
}
const Icon: FunctionalComponent<{ name: string; size?: number }> = (props) =>
  h(
    'svg',
    {
      viewBox: '0 0 24 24', width: props.size ?? 18, height: props.size ?? 18, fill: 'none',
      stroke: 'currentColor', 'stroke-width': 1.8, 'stroke-linecap': 'round',
      'stroke-linejoin': 'round', 'aria-hidden': 'true', focusable: 'false',
    },
    (ICONS[props.name] ?? []).map(([tag, attrs]) => h(tag, attrs)),
  )
Icon.props = ['name', 'size']

/* ───────────────────────── State ───────────────────────── */

const router = useRouter()
const navigationLinks = [{ label: 'Tentang', to: '/about' }]

const step = ref(1)
const direction = ref<'forward' | 'back'>('forward')
const editingFromReview = ref(false)

const categories = ref<Category[]>([])
const catalogLoading = ref(true)
const catalogError = ref(false)

const defaultForm = () => ({
  consultation: 'Video Call', categoryId: '', complaint: '', name: '',
  country: 'ID' as CountryCode, phone: '', date: '', time: '',
})
const form = ref(defaultForm())
const durationChoice = ref<DurationChoice>('60')
const customMinutes = ref<number | ''>('')
const topics = ref<string[]>([])
const touched = ref<Record<string, boolean>>({})

const contentRef = ref<HTMLElement | null>(null)
const headingRef = ref<HTMLElement | null>(null)
const copied = ref(false)
const confirmReset = ref(false)
let resetTimer: number | undefined
let copyTimer: number | undefined
let clockTimer: number | undefined

/* ───────────────────────── Draft (sessionStorage, hilang saat tab ditutup) ───────────────────────── */

const str = (v: unknown, max: number) => (typeof v === 'string' ? v.slice(0, max) : '')

function restoreDraft() {
  try {
    const raw = sessionStorage.getItem(DRAFT_KEY)
    if (!raw) return
    const d = JSON.parse(raw)
    const f = d?.form ?? {}
    form.value = {
      consultation: ['Video Call', 'Tatap Muka'].includes(f.consultation) ? f.consultation : 'Video Call',
      categoryId: str(f.categoryId, 40),
      complaint: str(f.complaint, 2000),
      name: str(f.name, 80),
      country: countries.some(c => c.code === f.country) ? f.country : 'ID',
      phone: str(f.phone, 30),
      date: str(f.date, 10),
      time: str(f.time, 5),
    }
    if (Array.isArray(d.topics)) topics.value = d.topics.filter((t: unknown) => typeof t === 'string' && topicOptions.includes(t))
    if (['30', '60', '90', 'custom'].includes(d.durationChoice)) durationChoice.value = d.durationChoice
    if (typeof d.customMinutes === 'number') customMinutes.value = d.customMinutes
    if (Number.isInteger(d.step)) step.value = Math.min(4, Math.max(1, d.step))
  } catch { /* draft rusak: abaikan */ }
}
function saveDraft() {
  try {
    sessionStorage.setItem(DRAFT_KEY, JSON.stringify({
      form: form.value, topics: topics.value, durationChoice: durationChoice.value,
      customMinutes: customMinutes.value, step: step.value,
    }))
  } catch { /* penyimpanan tidak tersedia: abaikan */ }
}
if (typeof window !== 'undefined') restoreDraft()

/* ───────────────────────── Waktu (selalu WIB) ───────────────────────── */

const now = ref(new Date())
const today = computed(() => now.value.toLocaleDateString('en-CA', { timeZone: TZ }))
const todayDate = computed(() => parseDate(today.value))
const nowMinutes = computed(() => {
  const [hh, mm] = new Intl.DateTimeFormat('en-GB', { timeZone: TZ, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
    .format(now.value).split(':').map(Number)
  return hh * 60 + mm
})
const fmtLong = new Intl.DateTimeFormat('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric', timeZone: TZ })
const fmtShort = new Intl.DateTimeFormat('id-ID', { weekday: 'short', day: 'numeric', month: 'short', timeZone: TZ })
const dateFrom = (iso: string) => new Date(`${iso}T12:00:00+07:00`)
const longDate = (iso: string) => (iso ? fmtLong.format(dateFrom(iso)) : '')
const shortDate = (iso: string) => (iso ? fmtShort.format(dateFrom(iso)) : '')
const toMinutes = (t: string) => { const [hh, mm] = t.split(':').map(Number); return hh * 60 + mm }

const calendarPlaceholder = ref<DateValue>(
  parseDate(form.value.date && form.value.date >= today.value ? form.value.date : today.value),
)
const quickDates = computed(() =>
  [0, 1, 2].map(n => {
    const iso = n === 0 ? today.value : parseDate(today.value).add({ days: n }).toString()
    return { value: iso, label: ['Hari ini', 'Besok', 'Lusa'][n], short: shortDate(iso) }
  }),
)
const isSlotPast = (slot: string) => form.value.date === today.value && toMinutes(slot) <= nowMinutes.value

/* ───────────────────────── Turunan data ───────────────────────── */

const selectedCountry = computed(() => countries.find(c => c.code === form.value.country) ?? countries[0])
const national = computed(() => {
  const c = selectedCountry.value
  let d = form.value.phone.replace(/\D/g, '')
  if (d.startsWith('00')) d = d.slice(2)
  if (d.startsWith(c.dial.slice(1))) d = d.slice(c.dial.length - 1)
  if (d.startsWith('0')) d = d.slice(1)
  return d
})
const normalizedPhone = computed(() => `${selectedCountry.value.dial}${national.value}`)
const phoneDisplay = computed(() => {
  let rest = national.value
  const parts: string[] = []
  for (const g of selectedCountry.value.groups) { if (!rest) break; parts.push(rest.slice(0, g)); rest = rest.slice(g) }
  return `${selectedCountry.value.dial} ${parts.join(' ')}`.trim()
})
const phoneValid = computed(() => {
  const c = selectedCountry.value
  return national.value.length >= c.min && national.value.length <= c.max && national.value.startsWith(c.lead)
})

const selectedCategory = computed(() => categories.value.find(i => String(i.id) === form.value.categoryId))
const categoryPrice = computed(() => (selectedCategory.value ? formatRupiah(Number(selectedCategory.value.base_price)) : ''))
const customValid = computed(() => {
  const n = Number(customMinutes.value)
  return customMinutes.value !== '' && Number.isInteger(n) && n >= 15 && n <= 240
})
const durationValid = computed(() => durationChoice.value !== 'custom' || customValid.value)
const duration = computed(() => (durationChoice.value === 'custom' ? Number(customMinutes.value) : Number(durationChoice.value)))
const durationLabel = computed(() => `${duration.value} menit${durationChoice.value === 'custom' ? ' (permintaan khusus)' : ''}`)
const cleanName = (v: string) => v.trim().replace(/\s+/g, ' ')

const errors = computed(() => {
  const e: Record<string, string> = {}
  const c = selectedCountry.value
  if (!selectedCategory.value) {
    e.category = categories.value.length ? 'Pilih satu kategori layanan.' : 'Kategori belum tersedia. Muat ulang daftar kategori.'
  }
  if (!durationValid.value) e.duration = 'Masukkan durasi bulat antara 15 dan 240 menit.'
  const name = cleanName(form.value.name)
  if (name.length < 2 || name.length > 80 || !NAME_RE.test(name)) e.name = 'Masukkan nama lengkap (2–80 karakter, gunakan huruf).'
  if (!form.value.phone.trim()) e.phone = 'Masukkan nomor WhatsApp Anda.'
  else if (!phoneValid.value) e.phone = `Nomor ${c.name} diawali angka ${c.lead} dan berisi ${c.digits} digit, tanpa kode negara.`
  if (!form.value.date) e.date = 'Pilih tanggal yang Anda inginkan.'
  else if (form.value.date < today.value) e.date = 'Tanggal tidak boleh sudah lewat.'
  if (!form.value.time) e.time = 'Pilih waktu yang paling nyaman.'
  else if (form.value.date === today.value && toMinutes(form.value.time) <= nowMinutes.value) {
    e.time = 'Waktu ini sudah lewat. Pilih waktu berikutnya atau tanggal lain.'
  }
  return e
})
const touch = (k: string) => { touched.value[k] = true }
const shown = (k: string) => (touched.value[k] ? errors.value[k] ?? '' : '')

const summaryRows = computed(() => [
  {
    label: 'Layanan',
    value: `${form.value.consultation}${selectedCategory.value ? ` · ${selectedCategory.value.name}` : ''}`,
    hint: '',
  },
  { label: 'Durasi', value: durationValid.value ? `${duration.value} menit` : '', hint: '' },
  {
    label: 'Jadwal',
    value: form.value.date ? `${shortDate(form.value.date)}${form.value.time ? `, ${form.value.time} WIB` : ''}` : '',
    hint: '',
  },
  {
    label: 'Kontak',
    value: cleanName(form.value.name),
    hint: phoneValid.value ? phoneDisplay.value : '',
  },
])
const miniSummary = computed(() =>
  selectedCategory.value ? `${selectedCategory.value.name} · ${durationValid.value ? duration.value : '–'} menit · ${categoryPrice.value}` : '',
)
const hasDraft = computed(() =>
  !!(form.value.name || form.value.phone || form.value.complaint || form.value.categoryId || form.value.date || form.value.time || topics.value.length),
)
const nextLabel = computed(() => (editingFromReview.value ? 'Simpan perubahan' : step.value === 3 ? 'Tinjau pengajuan' : 'Lanjut'))

/* ───────────────────────── Pesan WhatsApp ───────────────────────── */

const message = computed(() => [
  'Halo Rumah Nafasy, saya ingin mengajukan konsultasi psikologi.', '',
  '*Data Pemohon*', `Nama: ${cleanName(form.value.name)}`, `Nomor WhatsApp: ${normalizedPhone.value}`, '',
  '*Rencana Konsultasi*', `Jenis: ${form.value.consultation}`,
  `Kategori: ${selectedCategory.value?.name ?? '-'}`,
  `Acuan biaya: ${categoryPrice.value || 'Dikonfirmasi dengan psikolog'}`,
  ...(topics.value.length ? [`Topik: ${topics.value.join(', ')}`] : []),
  `Durasi: ${durationLabel.value}`,
  `Tanggal yang diinginkan: ${longDate(form.value.date)}`,
  `Waktu yang diinginkan: ${form.value.time} WIB`, '',
  '*Keluhan / catatan:*', form.value.complaint.trim() || 'Belum ada catatan tambahan.', '',
  'Mohon konfirmasi ketersediaan jadwal dan informasi selanjutnya. Terima kasih.',
].join('\n'))
const previewLines = computed(() =>
  message.value.split('\n').map(t => (/^\*.+\*$/.test(t) ? { text: t.slice(1, -1), bold: true } : { text: t, bold: false })),
)
const whatsappUrl = computed(() => `https://wa.me/${WA_NUMBER}?text=${encodeURIComponent(message.value)}`)

/* ───────────────────────── Aksi ───────────────────────── */

const smooth = (): ScrollBehavior => (window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth')

async function loadCatalog() {
  catalogLoading.value = true
  catalogError.value = false
  try {
    const response = await apiFetch('public/categories')
    categories.value = (response?.data ?? []) as Category[]
  } catch {
    catalogError.value = true
  } finally {
    catalogLoading.value = false
    if (!selectedCategory.value) { form.value.categoryId = ''; step.value = 1 }
  }
}

async function afterStepChange() {
  await nextTick()
  headingRef.value?.focus({ preventScroll: true })
  contentRef.value?.scrollIntoView({ behavior: smooth(), block: 'start' })
}
function goTo(n: number, dir: 'forward' | 'back') {
  direction.value = dir
  step.value = n
  afterStepChange()
}
async function focusField(f: string) {
  await nextTick()
  const el = document.getElementById(FIELD_IDS[f])
  el?.focus({ preventScroll: true })
  el?.scrollIntoView({ behavior: smooth(), block: 'center' })
}

function next() {
  const fields = stepFields[step.value] ?? []
  fields.forEach(touch)
  const bad = fields.find(f => errors.value[f])
  if (bad) { focusField(bad); return }
  if (step.value === 3) form.value.name = cleanName(form.value.name)
  if (editingFromReview.value) { editingFromReview.value = false; goTo(4, 'forward'); return }
  if (step.value < 4) goTo(step.value + 1, 'forward')
}
function back() {
  if (step.value > 1) goTo(step.value - 1, 'back')
  else router.push('/')
}
function goBackTo(n: number) { editingFromReview.value = false; goTo(n, 'back') }
function edit(n: number) { editingFromReview.value = true; goTo(n, 'back') }
function cancelEdit() { editingFromReview.value = false; goTo(4, 'forward') }

function setDate(iso: string) {
  form.value.date = iso
  calendarPlaceholder.value = parseDate(iso)
  touch('date')
}
function onPickDate(value: DateValue | undefined) {
  form.value.date = value?.toString() ?? ''
  touch('date')
}

async function copyMessage() {
  try {
    await navigator.clipboard.writeText(message.value)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = message.value
    ta.style.position = 'fixed'
    ta.style.opacity = '0'
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    ta.remove()
  }
  copied.value = true
  window.clearTimeout(copyTimer)
  copyTimer = window.setTimeout(() => { copied.value = false }, 2200)
}

function requestReset() {
  if (!confirmReset.value) {
    confirmReset.value = true
    resetTimer = window.setTimeout(() => { confirmReset.value = false }, 4000)
    return
  }
  window.clearTimeout(resetTimer)
  confirmReset.value = false
  form.value = defaultForm()
  topics.value = []
  durationChoice.value = '60'
  customMinutes.value = ''
  touched.value = {}
  editingFromReview.value = false
  calendarPlaceholder.value = parseDate(today.value)
  try { sessionStorage.removeItem(DRAFT_KEY) } catch { /* abaikan */ }
  goTo(1, 'back')
}

watch([form, topics, durationChoice, customMinutes, step], saveDraft, { deep: true })
watch(durationChoice, v => {
  if (v === 'custom') nextTick(() => document.getElementById('custom-minutes')?.focus())
})

onMounted(async () => {
  clockTimer = window.setInterval(() => { now.value = new Date() }, 30_000)
  await loadCatalog()
  if (form.value.date && form.value.date < today.value) {
    form.value.date = ''
    form.value.time = ''
    if (step.value === 4) step.value = 3
  }
})
onBeforeUnmount(() => {
  window.clearInterval(clockTimer)
  window.clearTimeout(resetTimer)
  window.clearTimeout(copyTimer)
})
</script>

<template>
  <Navbar
    :brand="{ name: 'Rumah Nafasy', href: '/', mark: '', ariaLabel: 'Beranda Rumah Nafasy' }"
    :links="navigationLinks"
  />

  <main class="page">
    <div class="layout">
      <!-- Ringkasan hidup: terisi seiring pengguna melangkah -->
      <aside class="summary" aria-labelledby="summary-title">
        <h2 id="summary-title">Ringkasan pengajuan</h2>
        <dl class="summary-list">
          <div v-for="row in summaryRows" :key="row.label">
            <dt>{{ row.label }}</dt>
            <dd>
              <template v-if="row.value">
                {{ row.value }}<small v-if="row.hint">{{ row.hint }}</small>
              </template>
              <span v-else class="placeholder">Belum diisi</span>
            </dd>
          </div>
        </dl>

        <div class="price-block">
          <template v-if="selectedCategory">
            <span class="price-label">Acuan biaya</span>
            <strong class="price-value">{{ categoryPrice }}</strong>
            <small>per sesi 60 menit. Biaya akhir dikonfirmasi psikolog.</small>
          </template>
          <small v-else>Pilih kategori untuk melihat acuan biaya.</small>
        </div>

        <ul class="trust">
          <li><Icon name="lock" :size="16" /><span><strong>Privasi Anda dihargai.</strong> Catatan hanya menjadi draf WhatsApp yang Anda tinjau sendiri.</span></li>
          <li><Icon name="shield" :size="16" /><span><strong>Tanpa pembayaran di sini.</strong> Biaya dikonfirmasi bersama psikolog.</span></li>
        </ul>

        <button v-if="hasDraft" type="button" class="text-btn" @click="requestReset">
          {{ confirmReset ? 'Yakin hapus semua isian? Klik lagi' : 'Hapus draf di tab ini' }}
        </button>
      </aside>

      <section ref="contentRef" class="content">
        <button class="back-link" type="button" @click="back">
          <Icon name="arrow-left" :size="16" /> {{ step === 1 ? 'Kembali ke beranda' : 'Kembali' }}
        </button>

        <header class="heading">
          <p class="step-meta">Langkah {{ step }} dari 4</p>
          <h1 ref="headingRef" tabindex="-1">{{ stepTitles[step - 1] }}</h1>
          <p class="intro">{{ stepDescriptions[step - 1] }}</p>
        </header>

        <ol class="stepper" aria-label="Progres pengajuan">
          <li
            v-for="(label, i) in stepLabels" :key="label" class="step"
            :class="{ done: step > i + 1, current: step === i + 1 }"
            :aria-current="step === i + 1 ? 'step' : undefined"
          >
            <button v-if="step > i + 1" type="button" class="step-btn" @click="goBackTo(i + 1)">
              <span class="step-dot"><Icon name="check" :size="14" /></span>
              <span class="step-label">{{ label }}</span>
              <span class="sr-only">, selesai. Kembali ke langkah ini</span>
            </button>
            <span v-else class="step-btn">
              <span class="step-dot">{{ i + 1 }}</span>
              <span class="step-label">{{ label }}</span>
            </span>
          </li>
        </ol>

        <Transition :name="direction === 'forward' ? 'step-fwd' : 'step-back'" mode="out-in">
          <!-- ───────── Langkah 1: layanan ───────── -->
          <section v-if="step === 1" key="s1" class="panel">
            <fieldset class="group">
              <legend>Cara konsultasi</legend>
              <div class="grid-2">
                <label v-for="kind in consultationKinds" :key="kind.value" class="opt">
                  <input v-model="form.consultation" type="radio" name="consultation" :value="kind.value" />
                  <span class="face card">
                    <span class="icon-tile"><Icon :name="kind.icon" :size="20" /></span>
                    <span class="copy"><strong>{{ kind.value }}</strong><small>{{ kind.note }}</small></span>
                    <span class="dot" aria-hidden="true" />
                  </span>
                </label>
              </div>
            </fieldset>

            <fieldset id="category-group" class="group" tabindex="-1">
              <legend>Kategori layanan</legend>
              <p class="group-hint">Harga acuan untuk satu sesi 60 menit.</p>

              <div v-if="catalogLoading" class="grid-2" aria-busy="true" aria-label="Memuat kategori">
                <span v-for="n in 4" :key="n" class="skeleton" />
              </div>
              <div v-else-if="catalogError" class="notice error" role="alert">
                <p>Kategori dan harga belum bisa dimuat. Periksa koneksi Anda, lalu coba lagi.</p>
                <button type="button" class="btn secondary small" @click="loadCatalog"><Icon name="refresh" :size="16" /> Muat ulang</button>
              </div>
              <p v-else-if="!categories.length" class="notice">Belum ada kategori layanan yang tersedia.</p>
              <div v-else class="grid-2">
                <label v-for="item in categories" :key="item.id" class="opt">
                  <input v-model="form.categoryId" type="radio" name="category" :value="String(item.id)" />
                  <span class="face category">
                    <strong>{{ item.name }}</strong>
                    <span class="price">{{ formatRupiah(Number(item.base_price)) }} <small>/ sesi</small></span>
                    <span class="dot" aria-hidden="true" />
                  </span>
                </label>
              </div>
              <p v-if="shown('category')" class="field-msg error" role="alert">{{ shown('category') }}</p>
            </fieldset>

            <fieldset class="group">
              <legend>Durasi sesi</legend>
              <div class="grid-4">
                <label v-for="opt in durationOptions" :key="opt.value" class="opt">
                  <input v-model="durationChoice" type="radio" name="duration" :value="opt.value" />
                  <span class="face seg">
                    <strong>{{ opt.title }}<small v-if="opt.unit"> {{ opt.unit }}</small></strong>
                    <small>{{ opt.note }}</small>
                  </span>
                </label>
              </div>

              <div v-if="durationChoice === 'custom'" class="custom-row">
                <label class="field-label" for="custom-minutes">Durasi yang Anda butuhkan</label>
                <div class="input-suffix">
                  <input
                    id="custom-minutes" v-model.number="customMinutes" class="field" :class="{ invalid: shown('duration') }"
                    type="number" inputmode="numeric" min="15" max="240" step="5" placeholder="45"
                    :aria-invalid="!!shown('duration')" aria-describedby="custom-msg" @blur="touch('duration')"
                  />
                  <span>menit</span>
                </div>
                <p id="custom-msg" class="field-msg" :class="{ error: shown('duration') }" aria-live="polite">
                  {{ shown('duration') || 'Antara 15 dan 240 menit. Dikirim sebagai permintaan khusus.' }}
                </p>
              </div>
            </fieldset>
          </section>

          <!-- ───────── Langkah 2: kebutuhan ───────── -->
          <section v-else-if="step === 2" key="s2" class="panel">
            <div class="section-head">
              <div>
                <h2>Apa yang ingin Anda bicarakan?</h2>
                <p>Tulis seperlunya. Tidak perlu mencari kata-kata yang sempurna.</p>
              </div>
              <span class="badge">Opsional</span>
            </div>

            <fieldset class="group">
              <legend class="sub">Topik yang paling dekat <small>(boleh lebih dari satu)</small></legend>
              <div class="chips">
                <label v-for="t in topicOptions" :key="t" class="opt">
                  <input v-model="topics" type="checkbox" :value="t" />
                  <span class="face chip"><Icon name="check" :size="14" class="chip-check" />{{ t }}</span>
                </label>
              </div>
            </fieldset>

            <div class="field-block">
              <label class="field-label" for="complaint">Cerita singkat atau catatan</label>
              <textarea
                id="complaint" v-model="form.complaint" class="field textarea" rows="7" maxlength="2000"
                placeholder="Contoh: Dua bulan terakhir saya sulit tidur dan sering cemas menjelang kerja. Saya sudah mencoba mengurangi kopi, tetapi belum membantu."
                aria-describedby="complaint-help"
              />
              <div class="field-foot">
                <span id="complaint-help" class="field-help">Boleh ceritakan sejak kapan, kapan muncul, dampaknya, dan apa yang sudah dicoba.</span>
                <span class="counter" :class="{ near: form.complaint.length > 1800 }">{{ form.complaint.length }}/2000</span>
              </div>
            </div>

            <p class="privacy"><Icon name="lock" :size="15" /> Catatan ini hanya masuk ke draf WhatsApp yang bisa Anda tinjau sebelum dikirim.</p>

            <aside class="support" role="note">
              <Icon name="heart" :size="20" />
              <p>
                <strong>Butuh bantuan segera?</strong>
                Formulir ini bukan layanan darurat. Jika Anda di Indonesia dan sedang berada dalam krisis atau terpikir menyakiti diri sendiri,
                hubungi Healing119 dari Kemenkes di 119 ekstensi 8 atau kunjungi
                <a href="https://healing119.id" target="_blank" rel="noopener noreferrer">healing119.id</a>.
              </p>
            </aside>
          </section>

          <!-- ───────── Langkah 3: kontak & jadwal ───────── -->
          <section v-else-if="step === 3" key="s3" class="panel">
            <div class="field-block first">
              <label class="field-label" for="name">Nama lengkap</label>
              <input
                id="name" v-model="form.name" class="field" :class="{ invalid: shown('name') }"
                autocomplete="name" maxlength="80" placeholder="Contoh: Siti Nurhaliza"
                :aria-invalid="!!shown('name')" aria-describedby="name-msg"
                @blur="touch('name')"
              />
              <p id="name-msg" class="field-msg" :class="{ error: shown('name') }" aria-live="polite">
                {{ shown('name') || 'Gunakan nama yang mudah kami panggil.' }}
              </p>
            </div>

            <div class="field-block">
              <label class="field-label" for="phone">Nomor WhatsApp</label>
              <div class="phone-row" :class="{ invalid: shown('phone') }">
                <select v-model="form.country" class="field country" aria-label="Kode negara WhatsApp">
                  <option v-for="c in countries" :key="c.code" :value="c.code">{{ c.name }} ({{ c.dial }})</option>
                </select>
                <input
                  id="phone" v-model="form.phone" class="field" :class="{ invalid: shown('phone') }"
                  type="tel" inputmode="tel" autocomplete="tel-national" :placeholder="selectedCountry.example"
                  :aria-invalid="!!shown('phone')" aria-describedby="phone-msg"
                  @blur="touch('phone')"
                />
              </div>
              <p id="phone-msg" class="field-msg" :class="{ error: shown('phone'), ok: !shown('phone') && phoneValid }" aria-live="polite">
                <template v-if="shown('phone')">{{ shown('phone') }}</template>
                <template v-else-if="phoneValid"><Icon name="check" :size="14" /> Akan ditulis sebagai {{ phoneDisplay }}</template>
                <template v-else>Tanpa kode negara. Contoh: {{ selectedCountry.example }}</template>
              </p>
            </div>

            <div class="schedule">
              <fieldset id="date-group" class="group" tabindex="-1">
                <legend>Tanggal pilihan</legend>
                <div class="quick">
                  <button
                    v-for="q in quickDates" :key="q.value" type="button" class="pill"
                    :class="{ active: form.date === q.value }" :aria-pressed="form.date === q.value" @click="setDate(q.value)"
                  >{{ q.label }} <small>{{ q.short }}</small></button>
                </div>
                <CalendarRoot
                  v-model:placeholder="calendarPlaceholder"
                  :model-value="form.date ? parseDate(form.date) : undefined"
                  :min-value="todayDate"
                  locale="id-ID"
                  weekday-format="short"
                  calendar-label="Pilih tanggal konsultasi"
                  :week-starts-on="1"
                  fixed-weeks
                  prevent-deselect
                  class="calendar"
                  @update:model-value="onPickDate"
                >
                  <CalendarHeader class="calendar-header">
                    <CalendarPrev class="calendar-nav" aria-label="Bulan sebelumnya">‹</CalendarPrev>
                    <CalendarHeading class="calendar-heading" />
                    <CalendarNext class="calendar-nav" aria-label="Bulan berikutnya">›</CalendarNext>
                  </CalendarHeader>
                  <CalendarGrid v-slot="{ grid, weekDays }" class="calendar-grid">
                    <template v-for="month in grid" :key="month.value.toString()">
                      <CalendarGridHead>
                        <CalendarGridRow>
                          <CalendarHeadCell v-for="day in weekDays" :key="day" class="calendar-weekday">{{ day }}</CalendarHeadCell>
                        </CalendarGridRow>
                      </CalendarGridHead>
                      <CalendarGridBody>
                        <CalendarGridRow v-for="(week, weekIndex) in month.rows" :key="weekIndex">
                          <CalendarCell v-for="day in week" :key="day.toString()" :date="day" class="calendar-cell">
                            <CalendarCellTrigger :day="day" :month="month.value" class="calendar-day" />
                          </CalendarCell>
                        </CalendarGridRow>
                      </CalendarGridBody>
                    </template>
                  </CalendarGrid>
                </CalendarRoot>
                <p v-if="shown('date')" class="field-msg error" role="alert">{{ shown('date') }}</p>
              </fieldset>

              <fieldset id="time-group" class="group" tabindex="-1">
                <legend>Waktu pilihan <small>WIB</small></legend>
                <div v-for="g in timeGroups" :key="g.label" class="slot-group">
                  <p class="slot-label">{{ g.label }}</p>
                  <div class="slots">
                    <label v-for="s in g.slots" :key="s" class="opt">
                      <input v-model="form.time" type="radio" name="time" :value="s" :disabled="isSlotPast(s)" @change="touch('time')" />
                      <span class="face slot">{{ s }}</span>
                    </label>
                  </div>
                </div>
                <div class="custom-time">
                  <label class="field-label" for="custom-time">Atau pilih waktu lain</label>
                  <input id="custom-time" v-model="form.time" class="field" type="time" @blur="touch('time')" />
                </div>
                <p v-if="shown('time')" class="field-msg error" role="alert">{{ shown('time') }}</p>
              </fieldset>
            </div>

            <p class="schedule-note" aria-live="polite">
              <template v-if="form.date && form.time">
                <Icon name="check" :size="15" /> {{ longDate(form.date) }}, pukul {{ form.time }} WIB
              </template>
              <template v-else>Ini waktu perkiraan. Jadwal final dikonfirmasi tim melalui WhatsApp.</template>
            </p>
          </section>

          <!-- ───────── Langkah 4: tinjau ───────── -->
          <section v-else key="s4" class="panel review">
            <dl class="review-list">
              <div class="review-row">
                <dt>Layanan</dt>
                <dd>
                  {{ form.consultation }} · {{ selectedCategory?.name }}
                  <small>{{ durationLabel }}<template v-if="categoryPrice"> · acuan {{ categoryPrice }} / sesi 60 menit</template></small>
                </dd>
                <button type="button" class="edit" @click="edit(1)"><Icon name="edit" :size="14" /> Ubah<span class="sr-only"> layanan</span></button>
              </div>
              <div class="review-row">
                <dt>Jadwal</dt>
                <dd>{{ longDate(form.date) }}<small>Pukul {{ form.time }} WIB · menunggu konfirmasi tim</small></dd>
                <button type="button" class="edit" @click="edit(3)"><Icon name="edit" :size="14" /> Ubah<span class="sr-only"> jadwal</span></button>
              </div>
              <div class="review-row">
                <dt>Pemohon</dt>
                <dd>{{ cleanName(form.name) }}<small>{{ phoneDisplay }}</small></dd>
                <button type="button" class="edit" @click="edit(3)"><Icon name="edit" :size="14" /> Ubah<span class="sr-only"> data pemohon</span></button>
              </div>
              <div class="review-row">
                <dt>Catatan</dt>
                <dd class="wrap">
                  <template v-if="topics.length">{{ topics.join(', ') }}<br /></template>
                  {{ form.complaint.trim() || (topics.length ? '' : 'Belum ada catatan tambahan.') }}
                </dd>
                <button type="button" class="edit" @click="edit(2)"><Icon name="edit" :size="14" /> Ubah<span class="sr-only"> catatan</span></button>
              </div>
            </dl>

            <div class="wa-preview">
              <div class="wa-head">
                <span><Icon name="whatsapp" :size="16" /> Pesan yang akan terbuka di WhatsApp</span>
                <button type="button" class="text-btn" @click="copyMessage">
                  <Icon :name="copied ? 'check' : 'copy'" :size="14" /> {{ copied ? 'Tersalin' : 'Salin pesan' }}
                </button>
              </div>
              <div class="bubble">
                <p v-for="(line, i) in previewLines" :key="i" :class="{ bold: line.bold, gap: !line.text }">{{ line.text }}</p>
              </div>
              <span class="sr-only" aria-live="polite">{{ copied ? 'Pesan disalin' : '' }}</span>
            </div>
            <p class="privacy">WhatsApp akan terbuka dengan pesan ini sebagai draf. Periksa kembali, lalu kirim jika sudah siap.</p>
          </section>
        </Transition>

        <footer class="actions">
          <p v-if="miniSummary && step < 4" class="mini">{{ miniSummary }}</p>
          <div class="actions-row">
            <button v-if="editingFromReview" type="button" class="btn secondary" @click="cancelEdit">Batal</button>
            <button v-else-if="step > 1" type="button" class="btn secondary" @click="back"><Icon name="arrow-left" :size="18" /> Kembali</button>
            <span v-else />
            <button v-if="step < 4" type="button" class="btn primary" @click="next">{{ nextLabel }} <Icon name="arrow-right" :size="18" /></button>
            <a v-else class="btn primary" :href="whatsappUrl" target="_blank" rel="noopener noreferrer"><Icon name="whatsapp" :size="18" /> Buka WhatsApp</a>
          </div>
        </footer>
        <p class="footnote">Dengan melanjutkan, Anda hanya mengirim permohonan jadwal. Belum ada pembayaran.</p>
      </section>
    </div>
  </main>
</template>

<style scoped>
/* ── Token lokal: turunan dari variabel tema yang sudah ada ── */
.page {
  --r-lg: 20px;
  --r-md: 14px;
  --accent-soft: color-mix(in srgb, var(--accent) 8%, var(--surface));
  --ring: color-mix(in srgb, var(--accent) 26%, transparent);
  --danger: #b42318;
  min-height: 100svh;
  color: var(--text);
  background: var(--background);
  font-size: 15px;
  line-height: 1.55;
}
.sr-only {
  position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0;
  overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; border: 0;
}
fieldset { min-width: 0; margin: 0; padding: 0; border: 0; }
fieldset:focus { outline: none; }
legend { padding: 0; margin-bottom: 4px; color: var(--ink); font-size: 15px; font-weight: 700; letter-spacing: -0.015em; }
legend small { color: var(--muted); font-weight: 400; }
legend.sub { font-size: 14px; }

/* ── Layout ── */
.layout {
  display: grid;
  grid-template-columns: minmax(280px, 340px) minmax(0, 700px);
  justify-content: center;
  gap: clamp(32px, 6vw, 88px);
  max-width: 1180px;
  margin: 0 auto;
  padding: 96px 24px 88px;
}
.content { min-width: 0; scroll-margin-top: 84px; }

/* ── Ringkasan (kiri) ── */
.summary {
  position: sticky; top: 92px; align-self: start;
  padding: 24px; border: 1px solid var(--line); border-radius: var(--r-lg); background: var(--surface);
}
.summary h2 { margin: 0 0 18px; color: var(--ink); font-size: 16px; letter-spacing: -0.02em; }
.summary-list { display: grid; gap: 14px; margin: 0; }
.summary-list > div { display: grid; gap: 2px; }
.summary-list dt { color: var(--muted); font-size: 13px; }
.summary-list dd { margin: 0; font-size: 14px; font-weight: 600; overflow-wrap: anywhere; }
.summary-list dd small { display: block; color: var(--muted); font-size: 13px; font-weight: 400; }
.placeholder { color: var(--muted); font-weight: 400; }
.price-block { display: grid; gap: 2px; margin-top: 20px; padding-top: 20px; border-top: 1px dashed var(--line); }
.price-label { color: var(--muted); font-size: 13px; }
.price-value { color: var(--ink); font-family: var(--font-display); font-size: 28px; letter-spacing: -0.045em; line-height: 1.2; }
.price-block small { color: var(--muted); font-size: 13px; }
.trust { display: grid; gap: 14px; margin: 22px 0 0; padding: 20px 0 0; border-top: 1px solid var(--line); list-style: none; }
.trust li { display: flex; gap: 10px; color: var(--muted); font-size: 13px; line-height: 1.5; }
.trust li svg { flex: 0 0 16px; margin-top: 2px; color: var(--accent); }
.trust strong { color: var(--text); font-weight: 600; }
.text-btn {
  display: inline-flex; align-items: center; gap: 6px; margin-top: 18px; padding: 4px 0;
  border: 0; background: none; color: var(--muted); font: inherit; font-size: 13px; cursor: pointer; text-decoration: underline; text-underline-offset: 3px;
}
.text-btn:hover { color: var(--accent); }

/* ── Header & stepper ── */
.back-link {
  display: inline-flex; align-items: center; gap: 6px; min-height: 36px; padding: 0;
  border: 0; background: none; color: var(--muted); font: inherit; font-size: 14px; font-weight: 550; cursor: pointer;
}
.back-link:hover { color: var(--accent); }
.heading { margin-top: 18px; }
.step-meta { margin: 0 0 8px; color: var(--accent); font-size: 14px; font-weight: 650; }
h1 {
  margin: 0; color: var(--ink); font-family: var(--font-display);
  font-size: clamp(29px, 4vw, 38px); line-height: 1.12; letter-spacing: -0.05em;
}
h1:focus { outline: none; }
.intro { margin: 10px 0 0; max-width: 56ch; color: var(--muted); font-size: 15px; }

.stepper { display: grid; grid-template-columns: repeat(4, 1fr); margin: 28px 0 20px; padding: 0; list-style: none; }
.step { position: relative; }
.step:not(:last-child)::after {
  position: absolute; top: 14px; left: 40px; right: 8px; height: 2px; border-radius: 2px; background: var(--line); content: '';
}
.step.done:not(:last-child)::after { background: var(--accent); }
.step-btn {
  display: flex; align-items: center; gap: 8px; padding: 0; border: 0; background: none;
  color: var(--muted); font: inherit; text-align: left;
}
button.step-btn { cursor: pointer; }
.step-dot {
  position: relative; z-index: 1; display: grid; place-items: center; width: 30px; height: 30px; flex: 0 0 30px;
  border: 1.5px solid var(--line); border-radius: 50%; background: var(--background); font-size: 13px; font-weight: 650;
}
.step-label { position: relative; z-index: 1; padding-right: 6px; background: var(--background); font-size: 13px; }
.step.current .step-dot { border-color: var(--accent); color: var(--accent); box-shadow: 0 0 0 4px var(--ring); }
.step.current .step-label { color: var(--text); font-weight: 650; }
.step.done .step-dot { border-color: var(--accent); background: var(--accent); color: #fff; }
.step.done .step-label { color: var(--text); }

/* ── Panel & grup ── */
.panel { padding: 28px; border: 1px solid var(--line); border-radius: var(--r-lg); background: var(--surface); }
.group + .group { margin-top: 32px; }
.group-hint { margin: 0; color: var(--muted); font-size: 13px; }
.section-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 22px; }
.section-head h2 { margin: 0; color: var(--ink); font-size: 18px; letter-spacing: -0.03em; }
.section-head p { margin: 4px 0 0; color: var(--muted); font-size: 14px; }
.badge { padding: 4px 10px; border-radius: 99px; background: color-mix(in srgb, var(--muted) 12%, transparent); color: var(--muted); font-size: 12px; font-weight: 600; }

.grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }
.grid-4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; margin-top: 12px; }

/* ── Pilihan (radio/checkbox asli, tampilan kustom) ── */
.opt { position: relative; display: block; }
.opt > input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
.opt > input:disabled { cursor: not-allowed; }
.face {
  display: flex; align-items: center; gap: 12px; min-height: 56px; padding: 14px;
  border: 1.5px solid var(--line); border-radius: var(--r-md); background: var(--background);
  transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
}
.opt:hover > input:not(:disabled) + .face { border-color: color-mix(in srgb, var(--accent) 50%, var(--line)); }
.opt > input:checked + .face { border-color: var(--accent); background: var(--accent-soft); box-shadow: 0 0 0 3px var(--ring); }
.opt > input:focus-visible + .face { outline: 2px solid var(--accent); outline-offset: 3px; }
.opt > input:disabled + .face { opacity: 0.4; }

.dot {
  width: 20px; height: 20px; flex: 0 0 20px; margin-left: auto;
  border: 1.5px solid var(--line); border-radius: 50%; background: var(--surface); transition: border 0.15s;
}
.opt > input:checked + .face .dot { border: 6px solid var(--accent); }

.card .icon-tile {
  display: grid; place-items: center; width: 40px; height: 40px; flex: 0 0 40px; border-radius: 12px;
  background: color-mix(in srgb, var(--accent) 10%, transparent); color: var(--accent);
}
.card .copy { display: flex; flex: 1; flex-direction: column; gap: 2px; min-width: 0; }
.card .copy strong { font-size: 15px; }
.card .copy small { color: var(--muted); font-size: 13px; line-height: 1.4; }

.category { position: relative; flex-direction: column; align-items: flex-start; gap: 6px; min-height: 100px; }
.category strong { padding-right: 28px; font-size: 14px; line-height: 1.35; }
.category .dot { position: absolute; top: 14px; right: 14px; margin: 0; }
.price { font-size: 17px; font-weight: 750; letter-spacing: -0.03em; }
.price small { color: var(--muted); font-size: 13px; font-weight: 450; letter-spacing: 0; }

.seg { flex-direction: column; align-items: flex-start; justify-content: center; gap: 2px; min-height: 64px; padding: 12px; }
.seg strong { font-size: 16px; }
.seg strong small { color: var(--muted); font-size: 12px; font-weight: 500; }
.seg > small { color: var(--muted); font-size: 12.5px; }

.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.chip { gap: 6px; min-height: 42px; padding: 8px 14px; border-radius: 99px; font-size: 14px; }
.chip-check { display: none; color: var(--accent); }
.opt > input:checked + .chip .chip-check { display: block; }

.slot { justify-content: center; min-height: 44px; padding: 8px; border-radius: 12px; font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }

/* ── Field ── */
.field-block { margin-top: 22px; }
.field-block.first { margin-top: 0; }
.field-label { display: block; margin: 0 0 7px; color: var(--text); font-size: 14px; font-weight: 650; }
.field {
  display: block; width: 100%; min-height: 48px; padding: 11px 14px;
  border: 1.5px solid var(--line); border-radius: 12px; background: var(--background); color: var(--text);
  font: inherit; font-size: 16px; /* 16px mencegah zoom otomatis di iOS */
  transition: border-color 0.15s, box-shadow 0.15s;
}
.field:focus { border-color: var(--accent); box-shadow: 0 0 0 4px var(--ring); outline: 2px solid transparent; }
.field::placeholder { color: color-mix(in srgb, var(--muted) 70%, transparent); }
.field.invalid { border-color: var(--danger); }
.textarea { min-height: 180px; resize: vertical; line-height: 1.65; }
.field-msg { display: flex; align-items: center; gap: 6px; min-height: 20px; margin: 6px 0 0; color: var(--muted); font-size: 13px; line-height: 1.45; }
.field-msg.error { color: var(--danger); }
.field-msg.ok { color: var(--accent); }
.field-foot { display: flex; justify-content: space-between; gap: 12px; margin-top: 6px; }
.field-help { color: var(--muted); font-size: 13px; }
.counter { flex: none; color: var(--muted); font-size: 13px; font-variant-numeric: tabular-nums; }
.counter.near { color: var(--danger); }
.privacy { display: flex; align-items: flex-start; gap: 8px; margin: 16px 0 0; color: var(--muted); font-size: 13px; }
.privacy svg { flex: none; margin-top: 2px; color: var(--accent); }

.phone-row { display: grid; grid-template-columns: minmax(150px, 0.8fr) minmax(0, 1.2fr); gap: 8px; }
.phone-row.invalid .country { border-color: var(--danger); }
.country { padding-right: 8px; }
.input-suffix { display: flex; align-items: center; gap: 10px; max-width: 220px; }
.input-suffix span { color: var(--muted); }
.custom-row { margin-top: 18px; padding: 16px; border: 1px dashed var(--line); border-radius: var(--r-md); }

.notice { margin: 12px 0 0; padding: 16px; border: 1px dashed var(--line); border-radius: var(--r-md); color: var(--muted); font-size: 14px; }
.notice.error { display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap; border-color: color-mix(in srgb, var(--danger) 45%, var(--line)); color: var(--danger); }
.notice p { margin: 0; }
.skeleton {
  display: block; min-height: 100px; border-radius: var(--r-md);
  background: linear-gradient(90deg, var(--line), color-mix(in srgb, var(--line) 35%, transparent), var(--line)); background-size: 200% 100%;
  animation: shimmer 1.4s linear infinite;
}
@keyframes shimmer { to { background-position: -200% 0; } }

.support {
  display: flex; gap: 12px; margin-top: 22px; padding: 16px; border-radius: var(--r-md);
  background: color-mix(in srgb, var(--accent) 7%, var(--surface)); border: 1px solid color-mix(in srgb, var(--accent) 20%, var(--line));
}
.support svg { flex: none; margin-top: 2px; color: var(--accent); }
.support p { margin: 0; color: var(--muted); font-size: 13.5px; line-height: 1.6; }
.support strong { display: block; color: var(--text); font-size: 14px; }
.support a { color: var(--accent); font-weight: 600; }

/* ── Jadwal ── */
.schedule { display: grid; grid-template-columns: minmax(300px, 1.05fr) minmax(0, 1fr); gap: 28px; margin-top: 28px; }
.quick { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0 12px; }
.pill {
  display: inline-flex; align-items: baseline; gap: 6px; min-height: 40px; padding: 8px 14px;
  border: 1.5px solid var(--line); border-radius: 99px; background: var(--background); color: var(--text);
  font: inherit; font-size: 14px; font-weight: 600; cursor: pointer; transition: border-color 0.15s, background-color 0.15s;
}
.pill small { color: var(--muted); font-size: 12px; font-weight: 400; }
.pill:hover { border-color: color-mix(in srgb, var(--accent) 50%, var(--line)); }
.pill.active { border-color: var(--accent); background: var(--accent-soft); box-shadow: 0 0 0 3px var(--ring); }
.pill:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }

.calendar { width: 100%; padding: 12px; border: 1px solid var(--line); border-radius: var(--r-md); background: var(--background); }
.calendar-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
.calendar-heading { font-size: 14px; font-weight: 700; text-transform: capitalize; }
.calendar-nav {
  display: grid; place-items: center; width: 36px; height: 36px; border: 1px solid var(--line); border-radius: 10px;
  background: var(--surface); color: var(--text); font: inherit; font-size: 20px; line-height: 1; cursor: pointer;
}
.calendar-nav:hover:not(:disabled) { border-color: var(--accent); }
.calendar-nav:disabled { opacity: 0.35; cursor: not-allowed; }
.calendar-nav:focus-visible, .calendar-day:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.calendar-grid { width: 100%; border-collapse: collapse; }
.calendar-weekday { height: 30px; color: var(--muted); font-size: 12px; font-weight: 600; text-align: center; text-transform: capitalize; }
.calendar-cell { padding: 1px; text-align: center; }
.calendar-day {
  display: grid; place-items: center; width: 100%; max-width: 40px; height: 40px; margin: auto;
  border: 0; border-radius: 10px; background: transparent; color: var(--text); font: inherit; font-size: 14px; cursor: pointer;
  font-variant-numeric: tabular-nums;
}
.calendar-day:hover:not([data-disabled]):not([data-selected]) { background: color-mix(in srgb, var(--accent) 10%, transparent); }
.calendar-day[data-today]:not([data-selected]) { box-shadow: inset 0 0 0 1.5px color-mix(in srgb, var(--accent) 55%, transparent); font-weight: 700; }
.calendar-day[data-selected] { background: var(--accent); color: #fff; font-weight: 700; }
.calendar-day[data-disabled] { opacity: 0.3; cursor: not-allowed; }
.calendar-day[data-outside-view] { visibility: hidden; }

.slot-group + .slot-group { margin-top: 12px; }
.slot-label { margin: 10px 0 6px; color: var(--muted); font-size: 13px; }
.slot-group:first-of-type .slot-label { margin-top: 12px; }
.slots { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px; }
.custom-time { margin-top: 18px; }
.custom-time .field-label { font-size: 13px; font-weight: 550; color: var(--muted); }
.schedule-note { display: flex; align-items: center; gap: 8px; margin: 22px 0 0; padding: 12px 14px; border-radius: 12px; background: var(--accent-soft); color: var(--text); font-size: 14px; }
.schedule-note svg { color: var(--accent); }

/* ── Tinjau ── */
.review-list { margin: 0; }
.review-row { display: grid; grid-template-columns: 96px minmax(0, 1fr) auto; gap: 12px; align-items: start; padding: 16px 0; border-bottom: 1px solid var(--line); }
.review-row:first-child { padding-top: 0; }
.review dt { color: var(--muted); font-size: 13px; }
.review dd { margin: 0; font-size: 14.5px; font-weight: 600; overflow-wrap: anywhere; }
.review dd.wrap { white-space: pre-wrap; font-weight: 500; }
.review dd small { display: block; margin-top: 2px; color: var(--muted); font-size: 13px; font-weight: 400; }
.edit {
  display: inline-flex; align-items: center; gap: 5px; min-height: 36px; padding: 0 10px;
  border: 1px solid var(--line); border-radius: 10px; background: var(--background); color: var(--accent);
  font: inherit; font-size: 13px; font-weight: 650; cursor: pointer;
}
.edit:hover { border-color: var(--accent); }
.edit:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }

.wa-preview { margin-top: 24px; border-radius: var(--r-md); overflow: hidden; background: color-mix(in srgb, var(--muted) 8%, var(--background)); border: 1px solid var(--line); }
.wa-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; background: var(--surface); border-bottom: 1px solid var(--line); font-size: 13px; font-weight: 600; }
.wa-head > span { display: inline-flex; align-items: center; gap: 7px; }
.wa-head svg { color: var(--accent); }
.wa-head .text-btn { margin: 0; font-weight: 600; }
.bubble {
  max-width: 94%; margin: 14px 14px 14px auto; padding: 12px 14px; border-radius: 16px 4px 16px 16px;
  background: color-mix(in srgb, var(--accent) 12%, var(--surface)); font-size: 14px; line-height: 1.5;
  max-height: 340px; overflow: auto;
}
.bubble p { margin: 0; overflow-wrap: anywhere; white-space: pre-wrap; }
.bubble p.bold { font-weight: 700; }
.bubble p.gap { height: 8px; }

/* ── Aksi ── */
.actions { margin-top: 18px; }
.mini { display: none; margin: 0 0 8px; color: var(--muted); font-size: 13px; }
.actions-row { display: flex; justify-content: space-between; gap: 12px; }
.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 10px; min-height: 48px; padding: 0 22px;
  border: 1.5px solid transparent; border-radius: 12px; font: inherit; font-size: 15px; font-weight: 650;
  text-decoration: none; cursor: pointer; transition: background-color 0.15s, border-color 0.15s, transform 0.15s;
}
.btn.small { min-height: 40px; padding: 0 14px; font-size: 14px; }
.btn.primary { background: var(--accent); color: #fff; }
.btn.primary:hover { background: var(--accent-hover); }
.btn.primary:active { transform: translateY(1px); }
.btn.secondary { border-color: var(--line); background: var(--surface); color: var(--text); }
.btn.secondary:hover { border-color: var(--accent); }
.btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
.footnote { margin: 12px 0 0; color: var(--muted); font-size: 13px; text-align: right; }

/* ── Transisi antar langkah: hanya sebagai respons atas aksi pengguna ── */
.step-fwd-enter-active, .step-fwd-leave-active, .step-back-enter-active, .step-back-leave-active { transition: opacity 0.16s ease, transform 0.2s ease; }
.step-fwd-enter-from, .step-back-leave-to { opacity: 0; transform: translateX(14px); }
.step-fwd-leave-to, .step-back-enter-from { opacity: 0; transform: translateX(-14px); }

@media (prefers-reduced-motion: reduce) {
  .page *, .page *::before, .page *::after { transition-duration: 0.01ms !important; animation: none !important; }
  .step-fwd-enter-from, .step-fwd-leave-to, .step-back-enter-from, .step-back-leave-to { transform: none; }
}

/* ── Responsif ── */
@media (max-width: 860px) {
  .layout { grid-template-columns: minmax(0, 1fr); max-width: 700px; gap: 0; padding: 80px 20px 0; }
  .summary { display: none; }
  .mini { display: block; }
  .actions {
    position: sticky; bottom: 0; z-index: 20; margin: 20px -20px 0;
    padding: 12px 20px calc(12px + env(safe-area-inset-bottom));
    border-top: 1px solid var(--line); background: color-mix(in srgb, var(--background) 92%, transparent); backdrop-filter: blur(12px);
  }
  .actions-row .btn.primary { flex: 1; }
  .footnote { margin: 12px 0 28px; text-align: left; }
}
@media (max-width: 640px) {
  .schedule { grid-template-columns: minmax(0, 1fr); gap: 24px; }
  .grid-4 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 520px) {
  .panel { padding: 20px 16px; }
  .layout { padding-right: 16px; padding-left: 16px; }
  .actions { margin-right: -16px; margin-left: -16px; padding-right: 16px; padding-left: 16px; }
  .stepper { margin: 22px 0 16px; }
  .step-label { display: none; }
  .step:not(:last-child)::after { left: 36px; right: 6px; }
  .grid-2 { grid-template-columns: minmax(0, 1fr); }
  .grid-2:has(.category) { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
  .category { min-height: 92px; padding: 12px; }
  .phone-row { grid-template-columns: minmax(0, 1fr); }
  .review-row { grid-template-columns: minmax(0, 1fr) auto; }
  .review-row dt { grid-column: 1 / -1; }
  .review-row dd { grid-column: 1; }
  .review-row .edit { grid-column: 2; grid-row: 2; }
}
</style>