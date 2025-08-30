<script setup lang="ts">
import { computed, unref } from 'vue'
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'

const { t, te } = useI18n()
const tr = (k: string) => te(`post.${k}`) ? t(`post.${k}`) : t(k)

type Item = {
  key:string
  type:'existing'|'task'
  id?:number
  uid?:string
  url?:string
  mime?:string
  width?:number|null
  height?:number|null
  progress?:number
  status?:string
}

const props = defineProps<{ items: Item[] | any }>()
const emit = defineEmits<{
  (e:'remove', key:string): void
  (e:'move', index:number, dir:-1|1): void
  (e:'reorder', keys:string[]): void
}>()

const list = computed<Item[]>(() => {
  const v = props.items as any
  if (Array.isArray(v)) return v
  const val = unref(v)
  return Array.isArray(val) ? val : []
})

function remove(key:string) { emit('remove', key) }
function move(i:number, dir:-1|1) { emit('move', i, dir) }

function chipClasses(st?: string) {
  const s = (st ?? '').toLowerCase()
  if (['rejected','blocked'].includes(s)) return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200'
  if (['failed','error'].includes(s)) return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200'
  if (['ready','done','processed','complete','completed'].includes(s)) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200'
  if (['scanning','uploaded','queued','pending','started','processing'].includes(s)) return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}
</script>

<template>
  <div
    v-if="list.length===0"
    class="rounded-xl border border-dashed border-gray-300 p-4 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300"
  >
    {{ tr('media.gallery.empty') }}
  </div>

  <ul
    v-else
    class="grid grid-cols-2 gap-3 md:grid-cols-3 lg:grid-cols-4"
  >
    <li
      v-for="(it,i) in list"
      :key="it?.key || i"
      class="group overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-700 dark:bg-gray-900"
    >
      <div class="relative">
        <div class="flex aspect-video items-center justify-center bg-gray-100 dark:bg-gray-800">
          <img
            v-if="it?.url && it?.mime?.startsWith('image/')"
            :src="it.url"
            :alt="tr('media.gallery.image_alt')"
            class="h-full w-full object-cover"
          >
          <video
            v-else-if="it?.url && it?.mime?.startsWith('video/')"
            :src="it.url"
            class="h-full w-full"
          />
          <div
            v-else
            class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400"
          >
            <Icon
              icon="solar:gallery-wide-bold-duotone"
              class="h-5 w-5"
            />
            <span>{{ tr('media.gallery.preview') }}</span>
          </div>
        </div>

        <div
          v-if="it?.status && it?.status !== 'ready'"
          class="absolute inset-x-0 bottom-0"
        >
          <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700">
            <div
              class="h-1.5 bg-indigo-500 transition-all dark:bg-indigo-400"
              :style="{ width: ((it?.progress ?? 0)) + '%' }"
            />
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between gap-2 p-3">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
          :class="chipClasses(it?.status)"
        >
          <Icon
            v-if="(it?.status ?? '').toLowerCase()==='ready'"
            icon="solar:check-circle-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else-if="['rejected','blocked'].includes((it?.status ?? '').toLowerCase())"
            icon="solar:forbidden-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else-if="['failed','error'].includes((it?.status ?? '').toLowerCase())"
            icon="solar:bug-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else
            icon="solar:loading-3-bold-duotone"
            class="h-4 w-4"
          />
          <span class="capitalize">{{ (it?.status ?? 'ready') }}</span>
        </span>

        <div class="flex items-center gap-1">
          <button
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-700 transition hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            :disabled="i===0"
            :aria-label="tr('media.gallery.move_up')"
            @click="move(i,-1)"
          >
            <Icon
              icon="solar:alt-arrow-up-bold-duotone"
              class="h-4 w-4"
            />
          </button>
          <button
            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-xs text-gray-700 transition hover:bg-gray-50 disabled:opacity-50 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 dark:hover:bg-gray-800"
            :disabled="i===list.length-1"
            :aria-label="tr('media.gallery.move_down')"
            @click="move(i,1)"
          >
            <Icon
              icon="solar:alt-arrow-down-bold-duotone"
              class="h-4 w-4"
            />
          </button>
          <button
            class="inline-flex items-center justify-center rounded-lg border border-red-300 bg-white p-2 text-xs text-red-600 transition hover:bg-red-50 dark:border-red-700 dark:bg-gray-900 dark:text-red-300 dark:hover:bg-red-900/30"
            :aria-label="tr('actions.remove')"
            @click="remove(it.key)"
          >
            <Icon
              icon="solar:trash-bin-minimalistic-bold-duotone"
              class="h-4 w-4"
            />
          </button>
        </div>
      </div>
    </li>
  </ul>
</template>
