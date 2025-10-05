<script setup lang="ts">
import {Icon} from "@iconify/vue";

const DATE_RANGES = ['all','today','this_week','this_month'] as const
type Range = (typeof DATE_RANGES)[number]

const query = defineModel<string>('q', { required: true })
const dateRange = defineModel<Range>('date_range', { required: true })
const language = defineModel<string>('language', { required: true })

const emit = defineEmits<{ (e:'apply'):void }>()

let t: number | null = null
function triggerApply(immediate = false) {
  if (t) window.clearTimeout(t)
  if (immediate) { emit('apply'); return }
  t = window.setTimeout(() => emit('apply'), 300)
}

function labelize(s: string) {
  return s.replace('_',' ').replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<template>
  <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
    <div class="relative h-10 w-[50%]">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
        <Icon
          icon="mdi:magnify"
          class="w-5 h-5"
        />
      </span>
      <input
        v-model="query"
        type="text"
        placeholder="Search publisher..."
        class="h-10 w-full rounded-xl border border-neutral-300 pl-10 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 dark:border-neutral-700 dark:bg-neutral-800"
        @input="triggerApply(false)"
      >
      <span class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400">⌘K</span>
    </div>
    <div class="flex items-center gap-3 text-sm">
      <div class="flex gap-2">
        <button
          v-for="r in DATE_RANGES"
          :key="r"
          :class="[
            'px-3 py-1 rounded-full transition-colors',
            dateRange === r
              ? 'bg-indigo-600 text-white'
              : 'bg-neutral-200 dark:bg-neutral-700 text-neutral-700 dark:text-neutral-300 hover:bg-neutral-300 dark:hover:bg-neutral-600'
          ]"
          @click="dateRange = r; triggerApply(true)"
        >
          {{ labelize(r) }}
        </button>
      </div>

      <select
        v-model="language"
        class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
        @change="triggerApply(true)"
      >
        <option value="">
          All languages
        </option>
        <option value="en">
          English
        </option>
        <option value="fa">
          Persian
        </option>
        <option value="ar">
          Arabic
        </option>
        <option value="ku">
          Kurdish
        </option>
      </select>
    </div>
  </div>
</template>
