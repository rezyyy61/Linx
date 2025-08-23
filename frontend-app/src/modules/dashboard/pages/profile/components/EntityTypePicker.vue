<template>
  <div class="grid gap-3 grid-cols-2 md:grid-cols-4">
    <button
      v-for="opt in options"
      :key="opt.value"
      :class="[
        'relative aspect-square rounded-xl border transition-all bg-white dark:bg-gray-800/80 cursor-pointer',
        'flex flex-col items-center justify-center text-center overflow-hidden',
        selected === opt.value
          ? 'border-red-500 ring-2 ring-red-200 dark:ring-red-900/40'
          : 'border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600 hover:shadow-sm'
      ]"
      @click="select(opt.value)"
    >
      <Icon
        :icon="opt.icon"
        width="26"
      />
      <div class="font-medium text-lg">
        {{ t(`profile.basic.entityType.options.${opt.value}.title`) }}
      </div>

      <!-- بج قفل داخل کارت، پایین و وسط -->
      <div
        v-if="isLocked(opt.value)"
        class="absolute bottom-3 left-1/2 -translate-x-1/2 inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full
               text-red-600 bg-red-50 dark:text-red-400 dark:bg-red-900/20"
      >
        <Icon
          icon="mdi:lock-outline"
          width="14"
        />
        <span>{{ t('profile.basic.entityType.requiresApproval') }}</span>
      </div>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();
const model = defineModel<string>({ required: true });

const options = [
  { value: "individual", icon: "mdi:account-outline" },
  { value: "party", icon: "mdi:flag-variant-outline" },
  { value: "collective", icon: "mdi:account-group-outline" },
  { value: "media", icon: "mdi:television-classic" }
];

const lockedSet = new Set(["party", "collective", "media"]);
const selected = computed(() => model.value);
const isLocked = (v: string) => lockedSet.has(v);
function select(v: string) { if (!isLocked(v)) model.value = v; }
</script>
