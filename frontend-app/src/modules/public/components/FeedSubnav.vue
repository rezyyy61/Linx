<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const modelValue = defineModel<string>({ required: true });

const items = computed(() => [
  { key: "feed" },
  { key: "publication" },
  { key: "books" },
  { key: "media" },
  { key: "campaigns" },
  { key: "profiles" },
  { key: "events" },
  { key: "announcements" },
]);

function setTab(k: string) {
  modelValue.value = k;
}
</script>

<template>
  <nav class="mx-auto w-[70%]">
    <div
      class="mx-4 rounded-xl border border-gray-200 bg-white/70 backdrop-blur px-2 py-2 shadow-sm dark:border-zinc-800 dark:bg-zinc-900/70"
    >
      <ul
        class="flex gap-2 items-center whitespace-nowrap overflow-x-auto justify-start md:justify-center md:flex-wrap md:overflow-visible md:whitespace-normal no-scrollbar"
      >
        <li
          v-for="it in items"
          :key="it.key"
          class="shrink-0"
        >
          <button
            type="button"
            :class="[
              'relative px-4 py-2 text-sm font-medium transition-colors rounded-md',
              modelValue === it.key
                ? 'text-red-600 dark:text-red-400'
                : 'text-gray-600 hover:text-gray-900 dark:text-zinc-300 dark:hover:text-white',
            ]"
            @click="setTab(it.key)"
          >
            {{ t(`feedbar.${it.key}`) }}
            <span
              v-if="modelValue === it.key"
              class="pointer-events-none absolute left-2 right-2 -bottom-1 h-0.5 rounded-full bg-red-600 dark:bg-red-400 transition-all"
            />
          </button>
        </li>
      </ul>
    </div>
  </nav>
</template>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
  display: none;
}
.no-scrollbar {
  -ms-overflow-style: none;
  scrollbar-width: none;
}
</style>
