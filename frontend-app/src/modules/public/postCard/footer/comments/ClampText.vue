<template>
  <div class="relative">
    <div
      ref="box"
      :class="expanded ? '' : 'max-h-[96px] overflow-hidden'"
    >
      <slot />
    </div>
    <div
      v-if="!expanded && overflow"
      class="pointer-events-none absolute inset-x-0 bottom-0 h-10 bg-gradient-to-t from-white to-transparent dark:from-zinc-900"
    />
    <div
      v-if="overflow"
      class="mt-2"
    >
      <button
        class="text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
        @click="expanded = !expanded"
      >
        {{ expanded ? 'Show less' : 'Read more' }}
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, nextTick } from 'vue'
const expanded = ref(false)
const overflow = ref(false)
const box = ref<HTMLElement | null>(null)
onMounted(async () => {
  await nextTick()
  if (box.value) overflow.value = box.value.scrollHeight > 96
})
</script>
