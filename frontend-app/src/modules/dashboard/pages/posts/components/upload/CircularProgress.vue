<script setup lang="ts">
import { ref, watch, computed, onUnmounted } from 'vue'

const props = withDefaults(defineProps<{
  value: number
  size?: number
  stroke?: number
  showLabel?: boolean
  duration?: number
}>(), { size: 56, stroke: 6, showLabel: true, duration: 400 })

const radius = computed(() => Math.max(0, (props.size - props.stroke) / 2))
const circumference = computed(() => 2 * Math.PI * radius.value)
const center = computed(() => props.size / 2)

const display = ref(0)
let raf = 0
let startTime = 0
let from = 0
let to = 0
const ease = (t: number) => 1 - Math.pow(1 - t, 3)
const animate = (ts: number) => {
  if (!startTime) startTime = ts
  const elapsed = ts - startTime
  const d = Math.max(0, props.duration)
  const p = d ? Math.min(1, elapsed / d) : 1
  const eased = ease(p)
  display.value = from + (to - from) * eased
  if (p < 1) raf = requestAnimationFrame(animate)
}
const setTarget = (v: number) => {
  const target = Math.max(0, Math.min(100, v))
  from = display.value
  to = target
  startTime = 0
  cancelAnimationFrame(raf)
  raf = requestAnimationFrame(animate)
}
watch(() => props.value, setTarget, { immediate: true })
onUnmounted(() => cancelAnimationFrame(raf))

const dashoffset = computed(() => circumference.value * (1 - display.value / 100))
</script>

<template>
  <div
    class="relative inline-grid place-items-center"
    :style="{ width: size + 'px', height: size + 'px' }"
    role="progressbar"
    :aria-valuemin="0"
    :aria-valuemax="100"
    :aria-valuenow="Math.round(display)"
  >
    <svg
      :width="size"
      :height="size"
      :viewBox="`0 0 ${size} ${size}`"
    >
      <circle
        :cx="center"
        :cy="center"
        :r="radius"
        :stroke-width="stroke"
        class="text-gray-200 dark:text-gray-700"
        stroke="currentColor"
        fill="none"
      />
      <g :transform="`rotate(-90 ${center} ${center})`">
        <circle
          :cx="center"
          :cy="center"
          :r="radius"
          :stroke-width="stroke"
          class="text-emerald-500"
          stroke="currentColor"
          fill="none"
          :stroke-dasharray="circumference"
          :stroke-dashoffset="dashoffset"
          stroke-linecap="round"
        />
      </g>
    </svg>
    <span
      v-if="showLabel"
      class="absolute text-xs font-medium"
    >{{ Math.round(display) }}%</span>
  </div>
</template>
