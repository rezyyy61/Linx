<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="text-sm text-gray-600 dark:text-gray-300">
        Custom values
      </div>
      <button
        class="px-3 py-2 rounded-md bg-gray-900 text-white dark:bg-gray-100 dark:text-gray-900"
        @click="add"
      >
        Add value
      </button>
    </div>
    <div class="grid gap-3">
      <div
        v-for="item in items"
        :key="item.id"
        class="bg-white dark:bg-gray-900 border dark:border-gray-800 rounded-xl p-3 grid md:grid-cols-[220px_1fr_auto] gap-3 items-center"
      >
        <input
          v-model="item.type"
          placeholder="Type (e.g. membership_count)"
          class="px-3 py-2 border rounded-md bg-white dark:bg-gray-900 dark:border-gray-800"
        >
        <input
          v-model="item.value"
          placeholder="Value"
          class="px-3 py-2 border rounded-md bg-white dark:bg-gray-900 dark:border-gray-800"
        >
        <div class="flex justify-end">
          <button
            class="px-3 py-2 rounded-md bg-red-600 text-white"
            @click="remove(item.id)"
          >
            Remove
          </button>
        </div>
      </div>
      <div
        v-if="!items.length"
        class="rounded-xl border border-dashed p-6 text-center text-gray-500 dark:border-gray-800"
      >
        No values yet
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nanoid } from "nanoid/non-secure";
const items = defineModel<Array<{ id: string; type: string; value: string }>>({ required: true });
function add() {
  items.value.push({ id: nanoid(8), type: "", value: "" });
}
function remove(id: string) {
  const i = items.value.findIndex(x => x.id === id);
  if (i >= 0) items.value.splice(i, 1);
}
</script>
