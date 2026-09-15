<script setup lang="ts" generic="T extends { id: number | string; label: string; sub?: string; meta?: any }">
import { ref, computed } from 'vue'
import {
  AutocompleteRoot,
  AutocompleteAnchor,
  AutocompleteInput,
  AutocompleteContent,
  AutocompleteViewport,
  AutocompleteItem,
  AutocompleteItemIndicator,
  AutocompleteEmpty,
  AutocompleteTrigger,
} from 'reka-ui'
import { MagnifyingGlassIcon, CheckIcon, ChevronUpDownIcon } from '@heroicons/vue/20/solid'

interface Props {
  items: T[]
  modelValue?: string | number | null
  placeholder?: string
  label?: string
  displayValue?: (item: T) => string
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: '',
  placeholder: 'Cari psikolog atau spesialisasi...',
  label: '',
  displayValue: (item: T) => item.label,
})

const emit = defineEmits<{
  (e: 'update:modelValue', value: any): void
  (e: 'select', item: T): void
}>()

const searchTerm = ref('')
const selectedItem = computed(() => props.items.find(i => String(i.id) === String(props.modelValue)))

const filteredItems = computed(() => {
  if (!searchTerm.value.trim()) return props.items
  const term = searchTerm.value.toLowerCase()
  return props.items.filter(
    item =>
      item.label.toLowerCase().includes(term) ||
      (item.sub && item.sub.toLowerCase().includes(term))
  )
})

function handleSelect(item: T) {
  emit('update:modelValue', item.id)
  emit('select', item)
}
</script>

<template>
  <div class="w-full flex flex-col gap-1.5">
    <label v-if="label" class="text-xs font-semibold text-neutral-700 dark:text-neutral-300">
      {{ label }}
    </label>

    <AutocompleteRoot
      :model-value="String(modelValue || '')"
      :open-on-focus="true"
      :open-on-click="true"
      class="relative w-full"
      @update:model-value="(val) => {
        const found = items.find(i => String(i.id) === String(val))
        if (found) handleSelect(found)
      }"
    >
      <AutocompleteAnchor class="relative w-full flex items-center bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 rounded-xl px-3.5 py-2.5 shadow-sm transition-all focus-within:ring-2 focus-within:ring-rose-500/20 focus-within:border-rose-500">
        <MagnifyingGlassIcon class="w-4 h-4 text-neutral-400 shrink-0 mr-2.5" />
        
        <AutocompleteInput
          :placeholder="placeholder"
          class="w-full bg-transparent text-sm text-neutral-900 dark:text-neutral-100 placeholder-neutral-400 outline-none focus:outline-none"
          :value="searchTerm || (selectedItem ? displayValue(selectedItem) : '')"
          @input="(e: Event) => searchTerm = (e.target as HTMLInputElement).value"
        />

        <AutocompleteTrigger class="text-neutral-400 hover:text-neutral-600 dark:hover:text-neutral-200 p-0.5 ml-1">
          <ChevronUpDownIcon class="w-4 h-4" />
        </AutocompleteTrigger>
      </AutocompleteAnchor>

      <AutocompleteContent
        class="absolute z-50 mt-1 max-h-60 w-full overflow-hidden rounded-xl bg-white dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 shadow-xl py-1 text-sm will-change-[opacity,transform]"
        :side-offset="4"
      >
        <AutocompleteViewport class="p-1 max-h-56 overflow-y-auto">
          <AutocompleteEmpty class="py-4 text-center text-xs text-neutral-500">
            Tidak ada psikolog atau spesialisasi yang cocok.
          </AutocompleteEmpty>

          <AutocompleteItem
            v-for="item in filteredItems"
            :key="item.id"
            :value="String(item.id)"
            class="flex items-center justify-between px-3 py-2 rounded-lg cursor-pointer select-none text-neutral-800 dark:text-neutral-200 hover:bg-rose-50 dark:hover:bg-rose-950/40 hover:text-rose-600 dark:hover:text-rose-400 transition-colors data-[highlighted]:bg-rose-50 dark:data-[highlighted]:bg-rose-950/40 data-[highlighted]:text-rose-600 outline-none"
            @select="handleSelect(item)"
          >
            <div class="flex items-center gap-2.5 min-w-0">
              <div v-if="item.meta?.avatar" class="w-7 h-7 rounded-full overflow-hidden bg-neutral-100 shrink-0">
                <img :src="item.meta.avatar" :alt="item.label" class="w-full h-full object-cover" />
              </div>
              <div v-else-if="item.meta?.icon" class="w-7 h-7 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center text-xs font-bold shrink-0">
                {{ item.meta.icon }}
              </div>
              <div class="min-w-0">
                <p class="font-medium text-xs truncate">{{ item.label }}</p>
                <p v-if="item.sub" class="text-[10px] text-neutral-500 dark:text-neutral-400 truncate">{{ item.sub }}</p>
              </div>
            </div>

            <AutocompleteItemIndicator class="text-rose-600 ml-2">
              <CheckIcon class="w-4 h-4" />
            </AutocompleteItemIndicator>
          </AutocompleteItem>
        </AutocompleteViewport>
      </AutocompleteContent>
    </AutocompleteRoot>
  </div>
</template>
