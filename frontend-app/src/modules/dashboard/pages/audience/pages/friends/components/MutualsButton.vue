<!-- src/modules/dashboard/pages/audience/components/MutualsButton.vue -->
<template>
  <button
    class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1 text-xs border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-800"
    @click="$emit('open')"
  >
    <span>Mutuals</span>
    <span
      v-if="count!==undefined"
      class="text-[11px] px-1 rounded bg-zinc-100 dark:bg-zinc-700"
    >{{ count }}</span>
  </button>
</template>

<script setup lang="ts">
defineProps<{ count?: number }>()
defineEmits<{ (e:'open'):void }>()
</script>
