<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { Icon } from '@iconify/vue'
import { useI18n } from 'vue-i18n'
import { usePostHeader } from '@/modules/dashboard/pages/posts/usePostHeader'

defineOptions({ name: 'PostHeader' })
const emit = defineEmits<{ (e:'apply', params:Record<string,any>):void }>()
const { t, te } = useI18n()
const tr = (k: string) => te(`post.postlist.header.${k}`) ? t(`post.postlist.header.${k}`) : k

const fh = usePostHeader()
// نکته مهم: اینجا refها را جدا می‌کنیم تا v-model روی خود ref بنشیند نه روی خاصیت یک آبجکت
const { q, visibility, status, dateFrom, dateTo, sort, toApiParams } = fh

const searchTimer = ref<ReturnType<typeof setTimeout> | null>(null)

function emitApply() {
  emit('apply', toApiParams())
}
function onSearchInput() {
  if (searchTimer.value) clearTimeout(searchTimer.value)
  searchTimer.value = setTimeout(() => {
    searchTimer.value = null
    emitApply()
  }, 350)
}
function onImmediateChange() {
  emitApply()
}

onMounted(() => {
  emitApply()
})
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-900">
    <div class="flex flex-wrap items-end gap-3">
      <div class="min-w-[220px] flex-1">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('search') }}</label>
        <div class="relative">
          <Icon
            icon="mdi:magnify"
            class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400"
          />
          <input
            v-model="q"
            type="search"
            :placeholder="tr('search_placeholder')"
            class="w-full rounded-xl border border-gray-300 bg-white py-2 pl-10 pr-8 text-sm outline-none transition focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
            @input="onSearchInput"
          >
        </div>
      </div>

      <div class="w-36">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('visibility') }}</label>
        <select
          v-model="visibility"
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          @change="onImmediateChange"
        >
          <option value="all">
            {{ tr('visibility_all') }}
          </option>
          <option value="public">
            {{ tr('visibility_public') }}
          </option>
          <option value="private">
            {{ tr('visibility_private') }}
          </option>
          <option value="friends">
            {{ tr('visibility_friends') }}
          </option>
        </select>
      </div>

      <div class="w-40">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('status') }}</label>
        <select
          v-model="status"
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          @change="onImmediateChange"
        >
          <option value="all">
            {{ tr('status_all') }}
          </option>
          <option value="draft">
            {{ tr('status_draft') }}
          </option>
          <option value="published">
            {{ tr('status_published') }}
          </option>
        </select>
      </div>

      <div class="w-40">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('date_from') }}</label>
        <input
          v-model="dateFrom"
          type="date"
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          @change="onImmediateChange"
        >
      </div>

      <div class="w-40">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('date_to') }}</label>
        <input
          v-model="dateTo"
          type="date"
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          @change="onImmediateChange"
        >
      </div>

      <div class="w-40">
        <label class="mb-1 block text-[11px] font-medium text-gray-500 dark:text-gray-400">{{ tr('sort') }}</label>
        <select
          v-model="sort"
          class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:border-gray-400 focus:ring-2 focus:ring-emerald-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100"
          @change="onImmediateChange"
        >
          <option value="newest">
            {{ tr('sort_newest') }}
          </option>
          <option value="oldest">
            {{ tr('sort_oldest') }}
          </option>
        </select>
      </div>
    </div>
  </div>
</template>
