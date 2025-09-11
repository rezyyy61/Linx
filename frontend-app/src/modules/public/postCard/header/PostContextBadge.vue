<template>
  <span :class="cls">
    <Icon
      v-if="icon"
      :icon="icon"
      class="h-3.5 w-3.5"
    />
    <span>{{ text }}</span>
  </span>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed } from 'vue'

const props = defineProps<{
  text: string
  icon?: string
  tone?: 'zinc' | 'indigo' | 'emerald' | 'rose' | 'amber'
}>()

const cls = computed(() => {
  const t = props.tone || 'zinc'
  const tones: Record<string, string> = {
    zinc: 'border-white/10 bg-white/10 text-zinc-300',
    indigo: 'border-indigo-500/20 bg-indigo-500/10 text-indigo-300',
    emerald: 'border-emerald-500/20 bg-emerald-500/10 text-emerald-300',
    rose: 'border-rose-500/20 bg-rose-500/10 text-rose-300',
    amber: 'border-amber-500/20 bg-amber-500/10 text-amber-300'
  }
  return `inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-[10px] font-medium ${tones[t]}`
})
</script>
