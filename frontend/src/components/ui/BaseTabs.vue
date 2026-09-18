<script setup lang="ts">
import { TabsRoot, TabsList, TabsTrigger } from 'reka-ui'

interface Tab {
  value: string
  label: string
  count?: number
}

interface Props {
  tabs: Tab[]
  modelValue: string
}

defineProps<Props>()
const emit = defineEmits<{ (e: 'update:modelValue', v: string): void }>()
</script>

<template>
  <TabsRoot
    :model-value="modelValue"
    @update:model-value="(v: string) => emit('update:modelValue', v)"
  >
    <TabsList class="flex items-center gap-1 rounded-xl border border-[var(--line)] bg-[var(--surface)] p-1">
      <TabsTrigger
        v-for="tab in tabs"
        :key="tab.value"
        :value="tab.value"
        class="flex-1 rounded-lg px-3 py-1.5 text-xs font-medium text-[var(--muted)] transition-all duration-150 outline-none hover:text-[var(--text)] data-[state=active]:bg-[var(--muted)]/10 data-[state=active]:text-[var(--text)] data-[state=active]:shadow-xs focus-visible:ring-2 focus-visible:ring-[var(--accent)]/30"
      >
        {{ tab.label }}
        <span v-if="tab.count !== undefined" class="ml-1.5 tabular-nums opacity-60">
          {{ tab.count }}
        </span>
      </TabsTrigger>
    </TabsList>
  </TabsRoot>
</template>
