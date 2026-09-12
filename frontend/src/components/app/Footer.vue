<script setup lang="ts">
type SocialLink = {
  label: string
  href: string
  icon: 'bluesky' | 'instagram' | 'tiktok' | 'x'
}

defineProps<{
  copyright: string
  socialLinks: SocialLink[]
}>()
</script>

<template>
  <footer class="mt-auto flex flex-col items-start justify-between gap-5 border-t border-[var(--line)] pb-8 pt-6 text-[10px] uppercase tracking-[.08em] text-[var(--muted)] min-[701px]:flex-row min-[701px]:items-center min-[701px]:text-xs" id="kontak">
    <span>{{ copyright }}</span>
    <nav class="flex items-center gap-4" aria-label="Media sosial Rumah Natasy">
      <a
        v-for="social in socialLinks"
        :key="social.icon"
        :href="social.href"
        target="_blank"
        rel="noreferrer"
        :aria-label="social.label"
        class="inline-flex transition-opacity duration-200 hover:opacity-65 focus-visible:outline-2 focus-visible:outline-[var(--text)] focus-visible:outline-offset-4"
      >
        <SimpleBlueskyIcon v-if="social.icon === 'bluesky'" :size="16" />
        <SimpleInstagramIcon v-else-if="social.icon === 'instagram'" :size="16" />
        <SimpleTiktokIcon v-else-if="social.icon === 'tiktok'" :size="16" />
        <SimpleXIcon v-else :size="16" />
      </a>
    </nav>
  </footer>
</template>