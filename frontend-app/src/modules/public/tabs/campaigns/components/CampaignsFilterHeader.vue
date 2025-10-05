<script setup lang="ts">
import { Icon } from "@iconify/vue";

const DATE_RANGES = ["all","today","this_week","this_month"] as const
type Range = (typeof DATE_RANGES)[number]

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const KINDS = ["","fundraising","petition","volunteer","awareness"] as const
type Kind = (typeof KINDS)[number]

const query = defineModel<string>("q", { default: "" })
const dateRange = defineModel<Range>("date_range", { default: "all" })
const kind = defineModel<Kind>("kind", { default: "" })

const emit = defineEmits<{ (e:"apply"): void }>()
let t: number | null = null
function triggerApply(immediate = false) {
  if (t) window.clearTimeout(t)
  if (immediate) { emit("apply"); return }
  t = window.setTimeout(() => emit("apply"), 300)
}

function labelizeRange(s: string) {
  return s.replace("_"," ").replace(/\b\w/g, c => c.toUpperCase())
}
</script>

<template>
  <div class="w-full grid grid-cols-1 md:[grid-template-columns:1fr_auto] items-center gap-3 mb-6">
    <div class="relative h-10 w-full">
      <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
        <Icon
          icon="mdi:magnify"
          class="w-5 h-5"
        />
      </span>

      <input
        v-model="query"
        type="text"
        placeholder="Search campaigns..."
        class="h-10 w-[80%] rounded-xl border border-neutral-300 px-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-600 dark:border-neutral-700 dark:bg-neutral-800"
        @input="triggerApply(false)"
      >

      <span class="absolute right-[20%] top-1/2 px-4 -translate-y-1/2 text-neutral-400">⌘K</span>
    </div>

    <div class="flex items-center justify-start md:justify-end gap-3 text-sm shrink-0">
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
          {{ labelizeRange(r) }}
        </button>
      </div>

      <select
        v-model="kind"
        class="rounded-xl border border-neutral-300 dark:border-neutral-700 bg-white dark:bg-neutral-800 px-3 py-2 text-sm focus:ring-2 focus:ring-indigo-500"
        @change="triggerApply(true)"
      >
        <option value="">
          All kinds
        </option>
        <option value="fundraising">
          Fundraising
        </option>
        <option value="petition">
          Petition
        </option>
        <option value="volunteer">
          Volunteer
        </option>
        <option value="awareness">
          Awareness
        </option>
      </select>
    </div>
  </div>
</template>
