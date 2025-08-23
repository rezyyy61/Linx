<template>
  <div class="grid gap-2 sm:grid-cols-4">
    <button
      v-for="s in steps"
      :key="s.key"
      :class="[
        'flex items-center gap-2 px-3 py-2 rounded-lg border transition-colors',
        modelValue === s.key
          ? 'border-red-500 text-red-600 bg-red-50 dark:bg-red-900/10'
          : 'border-slate-300 dark:border-slate-700 hover:bg-black/5 dark:bg-gray-800/80 text-slate-700 dark:text-slate-200'
      ]"
      @click="$emit('update:modelValue', s.key)"
    >
      <Icon
        :icon="s.icon"
        width="18"
      />
      <span class="text-sm p-1">{{ t(`profile.steps.${s.key}`) }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue";
import { useI18n } from "vue-i18n";

defineProps<{ modelValue: string }>();
defineEmits<{ 'update:modelValue': [string] }>();

const { t } = useI18n();

const steps = [
  { key: "general", icon: "mdi:numeric-1-circle-outline" },
  { key: "ideology", icon: "mdi:shield-search-outline" },
  { key: "description", icon: "mdi:clipboard-text-outline" },
  { key: "links", icon: "mdi:link-variant" }
];
</script>
