<template>
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-2 flex-1">
      <div class="relative flex-1">
        <input
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 pr-9 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
          type="text"
          :value="modelValue.q"
          placeholder="Search announcements"
          :disabled="loading"
          @input="$emit('update:modelValue', { ...modelValue, q: ($event.target as HTMLInputElement).value })"
        >
        <span class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400">⌘K</span>
      </div>
      <label class="inline-flex items-center gap-2 text-sm px-3 py-2 rounded-xl border border-gray-300 dark:border-zinc-700 dark:text-zinc-200">
        <input
          type="checkbox"
          :checked="modelValue.onlyPinned"
          :disabled="loading"
          @change="$emit('update:modelValue', { ...modelValue, onlyPinned: ($event.target as HTMLInputElement).checked })"
        >
        <span>Pinned</span>
      </label>
    </div>

    <div class="flex items-center gap-2">
      <select
        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
        :value="modelValue.sort"
        :disabled="loading"
        @change="$emit('update:modelValue', { ...modelValue, sort: ($event.target as HTMLSelectElement).value as any })"
      >
        <option value="latest">
          Latest
        </option>
        <option value="oldest">
          Oldest
        </option>
        <option value="popular">
          Popular
        </option>
      </select>
      <button
        class="rounded-xl border px-3 py-2 text-sm dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800"
        :disabled="loading"
        @click="$emit('update:modelValue', { q: '', onlyPinned: false, sort: 'latest' })"
      >
        Reset
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  modelValue: { q: string; onlyPinned: boolean; sort: "latest" | "oldest" | "popular" };
  loading?: boolean;
}>();
defineEmits(["update:modelValue"]);
</script>
