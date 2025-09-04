<script setup lang="ts">
import { computed } from "vue";
import { Icon } from "@iconify/vue";

const props = defineProps<{
  steps: { key: string; label: string; icon?: string }[];
  active: number;
}>();
const emit = defineEmits<{ (e: "go", i: number): void }>();

const items = computed(() =>
  props.steps.map((s, i) => ({
    ...s,
    icon: s.icon ?? (i===0 ? "lucide:info" : i===1 ? "lucide:shield" : i===2 ? "lucide:clipboard-list" : "lucide:link-2"),
  }))
);

function pillClass(i: number) {
  return i === props.active
    ? "border-red-400 bg-red-50 text-red-700"
    : "border-gray-300 bg-white text-gray-700 dark:bg-transparent dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800";
}
function iconWrapClass(i: number) {
  return i === props.active
    ? "text-red-600 border-red-300 bg-white"
    : "text-gray-500 border-gray-300 bg-white dark:bg-transparent";
}
</script>

<template>
  <div class="w-full">
    <div class="flex flex-wrap gap-3 w-full">
      <button
        v-for="(s,i) in items"
        :key="s.key"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 transition-colors"
        :class="pillClass(i)"
        :aria-selected="i===active"
        @click="emit('go', i)"
      >
        <span
          class="inline-flex items-center justify-center h-6 w-6 rounded-full border"
          :class="iconWrapClass(i)"
        >
          <Icon
            :icon="s.icon"
            class="h-3.5 w-3.5"
          />
        </span>
        <span class="text-sm font-medium">{{ i+1 }}. {{ s.label }}</span>
      </button>
    </div>
  </div>
</template>
