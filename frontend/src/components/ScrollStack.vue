<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref } from 'vue'

interface ScrollStackProps {
  className?: string
  itemDistance?: number
  itemStackDistance?: number
  stackPosition?: string
  scaleEndPosition?: string
  baseScale?: number
  itemScale?: number
  rotationAmount?: number
  onStackComplete?: () => void
}

const props = withDefaults(defineProps<ScrollStackProps>(), {
  className: '',
  itemDistance: 120,
  itemStackDistance: 28,
  stackPosition: '6%',
  scaleEndPosition: '2%',
  baseScale: 0.96,
  itemScale: 0.02,
  rotationAmount: 0,
})

const stackRoot = ref<HTMLElement | null>(null)
const cards = ref<HTMLElement[]>([])
let frame = 0
let stackCompleted = false

function parsePosition(value: string, viewportHeight: number) {
  if (value.includes('%')) return (parseFloat(value) / 100) * viewportHeight
  return parseFloat(value)
}

function getDocumentTop(element: HTMLElement) {
  const root = stackRoot.value
  if (!root || element === root) return element.getBoundingClientRect().top + window.scrollY

  // offsetTop is relative to the nearest positioned ancestor (often the
  // inner wrapper), not necessarily the stack root. Walk the offset-parent
  // chain so every card uses the same document coordinate space.
  let top = 0
  let current: HTMLElement | null = element
  while (current && current !== root) {
    top += current.offsetTop
    current = current.offsetParent as HTMLElement | null
  }

  // If an intermediate wrapper is the offset parent, include its position
  // relative to the root as well. The root's rect supplies the document origin.
  return root.getBoundingClientRect().top + window.scrollY + top
}

function update() {
  frame = 0
  const root = stackRoot.value
  if (!root || !cards.value.length) return

  const viewportHeight = window.innerHeight
  const scrollTop = window.scrollY
  const stackPosition = parsePosition(props.stackPosition, viewportHeight)
  const scaleEndPosition = parsePosition(props.scaleEndPosition, viewportHeight)
  const endMarker = root.querySelector<HTMLElement>('.scroll-stack-end')
  const pinEnd = endMarker
    ? endMarker.getBoundingClientRect().top + scrollTop - viewportHeight / 2
    : Number.POSITIVE_INFINITY

  cards.value.forEach((card, index) => {
    const cardTop = getDocumentTop(card)
    const pinStart = cardTop - stackPosition - props.itemStackDistance * index
    const triggerEnd = cardTop - scaleEndPosition
    const scaleProgress = Math.max(0, Math.min(1, (scrollTop - pinStart) / Math.max(1, triggerEnd - pinStart)))
    const targetScale = props.baseScale + index * props.itemScale
    const scale = 1 - scaleProgress * (1 - targetScale)
    const rotation = index * props.rotationAmount * scaleProgress
    const translateY = scrollTop < pinStart
      ? 0
      : scrollTop <= pinEnd
        ? scrollTop - cardTop + stackPosition + props.itemStackDistance * index
        : pinEnd - cardTop + stackPosition + props.itemStackDistance * index

    card.style.transform = `translate3d(0, ${translateY.toFixed(2)}px, 0) scale(${scale.toFixed(3)}) rotate(${rotation.toFixed(2)}deg)`
    card.style.zIndex = String(index + 1)
  })

  const lastCard = cards.value[cards.value.length - 1]
  const lastPinStart = getDocumentTop(lastCard) - stackPosition - props.itemStackDistance * (cards.value.length - 1)
  const isComplete = scrollTop >= lastPinStart && scrollTop <= pinEnd
  if (isComplete && !stackCompleted) props.onStackComplete?.()
  stackCompleted = isComplete
}

function onScroll() {
  if (!frame) frame = requestAnimationFrame(update)
}

onMounted(async () => {
  await nextTick()
  const root = stackRoot.value
  if (!root) return

  root.style.position = 'relative'
  cards.value = Array.from(root.querySelectorAll<HTMLElement>('.scroll-stack-card'))
  cards.value.forEach((card, index) => {
    card.style.position = 'relative'
    card.style.inset = 'auto'
    card.style.marginBottom = index < cards.value.length - 1 ? `${props.itemDistance}px` : '0'
    card.style.willChange = 'transform'
    card.style.transformOrigin = 'top center'
    card.style.backfaceVisibility = 'hidden'
  })

  window.addEventListener('scroll', onScroll, { passive: true })
  window.addEventListener('resize', onScroll)
  update()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  window.removeEventListener('resize', onScroll)
  if (frame) cancelAnimationFrame(frame)
})
</script>

<script lang="ts">
import { defineComponent, h } from 'vue'

export const ScrollStackItem = defineComponent({
  name: 'ScrollStackItem',
  props: { itemClassName: { type: String, default: '' } },
  setup(props, { slots }) {
    return () => h('div', { class: `scroll-stack-card ${props.itemClassName}`.trim() }, slots.default?.())
  },
})
</script>

<template>
  <div ref="stackRoot" :class="['scroll-stack-window-wrapper', props.className]">
    <div class="scroll-stack-inner">
      <slot />
      <div class="scroll-stack-end" aria-hidden="true"></div>
    </div>
  </div>
</template>
