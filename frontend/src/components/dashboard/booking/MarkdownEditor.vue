<script setup lang="ts">
import { ref, computed } from 'vue'
import { marked } from 'marked'
import DOMPurify from 'dompurify'
import {
  BoldIcon,
  ItalicIcon,
  H2Icon,
  ListBulletIcon,
  NumberedListIcon,
} from '@heroicons/vue/24/outline'

/**
 * Editor Markdown ringan untuk keluhan pasien.
 * - Mode tulis (textarea + toolbar) dan pratinjau hasil render.
 * - Output HTML selalu disanitasi DOMPurify (aman dari XSS).
 * - v-model berisi Markdown mentah (disimpan mentah di backend).
 */

const props = withDefaults(
  defineProps<{
    modelValue?: string
    placeholder?: string
    maxlength?: number
    rows?: number
    disabled?: boolean
  }>(),
  {
    modelValue: '',
    placeholder: 'Tuliskan keluhan Anda…',
    maxlength: 10000,
    rows: 10,
    disabled: false,
  },
)

const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>()

const mode = ref<'write' | 'preview'>('write')
const textarea = ref<HTMLTextAreaElement | null>(null)

const content = computed({
  get: () => props.modelValue ?? '',
  set: (v: string) => emit('update:modelValue', v.slice(0, props.maxlength)),
})

const charCount = computed(() => content.value.length)

/** HTML hasil render — disanitasi sebelum masuk v-html. */
const renderedHtml = computed(() => {
  if (!content.value.trim()) return ''
  const raw = marked.parse(content.value, { async: false, gfm: true, breaks: true })
  return DOMPurify.sanitize(raw, { USE_PROFILES: { html: true } })
})

/** Sisipkan syntax Markdown di sekitar seleksi kursor. */
function wrapSelection(before: string, after = before) {
  const el = textarea.value
  if (!el || props.disabled) return
  const start = el.selectionStart
  const end = el.selectionEnd
  const selected = content.value.slice(start, end)
  const already = content.value.slice(start - before.length, start) === before
    && content.value.slice(end, end + after.length) === after
  let next: string
  let cursor: number
  if (already) {
    // Toggle off: lepaskan pembungkus yang sudah ada
    next = content.value.slice(0, start - before.length) + selected + content.value.slice(end + after.length)
    cursor = end - before.length
  } else {
    next = content.value.slice(0, start) + before + (selected || '') + after + content.value.slice(end)
    cursor = start + before.length + selected.length + after.length
  }
  content.value = next
  requestAnimationFrame(() => {
    el.focus()
    el.setSelectionRange(cursor, cursor)
  })
}

/** Prefix setiap baris terpilih (heading / list / numbered list). */
function prefixLines(prefix: string) {
  const el = textarea.value
  if (!el || props.disabled) return
  const start = el.selectionStart
  const end = el.selectionEnd
  const lineStart = content.value.lastIndexOf('\n', start - 1) + 1
  let lineEnd = content.value.indexOf('\n', end)
  if (lineEnd === -1) lineEnd = content.value.length
  const block = content.value.slice(lineStart, lineEnd)
  const numbered = prefix === '1. '
  const replaced = block
    .split('\n')
    .map((line, i) => {
      const p = numbered ? `${i + 1}. ` : prefix
      const stripped = line.replace(/^(#{1,4}\s|[-*]\s|\d+\.\s)/, '')
      return line.trim() === '' ? line : p + stripped
    })
    .join('\n')
  content.value = content.value.slice(0, lineStart) + replaced + content.value.slice(lineEnd)
  requestAnimationFrame(() => {
    el.focus()
    el.setSelectionRange(lineStart, lineStart + replaced.length)
  })
}

function onKeydown(e: KeyboardEvent) {
  if (props.disabled) return
  if (!(e.ctrlKey || e.metaKey)) return
  const k = e.key.toLowerCase()
  if (k === 'b') { e.preventDefault(); wrapSelection('**') }
  else if (k === 'i') { e.preventDefault(); wrapSelection('*') }
}

const tools = [
  { icon: BoldIcon, label: 'Tebal (Ctrl+B)', action: () => wrapSelection('**') },
  { icon: ItalicIcon, label: 'Miring (Ctrl+I)', action: () => wrapSelection('*') },
  { icon: H2Icon, label: 'Judul bagian', action: () => prefixLines('## ') },
  { icon: ListBulletIcon, label: 'Daftar poin', action: () => prefixLines('- ') },
  { icon: NumberedListIcon, label: 'Daftar bernomor', action: () => prefixLines('1. ') },
]
</script>

<template>
  <div
    class="overflow-hidden rounded-xl border bg-[var(--surface)] transition-colors"
    :class="disabled ? 'border-[var(--line)] opacity-70' : 'border-[var(--line)] focus-within:border-[var(--accent)]/60'"
  >
    <!-- Toolbar + tab -->
    <div class="flex items-center justify-between gap-2 border-b border-[var(--line)] bg-[var(--muted)]/4 px-2 py-1.5">
      <div v-if="mode === 'write'" class="flex items-center gap-0.5">
        <button
          v-for="t in tools"
          :key="t.label"
          type="button"
          :title="t.label"
          :aria-label="t.label"
          :disabled="disabled"
          class="flex h-7 w-7 items-center justify-center rounded-md text-[var(--muted)] transition-colors hover:bg-[var(--muted)]/10 hover:text-[var(--text)] disabled:opacity-40"
          @click="t.action()"
        >
          <component :is="t.icon" class="h-4 w-4" />
        </button>
      </div>
      <div v-else class="px-1.5 text-[11px] font-medium text-[var(--muted)]">
        Pratinjau
      </div>

      <div class="flex items-center gap-0.5 rounded-lg bg-[var(--muted)]/8 p-0.5">
        <button
          type="button"
          class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors"
          :class="mode === 'write' ? 'bg-[var(--surface)] text-[var(--text)] shadow-sm' : 'text-[var(--muted)] hover:text-[var(--text)]'"
          @click="mode = 'write'"
        >
          Tulis
        </button>
        <button
          type="button"
          class="rounded-md px-2.5 py-1 text-[11px] font-medium transition-colors"
          :class="mode === 'preview' ? 'bg-[var(--surface)] text-[var(--text)] shadow-sm' : 'text-[var(--muted)] hover:text-[var(--text)]'"
          @click="mode = 'preview'"
        >
          Pratinjau
        </button>
      </div>
    </div>

    <!-- Body -->
    <textarea
      v-if="mode === 'write'"
      ref="textarea"
      :value="content"
      :rows="rows"
      :placeholder="placeholder"
      :disabled="disabled"
      :maxlength="maxlength"
      class="block w-full resize-y bg-transparent px-3.5 py-3 text-sm leading-relaxed text-[var(--text)] outline-none placeholder:text-[var(--muted)]/60"
      @input="content = ($event.target as HTMLTextAreaElement).value"
      @keydown="onKeydown"
    />

    <!-- Pratinjau hasil render (sudah disanitasi) -->
    <div
      v-else
      class="markdown-body min-h-[120px] px-3.5 py-3 text-sm leading-relaxed text-[var(--text)]"
    >
      <template v-if="content.trim()">
        <!-- eslint-disable-next-line vue/no-v-html — sudah disanitasi DOMPurify -->
        <div v-html="renderedHtml" />
      </template>
      <p v-else class="text-xs text-[var(--muted)]">
        Belum ada isi — beralih ke mode Tulis untuk menulis keluhan.
      </p>
    </div>

    <!-- Footer -->
    <div class="flex items-center justify-between gap-3 border-t border-[var(--line)] bg-[var(--muted)]/4 px-3 py-1.5">
      <p class="text-[10px] text-[var(--muted)]">
        Markdown didukung: <code class="rounded bg-[var(--muted)]/10 px-1">**tebal**</code>
        <code class="rounded bg-[var(--muted)]/10 px-1">*miring*</code>
        <code class="rounded bg-[var(--muted)]/10 px-1">## judul</code>
        <code class="rounded bg-[var(--muted)]/10 px-1">- daftar</code>
      </p>
      <span
        class="shrink-0 text-[10px] tabular-nums"
        :class="charCount >= maxlength ? 'text-rose-500' : 'text-[var(--muted)]'"
      >
        {{ charCount }}/{{ maxlength }}
      </span>
    </div>
  </div>
</template>

<style scoped>
/* Render Markdown: tipografi sederhana konsisten dengan tema app. */
.markdown-body :deep(h1),
.markdown-body :deep(h2),
.markdown-body :deep(h3) {
  font-size: 0.875rem;
  font-weight: 600;
  margin: 0.9em 0 0.35em;
  color: var(--text);
}
.markdown-body :deep(h1:first-child),
.markdown-body :deep(h2:first-child),
.markdown-body :deep(h3:first-child) {
  margin-top: 0;
}
.markdown-body :deep(p) {
  margin: 0.45em 0;
}
.markdown-body :deep(ul),
.markdown-body :deep(ol) {
  margin: 0.45em 0;
  padding-left: 1.4em;
}
.markdown-body :deep(ul) {
  list-style: disc;
}
.markdown-body :deep(ol) {
  list-style: decimal;
}
.markdown-body :deep(li) {
  margin: 0.2em 0;
}
.markdown-body :deep(strong) {
  font-weight: 600;
}
.markdown-body :deep(code) {
  background: color-mix(in srgb, var(--muted) 12%, transparent);
  border-radius: 4px;
  padding: 0.1em 0.35em;
  font-size: 0.8em;
}
.markdown-body :deep(blockquote) {
  border-left: 3px solid var(--line);
  padding-left: 0.8em;
  margin: 0.5em 0;
  color: var(--muted);
}
.markdown-body :deep(a) {
  color: var(--accent);
  text-decoration: underline;
}
</style>
