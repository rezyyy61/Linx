<template>
  <section class="space-y-6">
    <div class="flex items-center gap-2">
      <Icon
        icon="mdi:eye-outline"
        class="w-6 h-6 text-gray-700 dark:text-gray-200"
      />
      <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100">
        Post
      </h2>
      <span class="text-sm text-gray-500 dark:text-gray-400">#{{ id }}</span>
      <div
        v-if="post"
        class="ml-auto flex items-center gap-2"
      >
        <button
          type="button"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-800 hover:bg-gray-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-800"
          @click="goEdit"
        >
          <Icon
            icon="mdi:pencil-outline"
            class="w-4 h-4"
          />
          <span>Edit</span>
        </button>
        <button
          type="button"
          class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-red-300 text-red-700 hover:bg-red-50 dark:text-red-300 dark:border-red-600 dark:hover:bg-red-900/20"
          @click="onDelete"
        >
          <Icon
            icon="mdi:trash-can-outline"
            class="w-4 h-4"
          />
          <span>Delete</span>
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="rounded-2xl overflow-hidden border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700"
    >
      <div class="h-60 bg-gray-100 animate-pulse dark:bg-gray-800" />
      <div class="p-6 space-y-3">
        <div class="h-5 w-2/3 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
        <div class="h-4 w-full bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
        <div class="h-4 w-5/6 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
      </div>
    </div>

    <div
      v-else-if="post"
      class="rounded-2xl overflow-hidden border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700"
    >
      <PostMediaViewer
        v-if="viewerItems.length"
        :key="post.id"
        :items="viewerItems"
        :tile-height="560"
      />
      <div
        v-else
        class="bg-gray-50 dark:bg-gray-800/40"
      >
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2 p-2">
          <div
            v-for="it in fallbackItems"
            :key="it.id"
            class="relative w-full overflow-hidden rounded-lg bg-gray-100 dark:bg-gray-800"
          >
            <div class="aspect-[4/3] w-full">
              <img
                v-if="it.kind==='image'"
                :src="it.url"
                alt=""
                class="h-full w-full object-cover"
                loading="lazy"
                decoding="async"
              >
              <video
                v-else-if="it.kind==='video'"
                :src="it.url"
                class="h-full w-full object-cover"
                controls
              />
              <audio
                v-else-if="it.kind==='audio'"
                :src="it.url"
                class="w-full absolute left-0 right-0 bottom-2"
                controls
              />
              <div
                v-else
                class="flex h-full w-full items-center justify-center"
              >
                <Icon
                  icon="solar:document-bold-duotone"
                  class="h-10 w-10 text-gray-400 dark:text-gray-500"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="p-6 space-y-4">
        <div class="flex items-center gap-2">
          <span :class="badgeClass(post.status)">{{ post.status }}</span>
          <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200">
            <Icon
              :icon="visibilityIcon(post.visibility)"
              class="w-3.5 h-3.5"
            />
            <span class="uppercase">{{ post.visibility }}</span>
          </span>
          <span class="ml-auto inline-flex items-center gap-1 text-xs text-gray-5 00 dark:text-gray-400">
            <Icon
              icon="mdi:calendar-clock"
              class="w-4 h-4"
            />
            <span>{{ displayDate }}</span>
          </span>
        </div>

        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 break-words">
          {{ title }}
        </h3>

        <SafeHtml
          v-if="!empty"
          :html="html"
          :dir="dir"
          class="post-body text-[15px] leading-relaxed text-gray-900 dark:text-gray-100"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { onMounted, ref, computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePostStore, type Post } from '@/stores/post/post'
import PostMediaViewer from '@/modules/dashboard/pages/posts/components/postList/PostMediaViewer.vue'
import { sanitizeForDisplay, directionFor, htmlToText, isEmptyHtml } from '@/utils/text-utils'
import SafeHtml from '@/components/SafeHtml.vue'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const router = useRouter()
const store = usePostStore()
const { locale } = useI18n()

type ViewerItem = {
  id: number
  url: string
  mime?: string
  width?: number
  height?: number
  title?: string
  kind?: 'image'|'video'|'audio'|'document'
  poster?: string
  src?: string
  type?: 'image'|'video'|'audio'|'document'
  w?: number
  h?: number
}

const id = Number(route.params.id)
const loading = ref(false)
const post = ref<Post | null>(null)

const raw = computed(() => post.value?.content || '')
const html = computed(() => sanitizeForDisplay(raw.value))
const dir = computed(() => directionFor(raw.value))
const empty = computed(() => isEmptyHtml(raw.value))
const title = computed(() => {
  const t = htmlToText(raw.value, { preserveLineBreaks: true })
  const first = t.split('\n').map(s => s.trim()).find(Boolean) || `Post #${id}`
  return first.length > 100 ? first.slice(0, 100) + '…' : first
})

const displayDate = computed(() => {
  const iso = post.value?.published_at || post.value?.created_at || ''
  if (!iso) return '—'
  const d = new Date(iso)
  if (isNaN(d.getTime())) return '—'
  return new Intl.DateTimeFormat(locale.value, { dateStyle: 'medium', timeStyle: 'short' }).format(d)
})

const viewerItems = computed<ViewerItem[]>(() => {
  const list = (post.value?.media || []) as any[]
  return list
    .filter(m => !!m?.url)
    .map((m, i) => {
      const mime: string = m.mime_type || ''
      const kind: ViewerItem['kind'] =
        mime.startsWith('video/') ? 'video' :
          mime.startsWith('image/') ? 'image' :
            mime.startsWith('audio/') ? 'audio' : 'document'

      const poster =
        m.poster ||
        m.poster_url ||
        m.preview_url ||
        m.thumbnail_url ||
        m.thumb ||
        m.processed?.poster_url ||
        m.meta?.poster_url ||
        undefined

      const width = typeof m.width === 'number' ? m.width : undefined
      const height = typeof m.height === 'number' ? m.height : undefined

      return {
        id: Number.isFinite(Number(m.id)) ? Number(m.id) : i,
        url: String(m.url),
        mime,
        width,
        height,
        title: m.title || '',
        kind,
        poster,
        src: String(m.url),
        type: kind,
        w: width,
        h: height,
      }
    })
})

const fallbackItems = computed(() => viewerItems.value)

async function load() {
  loading.value = true
  try {
    post.value = await store.fetchOne(id)
  } finally {
    loading.value = false
  }
}

function goEdit() { router.push(`/dashboard/posts/${id}/edit`) }

async function onDelete() {
  if (!post.value) return
  await store.remove(post.value.id)
  router.push('/dashboard/posts')
}

function visibilityIcon(v: Post['visibility']) {
  if (v === 'public') return 'mdi:earth'
  if (v === 'friends') return 'mdi:account-group-outline'
  return 'mdi:lock-outline'
}

function badgeClass(status: Post['status']) {
  if (status === 'published') return 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-emerald-600 text-white'
  if (status === 'draft') return 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-amber-500 text-white'
  return 'inline-flex items-center px-2 py-0.5 rounded-full text-[10px] bg-gray-500 text-white'
}

onMounted(load)
</script>

<style>
.post-body h1 { font-size: 1.5rem; line-height: 1.25; font-weight: 700; margin: 0.75rem 0; }
.post-body h2 { font-size: 1.25rem; line-height: 1.35; font-weight: 700; margin: 0.75rem 0; }
.post-body h3 { font-size: 1.125rem; line-height: 1.4; font-weight: 600; margin: 0.5rem 0; }
.post-body p { margin: 0.5rem 0; }
.post-body ul, .post-body ol { margin: 0.5rem 1.25rem; padding: 0 1rem; }
.post-body blockquote { border-inline-start: 4px solid rgba(156,163,175,0.6); padding: 0.5rem 1rem; margin: 0.75rem 0; color: rgb(75,85,99); }
.dark .post-body blockquote { border-inline-start-color: rgba(229,231,235,0.3); color: rgb(209,213,219); }
.post-body a { text-decoration: underline; }
.post-body img, .post-body video { max-width: 100%; height: auto; border-radius: 0.5rem; }
.post-body .ql-align-right { text-align: right; }
.post-body .ql-align-center { text-align: center; }
.post-body .ql-align-justify { text-align: justify; }
.post-body .ql-direction-rtl { direction: rtl; }
.post-body .ql-indent-1 { margin-inline-start: 2em; }
.post-body .ql-indent-2 { margin-inline-start: 4em; }
.post-body .ql-indent-3 { margin-inline-start: 6em; }
</style>
