<template>
  <div class="bg-white/80 dark:bg-gray-800/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 md:p-8 backdrop-blur">
    <div class="grid gap-5 md:grid-cols-2">
      <div
        v-for="f in fields"
        :key="f.key"
        class="relative"
      >
        <input
          :value="model[f.key]"
          :type="f.type"
          class="peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
          placeholder=" "
          dir="ltr"
          @input="onInput(f.key, ($event.target as HTMLInputElement).value)"
        >
        <label
          class="absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80
                 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base
                 peer-focus:top-2.5 peer-focus:text-sm"
        >
          {{ f.label }}
        </label>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
type SimpleLinksModel = {
  website: string;
  email: string;
  phone: string;
  telegram: string;
  instagram: string;
  facebook: string;
  twitter: string;
  custom: string;
};

const model = defineModel<SimpleLinksModel>({ required: true });

function onInput(key: keyof SimpleLinksModel, val: string) {
  model.value = { ...model.value, [key]: val } as SimpleLinksModel;
}

const fields = [
  { key: "website", label: "Website", type: "url" },
  { key: "email", label: "Email", type: "email" },
  { key: "phone", label: "Phone", type: "tel" },
  { key: "telegram", label: "Telegram", type: "text" },
  { key: "instagram", label: "Instagram", type: "text" },
  { key: "facebook", label: "Facebook", type: "text" },
  { key: "twitter", label: "Twitter", type: "text" },
  { key: "custom", label: "Custom link", type: "url" },
] as const;
</script>
