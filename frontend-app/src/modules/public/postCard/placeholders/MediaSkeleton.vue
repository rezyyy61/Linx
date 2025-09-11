<template>
  <div class="animate-pulse overflow-hidden rounded-xl border border-zinc-200 bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-800">
    <div
      class="w-full"
      :style="ratioStyle"
    />
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{ ratio?: string }>()
const ratio = props.ratio || '16/9'
const [w, h] = ratio.split('/').map(Number)
const ratioStyle = { paddingTop: `${(h / w) * 100}%` }
</script>
