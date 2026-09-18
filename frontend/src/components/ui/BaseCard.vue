<script setup lang="ts">
interface Props {
  as?: string
  padded?: boolean
}

withDefaults(defineProps<Props>(), {
  as: 'div',
  padded: true,
})
</script>

<template>
  <component
    :is="as"
    class="rounded-xl border border-[var(--line)] bg-[var(--surface)]"
    :class="padded ? 'p-5' : ''"
  >
    <slot />
  </component>
</template>
