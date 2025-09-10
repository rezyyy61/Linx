<template>
  <div class="w-full flex flex-wrap items-center gap-2">
    <div class="relative">
      <input
        :value="search"
        placeholder="Search friends…"
        class="pl-9 pr-3 py-2 rounded-xl border bg-white dark:bg-slate-900 dark:border-slate-700 text-sm w-64"
        @input="$emit('update:search', ($event.target as HTMLInputElement).value)"
      >
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">🔎</span>
    </div>

    <div class="flex items-center gap-2">
      <select
        :value="orderBy"
        class="rounded-xl border px-2 py-2 text-sm dark:bg-slate-900 dark:border-slate-700"
        @change="$emit('update:order-by', ($event.target as HTMLSelectElement).value as 'pinned' | 'name')"
      >
        <option value="name">
          Name A–Z
        </option>
        <option value="pinned">
          Pinned first
        </option>
      </select>

      <select
        :value="limit"
        class="rounded-xl border px-2 py-2 text-sm dark:bg-slate-900 dark:border-slate-700"
        @change="$emit('update:limit', Number(($event.target as HTMLSelectElement).value))"
      >
        <option :value="20">
          20
        </option>
        <option :value="50">
          50
        </option>
        <option :value="100">
          100
        </option>
      </select>
    </div>

    <button
      class="rounded-xl border px-3 py-2 text-sm dark:border-slate-700 disabled:opacity-50"
      :disabled="loading"
      @click="$emit('refresh')"
    >
      Refresh
    </button>

    <div class="ml-auto flex items-center gap-2">
      <button
        class="rounded-xl px-3 py-2 text-xs bg-red-600 text-white disabled:opacity-50"
        :disabled="selectedCount === 0"
        @click="$emit('bulk-remove')"
      >
        Remove selected ({{ selectedCount }})
      </button>
      <div class="hidden md:flex items-center gap-1">
        <button
          class="rounded-lg border px-2 py-1 text-xs dark:border-slate-700"
          @click="$emit('select-all')"
        >
          Select all
        </button>
        <button
          class="rounded-lg border px-2 py-1 text-xs dark:border-slate-700"
          @click="$emit('unselect-all')"
        >
          Unselect
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  search: string
  orderBy: 'pinned' | 'name'
  limit: number
  loading: boolean
  selectedCount: number
}>()

defineEmits<{
  (e:'update:search', v:string): void
  (e:'update:order-by', v:'pinned'|'name'): void
  (e:'update:limit', v:number): void
  (e:'refresh'): void
  (e:'bulk-remove'): void
  (e:'clear-selection'): void
  (e:'select-all'): void
  (e:'unselect-all'): void
}>()
</script>
