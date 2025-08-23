<template>
  <div class="space-y-6">
    <div class="grid gap-5 md:grid-cols-1">
      <div class="relative">
        <input
          v-model="model.displayName"
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          placeholder=" "
          maxlength="80"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.basic.displayName.label') }}
        </label>
        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400 text-right">
          {{ nameCount }}/80
        </div>
      </div>

      <div class="relative">
        <input
          v-model="slugDraft"
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 font-mono focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          dir="ltr"
          placeholder=" "
          @input="onSlugInput"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/80 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ t('profile.basic.slug.label') }}
        </label>
      </div>
    </div>

    <div>
      <label class="text-sm mb-2 block">{{ t('profile.basic.entityType.label') }}</label>
      <EntityTypePicker v-model="model.entityType" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import EntityTypePicker from "./EntityTypePicker.vue";

const { t } = useI18n();
const model = defineModel<{ displayName: string; slug: string; entityType: string }>({ required: true });

const nameCount = computed(() => (model.value.displayName || "").length);

const slugDraft = ref(model.value.slug || "");
const autoSlug = ref(!model.value.slug);

watch(
  () => model.value.slug,
  (v) => {
    const next = v || "";
    if (next !== slugDraft.value) slugDraft.value = next;
    autoSlug.value = !next;
  },
  { immediate: true }
);

watch(
  () => model.value.displayName,
  (v) => {
    if (autoSlug.value && !model.value.slug) {
      const s = slugify(v || "");
      slugDraft.value = s;
      model.value.slug = s;
    }
  }
);

function onSlugInput() {
  autoSlug.value = false;
  const s = slugify(slugDraft.value || "");
  slugDraft.value = s;
  model.value.slug = s;
}

function slugify(s: string) {
  return String(s)
    .normalize("NFKD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/[^\w\s-]/g, "")
    .trim()
    .replace(/\s+/g, "-")
    .replace(/-+/g, "-")
    .toLowerCase();
}
</script>

