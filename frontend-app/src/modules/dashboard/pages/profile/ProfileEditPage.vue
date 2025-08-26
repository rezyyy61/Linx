<template>
  <div class="w-full flex justify-center">
    <div class="w-full max-w-5xl mx-auto grid grid-cols-1 gap-8 items-start">
      <div class="space-y-6">
        <div class="flex items-center justify-between">
          <h1 class="text-2xl font-semibold text-red-500 my-4">
            {{ t('profile.politicalTitle') }}
          </h1>
        </div>

        <ProfileStepper v-model="step" />

        <div class="bg-white/85 dark:bg-gray-800/80 rounded-2xl border border-slate-200/60 dark:border-slate-800 backdrop-blur p-6 md:p-8 min-h-[70dvh] space-y-6">
          <!-- Step 1: General -->
          <div
            v-show="step === 'general'"
            class="space-y-6"
          >
            <div class="grid gap-6 md:grid-cols-[280px_1fr]">
              <div class="space-y-4">
                <LogoTile v-model:logo-url="state.media.logoUrl" />
              </div>

              <div class="grid gap-4">
                <BasicSection
                  v-model="state.basics"
                  :show-color="false"
                />
              </div>
            </div>
          </div>

          <!-- Step 2 -->
          <div
            v-show="step === 'ideology'"
            class="space-y-6"
          >
            <AboutSection
              v-model="state.translations[state.currentLocale]"
              v-model:location="state.basics.location"
              v-model:founded-year="state.basics.foundedYear"
            />
          </div>

          <!-- Step 3 -->
          <div
            v-show="step === 'description'"
            class="space-y-6"
          >
            <DescriptionSection v-model="state.translations[state.currentLocale]" />
          </div>

          <!-- Step 4 -->
          <div
            v-show="step === 'links'"
            class="space-y-6"
          >
            <LinksSection v-model="linksSimple" />
            <MediaSection
              v-model:logo-url="state.media.logoUrl"
              v-model:files="state.media.files"
            />
          </div>

          <!-- Footer actions -->
          <div class="pt-4 mt-2 border-t border-slate-200/60 dark:border-slate-800 flex justify-end">
            <button
              class="inline-flex items-center gap-2 px-4 md:px-5 py-2.5 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 disabled:opacity-60"
              :disabled="saving"
              @click="onSave"
            >
              <Icon
                icon="mdi:content-save-outline"
                width="18"
              />
              <span>{{ saving ? t('common.saving') : t('profile.summary.save') }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed, onMounted, ref} from "vue";
import { Icon } from "@iconify/vue";
import { useI18n } from "vue-i18n";
import { useProfileUi } from "./useProfileUi";

import ProfileStepper from "./components/ProfileStepper.vue";
import LogoTile from "./components/LogoTile.vue";
import BasicSection from "./components/BasicSection.vue";
import AboutSection from "./components/AboutSection.vue";
import LinksSection from "./components/LinksSection.vue";
import MediaSection from "./components/MediaSection.vue";
import DescriptionSection from "@/modules/dashboard/pages/profile/components/DescriptionSection.vue";

const { t } = useI18n();
const { state, load, save, linksSimple } = useProfileUi();

const step = ref<"general" | "ideology" | "description" | "links">("general");
const saving = computed(() => state.saving);

onMounted(() => {
  load();
});

const onSave = async () => {
  await save();
};
</script>
