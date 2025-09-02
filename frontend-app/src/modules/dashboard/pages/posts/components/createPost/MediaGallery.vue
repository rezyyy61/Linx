<script setup lang="ts">
import { computed, unref, ref, watch, onMounted, onUnmounted } from 'vue'
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

const current = ref(0)
watch(list, (nv) => {
  if (current.value >= nv.length) current.value = Math.max(0, nv.length - 1)
})

function next() {
  if (!list.value.length) return
  current.value = (current.value + 1) % list.value.length
}
function prev() {
  if (!list.value.length) return
  current.value = (current.value - 1 + list.value.length) % list.value.length
}

function removeCurrent() {
  const it = list.value[current.value]
  if (!it) return
  emit('remove', it.key)
}

function moveCurrent(dir:-1|1) {
  if (!list.value.length) return
  emit('move', current.value, dir)
  const to = current.value + dir
  if (to >= 0 && to < list.value.length) current.value = to
}

function chipClasses(st?: string) {
  const s = (st ?? '').toLowerCase()
  if (['rejected','blocked'].includes(s)) return 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-200'
  if (['failed','error'].includes(s)) return 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-200'
  if (['ready','done','processed','complete','completed'].includes(s)) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200'
  if (['scanning','uploaded','queued','pending','started','processing'].includes(s)) return 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200'
  return 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200'
}

const loaded = ref<Record<string, boolean>>({})
function markLoaded(k:string) { loaded.value[k] = true }

function labelFor(it:Item) {
  const m = (it.mime ?? '').toLowerCase()
  if (m.startsWith('image/')) return 'Image'
  if (m.startsWith('video/')) return 'Video'
  if (m.startsWith('audio/')) return 'Audio'
  return 'File'
}

function onKey(e: KeyboardEvent) {
  if (!list.value.length) return
  if (e.key === 'ArrowRight') next()
  else if (e.key === 'ArrowLeft') prev()
}

onMounted(() => window.addEventListener('keydown', onKey))
onUnmounted(() => window.removeEventListener('keydown', onKey))
</script>

<template>
  <div
    v-if="list.length===0"
    class="rounded-2xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-300"
  >
    {{ tr('media.gallery.empty') }}
  </div>

  <div
    v-else
    class="relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900"
  >
    <div class="relative">
      <div class="flex aspect-video items-center justify-center bg-gray-50 dark:bg-gray-800">
        <template v-if="list[current]">
          <img
            v-if="list[current]?.url && list[current]?.mime?.startsWith('image/')"
            :src="list[current].url"
            :alt="tr('media.gallery.image_alt')"
            class="h-full w-full object-contain transition-opacity duration-300"
            :class="loaded[list[current].key] ? 'opacity-100' : 'opacity-0'"
            loading="lazy"
            @load="markLoaded(list[current].key)"
          >
          <video
            v-else-if="list[current]?.url && list[current]?.mime?.startsWith('video/')"
            :src="list[current].url"
            class="max-h-full w-full object-contain"
            controls
            playsinline
            preload="metadata"
          />
          <div
            v-else
            class="flex flex-col items-center justify-center gap-2 p-6 text-xs text-gray-500 dark:text-gray-400"
          >
            <Icon
              icon="solar:gallery-wide-bold-duotone"
              class="h-7 w-7"
            />
            <span>{{ tr('media.gallery.preview') }}</span>
          </div>
        </template>
      </div>

      <div class="absolute inset-0 pointer-events-none bg-gradient-to-b from-black/0 via-black/0 to-black/20" />

      <div class="absolute left-3 top-3 flex items-center gap-2">
        <span
          v-if="list[current]?.status"
          class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium backdrop-blur"
          :class="chipClasses(list[current]?.status)"
        >
          <Icon
            v-if="(list[current]?.status ?? '').toLowerCase()==='ready'"
            icon="solar:check-circle-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else-if="['rejected','blocked'].includes((list[current]?.status ?? '').toLowerCase())"
            icon="solar:forbidden-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else-if="['failed','error'].includes((list[current]?.status ?? '').toLowerCase())"
            icon="solar:bug-bold-duotone"
            class="h-4 w-4"
          />
          <Icon
            v-else
            icon="solar:loading-3-bold-duotone"
            class="h-4 w-4 animate-spin-slow"
          />
          <span class="capitalize">{{ (list[current]?.status ?? 'ready') }}</span>
        </span>
      </div>

      <div
        v-if="list[current]?.status && list[current]?.status !== 'ready'"
        class="absolute inset-x-0 bottom-0"
      >
        <div class="h-1.5 w-full bg-gray-200 dark:bg-gray-700">
          <div
            class="h-1.5 bg-indigo-500 transition-all dark:bg-indigo-400"
            :style="{ width: ((list[current]?.progress ?? 0)) + '%' }"
          />
        </div>
      </div>

      <button
        type="button"
        class="absolute left-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded-full bg-white/90 p-2 text-gray-700 shadow hover:bg-white dark:bg-gray-800/80 dark:text-gray-200"
        :disabled="list.length<=1"
        aria-label="Previous"
        @click="prev"
      >
        <Icon
          icon="solar:alt-arrow-left-bold-duotone"
          class="h-6 w-6"
        />
      </button>

      <button
        type="button"
        class="absolute right-2 top-1/2 -translate-y-1/2 inline-flex items-center justify-center rounded-full bg-white/90 p-2 text-gray-700 shadow hover:bg-white dark:bg-gray-800/80 dark:text-gray-200"
        :disabled="list.length<=1"
        aria-label="Next"
        @click="next"
      >
        <Icon
          icon="solar:alt-arrow-right-bold-duotone"
          class="h-6 w-6"
        />
      </button>
    </div>

    <div class="flex items-center justify-between gap-2 border-t border-gray-100 px-3 py-2 text-xs dark:border-gray-800">
      <div class="flex items-center gap-2">
        <span class="rounded-md bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
          {{ labelFor(list[current]) }}
        </span>
        <span
          v-if="list[current]?.width && list[current]?.height"
          class="text-gray-500 dark:text-gray-400"
        >
          {{ list[current]?.width }}×{{ list[current]?.height }}
        </span>
        <span class="text-gray-500 dark:text-gray-400">• {{ current + 1 }} / {{ list.length }}</span>
      </div>

      <div class="flex items-center gap-1">
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-lg p-1.5 text-xs text-gray-700 transition hover:bg-gray-100 disabled:opacity-50 dark:text-gray-200 dark:hover:bg-gray-800"
          :disabled="current===0"
          :aria-label="tr('media.gallery.move_up')"
          @click="moveCurrent(-1)"
        >
          <Icon
            icon="solar:alt-arrow-up-bold-duotone"
            class="h-4 w-4"
          />
        </button>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-lg p-1.5 text-xs text-gray-700 transition hover:bg-gray-100 disabled:opacity-50 dark:text-gray-200 dark:hover:bg-gray-800"
          :disabled="current===list.length-1"
          :aria-label="tr('media.gallery.move_down')"
          @click="moveCurrent(1)"
        >
          <Icon
            icon="solar:alt-arrow-down-bold-duotone"
            class="h-4 w-4"
          />
        </button>
        <button
          type="button"
          class="inline-flex items-center justify-center rounded-lg p-1.5 text-xs text-red-600 transition hover:bg-red-50 dark:text-red-300 dark:hover:bg-red-900/30"
          :aria-label="tr('actions.remove')"
          @click="removeCurrent"
        >
          <Icon
            icon="solar:trash-bin-minimalistic-bold-duotone"
            class="h-4 w-4"
          />
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@keyframes spin-slow { from { transform: rotate(0deg) } to { transform: rotate(360deg) } }
.animate-spin-slow { animation: spin-slow 1.6s linear infinite }
</style>
