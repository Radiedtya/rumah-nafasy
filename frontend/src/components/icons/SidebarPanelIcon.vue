<script setup lang="ts">
interface Props {
  class?: string
}

withDefaults(defineProps<Props>(), {
  class: '',
})
</script>

<template>
  <svg
    xmlns="http://www.w3.org/2000/svg"
    fill="none"
    viewBox="0 0 24 24"
    stroke-width="1.5"
    stroke="currentColor"
    :class="$props.class"
    aria-hidden="true"
  >
    <path
      stroke-linecap="round"
      stroke-linejoin="round"
      d="M3.75 6.75c0-1.243 1.008-2.25 2.25-2.25h12a2.25 2.25 0 0 1 2.25 2.25v10.5a2.25 2.25 0 0 1-2.25 2.25h-12a2.25 2.25 0 0 1-2.25-2.25V6.75Z"
    />
    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 4.5v15" />
  </svg>
</template>
