<template>
  <div class="relative">
    <input
      :value="modelValue"
      :placeholder="placeholder"
      class="w-full pl-10 pr-8 py-2 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-white dark:bg-zinc-900 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
      type="search"
      @input="$emit('update:modelValue', ($event.target as HTMLInputElement).value)"
    >
    <Icon
      class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"
      icon="mdi:magnify"
      width="18"
      height="18"
    />
    <button
      v-if="modelValue"
      class="absolute right-2 top-1/2 -translate-y-1/2 p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-800 text-zinc-500"
      aria-label="Clear"
      @click="$emit('update:modelValue', '')"
    >
      <Icon
        icon="mdi:close"
        width="16"
        height="16"
      />
    </button>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue"
defineProps<{ modelValue: string, placeholder?: string }>()
defineEmits<{ (e: 'update:modelValue', v: string): void }>()
</script>
