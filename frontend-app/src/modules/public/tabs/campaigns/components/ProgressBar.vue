<script setup lang="ts">
import { computed } from "vue"
const props = defineProps<{ value?: number | string; max?: number | string }>()

const toNum = (x: unknown) => {
  const n = Number(x)
  return Number.isFinite(n) ? n : 0
}

const rawPct = computed(() => {
  const v = toNum(props.value)
  const m = toNum(props.max)
  if (m <= 0) return 0
  return Math.max(0, Math.min(100, (v / m) * 100))
})

const barPct = computed(() => {
  const p = rawPct.value
  if (p > 0 && p < 1) return 1
  return Math.round(p)
})
</script>

<template>
  <div class="w-full h-2 rounded-full bg-neutral-200 dark:bg-neutral-800 overflow-hidden">
    <div
      class="h-2 rounded-full bg-emerald-500 transition-all"
      :style="{ width: barPct + '%' }"
    />
  </div>
</template>
