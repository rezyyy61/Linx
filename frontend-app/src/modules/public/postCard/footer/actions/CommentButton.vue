<template>
  <button
    class="inline-flex items-center gap-1.5 rounded-lg px-2 py-1 hover:bg-zinc-100 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-zinc-800"
    aria-label="Comments"
    @click="$emit('click')"
    @keydown.enter.prevent="$emit('click')"
    @keydown.space.prevent="$emit('click')"
  >
    <Icon
      icon="mdi:chat-outline"
      class="h-5 w-5"
    />
    <span>{{ fmt(count) }}</span>
  </button>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { formatNumber as fmt } from '@/modules/public/postCard/utils/formatNumber'

const { count } = defineProps<{ count: number }>()
defineEmits<{ (e: 'click'): void }>()
</script>
