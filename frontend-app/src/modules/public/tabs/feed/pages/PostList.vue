<template>
  <div
    ref="rootEl"
    class="space-y-4"
  >
    <PublicComposerBar
      class="mb-4"
      @open="isComposerOpen = true"
    />

    <PublicComposerModal
      :open="isComposerOpen"
      @close="isComposerOpen = false"
      @created="onPostCreated"
    />

    <PostCard
      v-for="p in items"
      :key="p.id"
      :post="p"
    />

    <div
      v-if="loading"
      class="space-y-3 pt-2"
    >
      <div
        v-for="i in 3"
        :key="'sk-'+i"
        class="rounded-2xl border border-zinc-200/80 bg-white/70 p-4 dark:border-white/10 dark:bg-zinc-900/50 animate-pulse"
      >
        <div class="mb-2 h-4 w-1/3 rounded bg-zinc-200 dark:bg-zinc-700" />
        <div class="h-3 w-2/3 rounded bg-zinc-200 dark:bg-zinc-700" />
      </div>
    </div>

    <div
      v-show="hasMore"
      ref="sentinel"
      class="h-8"
    />
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import PostCard from '@/modules/public/postCard/PostCard.vue'
import { usePublicFeed } from '@/modules/public/postCard/composables/usePublicFeed'
import { useFeedRealtime } from '@/modules/public/postCard/composables/useFeedRealtime'
import { usePostActions } from '@/modules/public/postCard/composables/usePostActions'
import PublicComposerBar from "@/modules/public/postCard/createPost/PublicComposerBar.vue";
import PublicComposerModal from "@/modules/public/postCard/createPost/PublicPostComposer.vue";
import * as postsApi from '@/modules/public/postCard/api/posts'

const { items, loadMore, hasMore, loading } = usePublicFeed()
const { ensure, setCounts } = usePostActions()

async function initAfterLoad() {
  if (!items.value.length) await loadMore()
  for (const p of items.value) ensure(p)
}

const { start, stop } = useFeedRealtime(items, {
  channelName: 'public.posts',
  prependNew: true,
  accept: (p) => p.visibility === 'public',
  onUpsert: (p) => ensure(p),
  onCounts: (id, counts) => setCounts(id, counts),
})

const rootEl = ref<HTMLElement | null>(null)
const sentinel = ref<HTMLElement | null>(null)
let io: IntersectionObserver | null = null

function getScrollParent(el: HTMLElement | null): HTMLElement | null {
  let p: HTMLElement | null = el?.parentElement || null
  while (p) {
    const oy = getComputedStyle(p).overflowY
    if (/(auto|scroll|overlay)/i.test(oy)) return p
    p = p.parentElement
  }
  return null
}

async function onIntersect(entries: IntersectionObserverEntry[]) {
  const e = entries[0]
  if (!e.isIntersecting) return
  if (!hasMore.value || loading.value) return
  await loadMore()
}

function setupIO() {
  if (io) io.disconnect()
  const root = getScrollParent(rootEl.value)
  io = new IntersectionObserver(onIntersect, {
    root: root || null,
    rootMargin: '600px 0px 0px 0px',
    threshold: 0
  })
  if (sentinel.value) io.observe(sentinel.value)
}

const isComposerOpen = ref(false)

async function onPostCreated(p: any) {
  const full = p?.author ? p : await postsApi.get(String(p.id))
  ensure(full)
  items.value.unshift(full)
}

onMounted(async () => {
  await initAfterLoad()
  setupIO()
  await start()
})

onBeforeUnmount(() => {
  if (io) io.disconnect()
  void stop()
})
</script>
