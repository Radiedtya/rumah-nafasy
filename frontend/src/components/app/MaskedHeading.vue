<script setup lang="ts">
import { gsap } from 'gsap';
import { computed, onMounted, onUnmounted, ref, watch, type CSSProperties, type ComponentPublicInstance } from 'vue';

const clamp = (v: number, a: number, b: number): number => (v < a ? a : v > b ? b : v);

export type Reveal = 'rise' | 'wipe' | 'fade' | 'none';
export type Trigger = 'view' | 'mount' | 'hover';

interface MaskedHeadingProps {
  text?: string;
  tag?: string;
  mediaType?: 'image' | 'video';
  src?: string;
  poster?: string;
  fillScale?: number;
  parallax?: number;
  drift?: number;
  brightness?: number;
  saturation?: number;
  grayscale?: boolean;
  reveal?: Reveal;
  duration?: number;
  stagger?: number;
  trigger?: Trigger;
  align?: 'left' | 'center' | 'right';
  weight?: number;
  tracking?: number;
  lineHeight?: number;
  textScale?: number;
}

const props = withDefaults(defineProps<MaskedHeadingProps>(), {
  text: 'Designed in the details',
  tag: 'h2',
  mediaType: 'image',
  src: '',
  poster: '',
  fillScale: 1.25,
  parallax: 26,
  drift: 18,
  brightness: 1,
  saturation: 1,
  grayscale: false,
  reveal: 'rise',
  duration: 1.1,
  stagger: 0.09,
  trigger: 'view',
  align: 'center',
  weight: 700,
  tracking: -0.03,
  lineHeight: 1.06,
  textScale: 0.115
});

const uid = Math.random().toString(36).slice(2, 10);
const clipId = `mh-${uid}`;

const rootRef = ref<HTMLElement | null>(null);
const measureRef = ref<HTMLSpanElement | null>(null);
const revealRef = ref<HTMLSpanElement | null>(null);
const mediaRef = ref<HTMLSpanElement | null>(null);
const wordRefs = ref<(HTMLSpanElement | null)[]>([]);
const baseRefs = ref<(HTMLElement | null)[]>([]);
const glyphRefs = ref<(SVGTextElement | null)[]>([]);

const setWordRef = (el: Element | ComponentPublicInstance | null, index: number) => {
  wordRefs.value[index] = el as HTMLSpanElement | null;
};
const setBaseRef = (el: Element | ComponentPublicInstance | null, index: number) => {
  baseRefs.value[index] = el as HTMLElement | null;
};
const setGlyphRef = (el: Element | ComponentPublicInstance | null, index: number) => {
  glyphRefs.value[index] = el as SVGTextElement | null;
};

const words = computed(() => String(props.text).split(/\s+/).filter(Boolean));

const rootStyle = computed<CSSProperties>(() => ({
  textAlign: props.align,
  fontWeight: props.weight,
  letterSpacing: `${props.tracking}em`,
  lineHeight: props.lineHeight
}));

const offset = { x: 0, y: 0, tx: 0, ty: 0 };
let tween: gsap.core.Tween | null = null;

const place = () => {
  const root = rootRef.value;
  const media = mediaRef.value;
  if (!root || !media) return;
  const W = root.clientWidth;
  const H = root.clientHeight;

  const maxX = Math.max(0, ((props.fillScale - 1) / 2) * W);
  const maxY = Math.max(0, ((props.fillScale - 1) / 2) * H);

  media.style.transform = `translate3d(${clamp(offset.x, -maxX, maxX).toFixed(2)}px, ${clamp(offset.y, -maxY, maxY).toFixed(2)}px, 0) scale(${props.fillScale})`;
  media.style.filter = `brightness(${props.brightness}) saturate(${props.saturation})${props.grayscale ? ' grayscale(1)' : ''}`;
};

const sync = () => {
  const root = rootRef.value;
  const measure = measureRef.value;
  if (!root || !measure) return;

  root.style.fontSize = `${clamp(root.clientWidth * props.textScale, 20, 200).toFixed(1)}px`;

  const cs = window.getComputedStyle(measure);
  for (let i = 0; i < wordRefs.value.length; i += 1) {
    const box = wordRefs.value[i];
    const base = baseRefs.value[i];
    const glyph = glyphRefs.value[i];
    if (!box || !base || !glyph) continue;
    glyph.setAttribute('x', `${box.offsetLeft}`);
    glyph.setAttribute('y', `${base.offsetTop}`);
    glyph.style.fontFamily = cs.fontFamily;
    glyph.style.fontSize = cs.fontSize;
    glyph.style.fontWeight = cs.fontWeight;
    glyph.style.fontStyle = cs.fontStyle;
    glyph.style.letterSpacing = cs.letterSpacing;
  }
  place();
};

let ro: ResizeObserver | null = null;
let raf = 0;
let removeParallaxListeners: (() => void) | null = null;

onMounted(() => {
  const root = rootRef.value;
  if (!root) return;

  sync();
  ro = new ResizeObserver(sync);
  ro.observe(root);
  if (document.fonts?.ready) document.fonts.ready.then(sync).catch(() => {});

  let last = performance.now();
  let clock = 0;

  const frame = (now: number) => {
    const dt = Math.min(0.05, (now - last) / 1000);
    last = now;
    clock += dt;

    const dx = Math.sin(clock * 0.21) * props.drift;
    const dy = Math.cos(clock * 0.17) * props.drift * 0.6;

    const ease = 1 - Math.exp(-dt / 0.18);
    offset.x += (offset.tx + dx - offset.x) * ease;
    offset.y += (offset.ty + dy - offset.y) * ease;

    place();
    raf = requestAnimationFrame(frame);
  };

  const onMove = (e: PointerEvent) => {
    if (props.parallax <= 0) return;
    const r = root.getBoundingClientRect();
    const nx = ((e.clientX - r.left) / (r.width || 1)) * 2 - 1;
    const ny = ((e.clientY - r.top) / (r.height || 1)) * 2 - 1;
    offset.tx = clamp(nx, -1, 1) * -props.parallax;
    offset.ty = clamp(ny, -1, 1) * -props.parallax;
  };

  const onLeave = () => {
    offset.tx = 0;
    offset.ty = 0;
  };

  root.addEventListener('pointermove', onMove);
  root.addEventListener('pointerleave', onLeave);
  raf = requestAnimationFrame(frame);

  removeParallaxListeners = () => {
    root.removeEventListener('pointermove', onMove);
    root.removeEventListener('pointerleave', onLeave);
  };
});

onUnmounted(() => {
  cancelAnimationFrame(raf);
  ro?.disconnect();
  removeParallaxListeners?.();
});

watch(
  () => [words.value, props.tag, props.align, props.weight, props.tracking, props.lineHeight, props.textScale],
  () => sync(),
  { flush: 'post' }
);

let teardownReveal: (() => void) | null = null;

const riseDistance = (root: HTMLElement): number => (parseFloat(window.getComputedStyle(root).fontSize) || 48) * 1.15;

const setupReveal = () => {
  const root = rootRef.value;
  const layer = revealRef.value;
  if (!root || !layer) return;
  const glyphs = glyphRefs.value.filter((g): g is SVGTextElement => g !== null);
  if (!glyphs.length) return;

  const settle = () => {
    gsap.set(glyphs, { y: 0 });
    gsap.set(layer, { opacity: 1, scale: 1, clipPath: 'inset(0% 0% 0% 0%)' });
  };

  const rest = () => {
    if (props.reveal === 'rise') {
      gsap.set(glyphs, { y: riseDistance(root) });
    } else if (props.reveal === 'wipe') {
      gsap.set(layer, { clipPath: 'inset(0% 100% 0% 0%)' });
    } else if (props.reveal === 'fade') {
      gsap.set(layer, { opacity: 0, scale: 1.08 });
    }
  };

  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (props.reveal === 'none' || reduce) {
    settle();
    return;
  }

  const play = () => {
    tween?.kill();
    if (props.reveal === 'rise') {
      gsap.set(layer, { opacity: 1, scale: 1, clipPath: 'inset(0% 0% 0% 0%)' });
      tween = gsap.fromTo(
        glyphs,
        { y: riseDistance(root) },
        { y: 0, duration: props.duration, stagger: props.stagger, ease: 'power4.out', overwrite: 'auto' }
      );
    } else if (props.reveal === 'wipe') {
      gsap.set(glyphs, { y: 0 });
      const state = { p: 100 };
      tween = gsap.to(state, {
        p: 0,
        duration: props.duration,
        ease: 'power3.inOut',
        overwrite: 'auto',
        onUpdate: () => {
          layer.style.clipPath = `inset(0% ${state.p}% 0% 0%)`;
        }
      });
    } else {
      gsap.set(glyphs, { y: 0 });
      tween = gsap.fromTo(
        layer,
        { opacity: 0, scale: 1.08 },
        { opacity: 1, scale: 1, duration: props.duration, ease: 'power3.out', overwrite: 'auto' }
      );
    }
  };

  if (props.trigger === 'hover') {
    settle();
    root.addEventListener('pointerenter', play);
    teardownReveal = () => {
      root.removeEventListener('pointerenter', play);
      tween?.kill();
    };
    return;
  }

  if (props.trigger === 'view') {
    settle();
    rest();
    const io = new IntersectionObserver(
      entries => {
        if (entries.some(e => e.isIntersecting)) {
          play();
          io.disconnect();
        }
      },
      { threshold: 0.25 }
    );
    io.observe(root);
    teardownReveal = () => {
      io.disconnect();
      tween?.kill();
    };
    return;
  }

  play();
  teardownReveal = () => tween?.kill();
};

onMounted(() => {
  setupReveal();
});

onUnmounted(() => {
  teardownReveal?.();
  teardownReveal = null;
});

watch(
  () => [props.reveal, props.trigger, props.duration, props.stagger, words.value],
  () => {
    teardownReveal?.();
    teardownReveal = null;
    setupReveal();
  },
  { flush: 'post' }
);
</script>

<template>
  <component :is="tag" ref="rootRef" class="relative m-0 p-0 w-full antialiased text-balance" :style="rootStyle">
    <span ref="measureRef" class="text-transparent">
      <span
        v-for="(word, i) in words"
        :key="`${word}-${i}`"
        :ref="(el: Element | ComponentPublicInstance | null) => setWordRef(el, i)"
        class="inline-block not-last:after:content-['\00a0'] whitespace-pre"
      >
        {{ word }}
        <i :ref="(el: Element | ComponentPublicInstance | null) => setBaseRef(el, i)" class="inline-block w-0 h-0" />
      </span>
    </span>

    <svg class="absolute w-0 h-0 overflow-hidden" aria-hidden="true" focusable="false">
      <defs>
        <clipPath :id="clipId" clipPathUnits="userSpaceOnUse">
          <text
            v-for="(word, i) in words"
            :key="`${word}-${i}`"
            :ref="(el: Element | ComponentPublicInstance | null) => setGlyphRef(el, i)"
          >
            {{ word }}
          </text>
        </clipPath>
      </defs>
    </svg>

    <span ref="revealRef" class="block absolute inset-0 pointer-events-none">
      <span class="block absolute inset-0" :style="{ clipPath: `url(#${clipId})` }">
        <span ref="mediaRef" class="block absolute inset-0 will-change-[transform,filter]">
          <video
            v-if="mediaType === 'video'"
            class="block w-full h-full object-cover select-none"
            :src="src"
            :poster="poster"
            autoplay
            muted
            loop
            playsinline
          />
          <img v-else class="block w-full h-full object-cover select-none" :src="src" alt="" :draggable="false" />
        </span>
      </span>
    </span>
  </component>
</template>
