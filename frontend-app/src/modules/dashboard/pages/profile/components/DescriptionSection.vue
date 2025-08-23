<template>
  <div class="bg-white/80 dark:bg-gray-800/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 md:p-8 backdrop-blur">
    <div class="grid gap-6 md:grid-cols-1">
      <div class="relative">
        <textarea
          ref="aboutRef"
          v-model="model.about"
          class="peer w-full px-4 pt-7 pb-3 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 resize-none min-h-[160px] leading-relaxed"
          placeholder=" "
          :maxlength="aboutMax"
          @input="autoResize($event)"
        />
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.about.about.label') }}
        </label>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          {{ t('profile.about.about.placeholder') }}
        </p>
        <div class="mt-1 text-xs text-right text-gray-500 dark:text-gray-400">
          {{ aboutCount }}/{{ aboutMax }}
        </div>
      </div>

      <div class="relative">
        <textarea
          ref="goalsRef"
          v-model="model.goals"
          class="peer w-full px-4 pt-7 pb-3 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 resize-none min-h-[140px] leading-relaxed"
          placeholder=" "
          :maxlength="goalsMax"
          @input="autoResize($event)"
        />
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.about.goals.label') }}
        </label>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          {{ t('profile.about.goals.placeholder') }}
        </p>
        <div class="mt-1 text-xs text-right text-gray-500 dark:text-gray-400">
          {{ goalsCount }}/{{ goalsMax }}
        </div>
      </div>

      <div class="relative">
        <textarea
          ref="structureRef"
          v-model="model.structure"
          class="peer w-full px-4 pt-7 pb-3 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 resize-none min-h-[140px] leading-relaxed"
          placeholder=" "
          :maxlength="structureMax"
          @input="autoResize($event)"
        />
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-4 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.about.structure.label') }}
        </label>
        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">
          {{ t('profile.about.structure.placeholder') }}
        </p>
        <div class="mt-1 text-xs text-right text-gray-500 dark:text-gray-400">
          {{ structureCount }}/{{ structureMax }}
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, nextTick } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const model = defineModel<{
  about: string;
  goals: string;
  structure: string;
}>({ required: true });

const aboutRef = ref<HTMLTextAreaElement | null>(null);
const goalsRef = ref<HTMLTextAreaElement | null>(null);
const structureRef = ref<HTMLTextAreaElement | null>(null);

const aboutMax = 2000;
const goalsMax = 1500;
const structureMax = 1500;

const aboutCount = computed(() => (model.value.about || "").length);
const goalsCount = computed(() => (model.value.goals || "").length);
const structureCount = computed(() => (model.value.structure || "").length);

function autoResize(e: Event) {
  const el = e.target as HTMLTextAreaElement;
  if (!el) return;
  el.style.height = "auto";
  el.style.height = `${el.scrollHeight}px`;
}

function resizeAll() {
  [aboutRef.value, goalsRef.value, structureRef.value].forEach(el => {
    if (!el) return;
    el.style.height = "auto";
    el.style.height = `${el.scrollHeight}px`;
  });
}

onMounted(async () => {
  await nextTick();
  resizeAll();
});
</script>
