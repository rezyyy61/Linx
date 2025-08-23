<template>
  <div class="bg-white/80 dark:bg-gray-800/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 md:p-8 backdrop-blur">
    <div class="grid gap-5 md:grid-cols-2">
      <div class="relative">
        <input
          v-model="model.tagline"
          placeholder=" "
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          maxlength="160"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
           peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
           peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.about.tagline.placeholder') }}
        </label>
      </div>


      <div class="relative">
        <input
          v-model="model.activities"
          placeholder=""
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          maxlength="200"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.about.activities.label') }}
        </label>
      </div>

      <div class="relative">
        <input
          v-model="locationModel"
          placeholder=""
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.basic.location.label') }}
        </label>
      </div>

      <div class="relative">
        <input
          v-model="foundedYearModel"
          placeholder=""
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          type="number"
          inputmode="numeric"
          :min="1800"
          :max="yearMax"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.basic.foundedYear.label') }}
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";

const { t } = useI18n();

const model = defineModel<{ tagline: string; activities: string }>({ required: true });
const locationModel = defineModel<string>("location", { required: true });
const foundedYearModel = defineModel<number | string>("foundedYear", { required: true });

const yearMax = computed(() => new Date().getFullYear());
</script>
