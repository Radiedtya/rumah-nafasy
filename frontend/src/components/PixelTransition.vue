<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from 'vue'
import type { CSSProperties } from 'vue'
import { gsap } from 'gsap'

interface Props {
  gridSize?: number
  pixelColor?: string
  animationStepDuration?: number
  once?: boolean
  aspectRatio?: string
  style?: CSSProperties
}

const props = withDefaults(defineProps<Props>(), {
  gridSize: 12,
  pixelColor: '#f4f3f0',
  animationStepDuration: 0.4,
  once: false,
  aspectRatio: '100%',
  style: () => ({}),
})

const grid = ref<HTMLElement | null>(null)
const activeContent = ref<HTMLElement | null>(null)
const isActive = ref(false)
const isTouchDevice = ref(false)
let delayedCall: gsap.core.Tween | null = null

function buildGrid() {
  if (!grid.value) return
  grid.value.replaceChildren()
  const size = 100 / props.gridSize
  for (let row = 0; row < props.gridSize; row += 1) {
    for (let col = 0; col < props.gridSize; col += 1) {
      const pixel = document.createElement('div')
      pixel.className = 'pixel-transition__pixel'
      // These nodes are created at runtime, so Vue's scoped CSS attributes
      // are not attached to them. Keep their layout styles inline.
      pixel.style.cssText = `position:absolute;display:none;background-color:${props.pixelColor};width:${size}%;height:${size}%;left:${col * size}%;top:${row * size}%`
      grid.value.append(pixel)
    }
  }
}

function animate(activate: boolean) {
  const pixels = grid.value?.querySelectorAll<HTMLElement>('.pixel-transition__pixel')
  if (!pixels?.length || !activeContent.value) return
  isActive.value = activate
  gsap.killTweensOf(pixels)
  delayedCall?.kill()
  gsap.set(pixels, { display: 'none' })
  gsap.to(pixels, {
    display: 'block', duration: 0,
    stagger: { each: props.animationStepDuration / pixels.length, from: 'random' },
  })
  delayedCall = gsap.delayedCall(props.animationStepDuration, () => {
    if (!activeContent.value) return
    activeContent.value.style.display = activate ? 'block' : 'none'
    activeContent.value.style.pointerEvents = activate ? 'none' : ''
  })
  gsap.to(pixels, {
    display: 'none', duration: 0, delay: props.animationStepDuration,
    stagger: { each: props.animationStepDuration / pixels.length, from: 'random' },
  })
}

function enter() { if (!isActive.value) animate(true) }
function leave() { if (isActive.value && !props.once) animate(false) }
function click() {
  if (isActive.value) leave()
  else enter()
}

onMounted(() => {
  isTouchDevice.value = 'ontouchstart' in window || navigator.maxTouchPoints > 0 || window.matchMedia('(pointer: coarse)').matches
  buildGrid()
})
onUnmounted(() => delayedCall?.kill())
watch(() => [props.gridSize, props.pixelColor], buildGrid)
</script>

<template>
  <div
    class="pixel-transition"
    :style="style"
    tabindex="0"
    @mouseenter="!isTouchDevice && enter()"
    @mouseleave="!isTouchDevice && leave()"
    @click="isTouchDevice && click()"
    @focus="!isTouchDevice && enter()"
    @blur="!isTouchDevice && leave()"
  >
    <div class="pixel-transition__ratio" :style="{ paddingTop: aspectRatio }" />
    <div class="pixel-transition__content"><slot name="first" /></div>
    <div ref="activeContent" class="pixel-transition__content pixel-transition__active" style="display:none"><slot name="second" /></div>
    <div ref="grid" class="pixel-transition__grid" aria-hidden="true" />
  </div>
</template>

<style scoped>
.pixel-transition { position: absolute; inset: 0; overflow: hidden; }
.pixel-transition__ratio { display: none; }
.pixel-transition__content, .pixel-transition__grid { position: absolute; inset: 0; width: 100%; height: 100%; }
.pixel-transition__active { z-index: 2; }
.pixel-transition__grid { z-index: 3; pointer-events: none; }
.pixel-transition__pixel { position: absolute; display: none; }
</style>
