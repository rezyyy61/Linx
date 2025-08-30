<script setup lang="ts">
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'
import {computed, unref} from "vue";

const { t, te } = useI18n()
const tr = (k: string) => (te(`post.${k}`) ? t(`post.${k}`) : t(k))

const props = defineProps<{
  visibility: 'public'|'private'|'friends' | any
  status: 'published'|'draft'|'archived' | any
  publishedAt: string | null | any
}>()
const emit = defineEmits<{
  (e:'update:visibility', v:'public'|'private'|'friends'): void
  (e:'update:status', v:'published'|'draft'|'archived'): void
  (e:'update:publishedAt', v:string|null): void
}>()
const visibility = computed({
  get: () => unref(props.visibility),
  set: (v: 'public'|'private'|'friends') => emit('update:visibility', v),
})
const status = computed({
  get: () => unref(props.status),
  set: (v: 'published'|'draft'|'archived') => emit('update:status', v),
})
const publishedAt = computed({
  get: () => unref(props.publishedAt),
  set: (v: string|null) => emit('update:publishedAt', v),
})
</script>

<template>
  <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ tr('form.visibility') }}</label>
      <div class="relative">
        <Icon
          icon="solar:eye-bold-duotone"
          class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500"
        />
        <select
          v-model="visibility"
          class="w-full appearance-none rounded-xl border border-gray-300 bg-white pl-10 pr-10 py-2 text-sm outline-none ring-0 transition focus:border-gray-400 focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
          <option value="public">
            {{ tr('visibility.public') }}
          </option>
          <option value="private">
            {{ tr('visibility.private') }}
          </option>
          <option value="friends">
            {{ tr('visibility.friends') }}
          </option>
        </select>
        <Icon
          icon="solar:alt-arrow-down-bold-duotone"
          class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500"
        />
      </div>
    </div>

    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ tr('form.status') }}</label>
      <div class="relative">
        <Icon
          icon="solar:checklist-bold-duotone"
          class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500"
        />
        <select
          v-model="status"
          class="w-full appearance-none rounded-xl border border-gray-300 bg-white pl-10 pr-10 py-2 text-sm outline-none ring-0 transition focus:border-gray-400 focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
          <option value="published">
            {{ tr('status.published') }}
          </option>
          <option value="draft">
            {{ tr('status.draft') }}
          </option>
          <option value="archived">
            {{ tr('status.archived') }}
          </option>
        </select>
        <Icon
          icon="solar:alt-arrow-down-bold-duotone"
          class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500"
        />
      </div>
    </div>

    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ tr('form.publish_at') }}</label>
      <div class="relative">
        <Icon
          icon="solar:calendar-bold-duotone"
          class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400 dark:text-gray-500"
        />
        <input
          v-model="publishedAt"
          type="datetime-local"
          class="w-full rounded-xl border border-gray-300 bg-white pl-10 pr-3 py-2 text-sm outline-none ring-0 transition focus:border-gray-400 focus:ring-2 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
        >
      </div>
    </div>
  </div>
</template>
