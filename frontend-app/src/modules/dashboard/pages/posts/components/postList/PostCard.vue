<script setup lang="ts">
import {computed, ref} from 'vue'
import { Icon } from '@iconify/vue'
import { useI18n } from 'vue-i18n'
import { excerptFromHtml, directionFor, isEmptyHtml } from '@/utils/text-utils'
import {useMediaStore} from "@/stores/post/post.media";

type MediaItem = { id: number; url?: string; mime_type?: string; width?: number | null; height?: number | null }
type Post = {
  id: number
  user_id: number
  content: string | null
  visibility: 'public'|'private'|'friends'
  status: 'draft'|'published'|'archived'
  published_at: string | null
  created_at?: string
  updated_at?: string
  media: MediaItem[]
}

const props = defineProps<{ post: Post; clickable?: boolean; mine?: boolean }>()
const emit = defineEmits<{ (e:'open'): void; (e:'edit'): void; (e:'delete'): void }>()

const mediaStore = useMediaStore()
const deleting = ref(false)

const { t, te, locale } = useI18n()
const tr = (k: string, args?: any) => te(`post.${k}`) ? t(`post.${k}`, args) : t(k, args)

const firstMedia = computed(() => props.post.media?.[0])
const hasMedia = computed(() => !!firstMedia.value?.url)
const mediaCount = computed(() => Array.isArray(props.post.media) ? props.post.media.length : 0)
const extraCount = computed(() => Math.max(0, mediaCount.value - 1))

const kind = computed<'image'|'video'|'audio'|'document'|'none'>(() => {
  const m = firstMedia.value
  const mt = (m?.mime_type || '').toLowerCase()
  if (!m?.url) return 'none'
  if (mt.startsWith('image/')) return 'image'
  if (mt.startsWith('video/')) return 'video'
  if (mt.startsWith('audio/')) return 'audio'
  return 'document'
})

const typeCounts = computed(() => {
  const c = { image: 0, video: 0, audio: 0, document: 0 }
  for (const m of (props.post.media || [])) {
    const mt = (m?.mime_type || '').toLowerCase()
    if (mt.startsWith('image/')) c.image++
    else if (mt.startsWith('video/')) c.video++
    else if (mt.startsWith('audio/')) c.audio++
    else c.document++
  }
  return [
    { key: 'image',   count: c.image,   icon: 'solar:gallery-wide-bold-duotone',  label: tr('card.count.images', { count: c.image }) },
    { key: 'video',   count: c.video,   icon: 'solar:play-bold-duotone',          label: tr('card.count.videos', { count: c.video }) },
    { key: 'audio',   count: c.audio,   icon: 'solar:music-note-2-bold-duotone',  label: tr('card.count.audios', { count: c.audio }) },
    { key: 'document',count: c.document,icon: 'solar:document-bold-duotone',      label: tr('card.count.documents', { count: c.document }) },
  ].filter(x => x.count > 0)
})

function parseDateFlexible(raw?: string | null): Date | null {
  if (!raw) return null
  const ts = Date.parse(raw)
  if (!Number.isNaN(ts)) return new Date(ts)
  const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})[T ](\d{2}):(\d{2})(?::(\d{2}))?$/)
  if (m) {
    const [, y, mo, d, h, mi, s] = m
    return new Date(Number(y), Number(mo) - 1, Number(d), Number(h), Number(mi), s ? Number(s) : 0)
  }
  return null
}

const rawDate = computed(() => {
  if (props.post.status === 'draft') return props.post.created_at || null
  return props.post.published_at || props.post.created_at || null
})

const displayDate = computed(() => {
  const d = parseDateFlexible(rawDate.value)
  if (!d) return ''
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(d)
})

const excerpt = computed(() => {
  const html = props.post.content || ''
  if (!html || isEmptyHtml(html)) return ''
  return excerptFromHtml(html, 160, { preserveWords: true, preserveLineBreaks: false })
})
const textDir = computed(() => directionFor(props.post.content || ''))

function onOpen() { emit('open') }
function onEdit(e: Event) { e.stopPropagation(); emit('edit') }
async function onDelete(e: Event) {
  e.stopPropagation()
  if (deleting.value) return
  deleting.value = true
  try {
    const postId = Number(props.post?.id)
    const media = Array.isArray(props.post?.media) ? props.post.media : []
    if (postId && media.length) {
      await Promise.allSettled(
        media.map(m => mediaStore.detachFromPost(Number(m.id), postId, 'post'))
      )
    }
    emit('delete')
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div
    class="group relative flex h-full cursor-default flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md dark:border-gray-800 dark:bg-gray-900"
    @click="props.clickable && onOpen()"
  >
    <div class="p-4">
      <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
        <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 dark:bg-gray-800">
          <Icon
            icon="solar:eye-bold-duotone"
            class="h-4 w-4"
          />
          <span>{{ tr(`visibility.${post.visibility}`) }}</span>
        </span>
        <span class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 dark:bg-gray-800">
          <Icon
            icon="solar:checklist-bold-duotone"
            class="h-4 w-4"
          />
          <span>{{ tr(`status.${post.status}`) }}</span>
        </span>
      </div>
    </div>

    <div class="relative w-full overflow-hidden bg-gray-100 dark:bg-gray-800">
      <div class="aspect-[4/3] w-full">
        <img
          v-if="kind==='image'"
          :src="firstMedia?.url"
          alt=""
          class="h-full w-full object-cover"
          loading="lazy"
          decoding="async"
        >
        <video
          v-else-if="kind==='video'"
          :src="firstMedia?.url"
          class="h-full w-full object-cover"
          muted
        />
        <div
          v-else
          class="flex h-full w-full items-center justify-center"
        >
          <Icon
            :icon="kind==='audio' ? 'solar:music-note-2-bold-duotone' : (kind==='document' ? 'solar:document-bold-duotone' : 'solar:gallery-wide-bold-duotone')"
            class="h-10 w-10 text-gray-400 dark:text-gray-500"
          />
        </div>

        <div
          v-if="kind==='video'"
          class="pointer-events-none absolute inset-0 flex items-center justify-center"
        >
          <span class="rounded-full bg-black/40 p-2 backdrop-blur-sm">
            <Icon
              icon="solar:play-bold-duotone"
              class="h-6 w-6 text-white"
            />
          </span>
        </div>

        <div
          v-if="extraCount > 0"
          class="absolute right-2 top-2 select-none rounded-full bg-black/60 px-2.5 py-1 text-xs font-medium text-white backdrop-blur-sm"
          :title="tr('card.more', { count: extraCount })"
        >
          +{{ extraCount }}
        </div>

        <div
          v-if="typeCounts.length"
          class="absolute left-2 bottom-2 flex gap-1"
        >
          <span
            v-for="it in typeCounts"
            :key="it.key"
            class="inline-flex items-center gap-1 rounded-full bg-black/55 px-2 py-0.5 text-[11px] font-medium text-white backdrop-blur-sm"
            :title="it.label"
          >
            <Icon
              :icon="it.icon"
              class="h-3.5 w-3.5"
            />
            <span>{{ it.count }}</span>
          </span>
        </div>
      </div>
    </div>

    <div class="px-4 pt-3">
      <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        <div class="flex items-center gap-2">
          <Icon
            icon="solar:calendar-bold-duotone"
            class="h-4 w-4"
          />
          <span :title="rawDate || ''">{{ displayDate || '—' }}</span>
        </div>

        <div class="flex items-center gap-2">
          <span
            v-if="hasMedia"
            class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"
          >
            <Icon
              icon="solar:gallery-wide-bold-duotone"
              class="h-4 w-4"
            />
            <span>{{ tr('card.media') }}</span>
          </span>
          <span
            v-else
            class="inline-flex items-center gap-1 rounded-md bg-gray-100 px-2 py-0.5 text-gray-600 dark:bg-gray-800 dark:text-gray-300"
          >
            <Icon
              icon="solar:pen-bold-duotone"
              class="h-4 w-4"
            />
            <span>{{ tr('card.text_only') }}</span>
          </span>
        </div>
      </div>
    </div>

    <div class="p-4 pt-2">
      <div class="min-h-[3.75rem]">
        <p
          v-if="excerpt"
          :dir="textDir"
          class="line-clamp-3 text-sm leading-6 text-gray-800 dark:text-gray-100"
        >
          {{ excerpt }}
        </p>
        <p
          v-else
          class="invisible line-clamp-3 text-sm leading-6"
        >
          &nbsp;
        </p>
      </div>
    </div>

    <div
      v-if="mine"
      class="px-4 pb-4 pt-0 flex items-center justify-end gap-1"
    >
      <button
        type="button"
        class="rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800"
        @click.stop="onOpen"
      >
        <Icon
          icon="mdi:eye-outline"
          class="h-4 w-4 text-gray-600 dark:text-gray-300"
        />
      </button>
      <button
        type="button"
        class="rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800"
        @click.stop="onEdit"
      >
        <Icon
          icon="mdi:pencil-outline"
          class="h-4 w-4 text-gray-600 dark:text-gray-300"
        />
      </button>
      <button
        type="button"
        class="rounded-lg p-1.5 hover:bg-gray-100 dark:hover:bg-gray-800"
        @click.stop="onDelete"
      >
        <Icon
          icon="mdi:trash-can-outline"
          class="h-4 w-4 text-red-600"
        />
      </button>
    </div>
  </div>
</template>
