<template>
  <div
    ref="rootEl"
    class="space-y-4"
  >
    <div class="flex items-center justify-between">
      <div class="relative">
        <button
          class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
          @click="open = !open"
        >
          {{ label }}
          <svg
            viewBox="0 0 24 24"
            class="h-4 w-4"
          ><path
            fill="currentColor"
            d="M7 10l5 5 5-5z"
          /></svg>
        </button>
        <div
          v-if="open"
          class="absolute z-10 mt-1 w-40 rounded-lg border border-zinc-200 bg-white p-1 shadow dark:border-zinc-800 dark:bg-zinc-900"
        >
          <button
            class="w-full rounded px-2 py-1 text-left text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="setSort('relevant')"
          >
            Most relevant
          </button>
          <button
            class="w-full rounded px-2 py-1 text-left text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="setSort('newest')"
          >
            Newest
          </button>
          <button
            class="w-full rounded px-2 py-1 text-left text-xs hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="setSort('top')"
          >
            Top
          </button>
        </div>
      </div>
    </div>

    <div
      v-if="pageItems.length"
      class="divide-y divide-zinc-100 dark:divide-zinc-800"
    >
      <div
        v-for="root in pageItems"
        :key="root.id"
        class="py-3 first:pt-0 last:pb-0"
      >
        <FlatThread
          :post-id="postId"
          :root="root"
          :sort-by="sortBy"
          :logged-in="loggedInBool"
          :avatar-url="avatarUrl"
          :avatar-color="avatarColor"
          @added="emit('added')"
        />
      </div>
    </div>

    <div
      v-else
      class="py-10 text-center text-sm text-zinc-500"
    >
      No comments yet
    </div>

    <div
      v-if="isLoading"
      class="space-y-2 pt-2"
    >
      <div
        v-for="i in 2"
        :key="'sk-'+i"
        class="rounded-2xl border border-zinc-200/80 bg-white/70 p-3 dark:border-white/10 dark:bg-zinc-900/50 animate-pulse"
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
import { computed, inject, onBeforeUnmount, onMounted, ref, watch } from "vue"
import FlatThread from "./FlatThread.vue"
import type { Comment } from "@/modules/public/postCard/types/comment.types"

const { postId, pageSize = 20, loggedIn = false, avatarUrl = null, avatarColor = null } = defineProps<{
  postId: number | string
  pageSize?: number
  loggedIn?: boolean
  avatarUrl?: string | null
  avatarColor?: string | null
}>()
const emit = defineEmits<{ (e: "added"): void }>()

const ctx = inject<any>("commentCtx")
const sortBy = ref<"relevant" | "newest" | "top">("relevant")
const open = ref(false)

const pageItems = computed<Comment[]>(() => ctx?.roots?.value || [])
const hasMore = computed<boolean>(() => Boolean(ctx?.hasMoreRoots?.value))
const isLoading = computed<boolean>(() => Boolean(ctx?.loading?.value))
const apiSort = computed<"new" | "top">(() => (sortBy.value === "newest" ? "new" : "top"))
const label = computed(() => (sortBy.value === "relevant" ? "Most relevant" : sortBy.value === "newest" ? "Newest" : "Top"))
const loggedInBool = computed<boolean>(() => Boolean(loggedIn))

async function fetchFirst() {
  open.value = false
  await ctx.fetchRoots({ perPage: pageSize || 20, sort: apiSort.value })
}
function setSort(s: "relevant" | "newest" | "top") {
  if (sortBy.value === s) { open.value = false; return }
  sortBy.value = s
}

watch(sortBy, () => { fetchFirst() })

const rootEl = ref<HTMLElement | null>(null)
const sentinel = ref<HTMLElement | null>(null)
let io: IntersectionObserver | null = null

function getScrollParent(el: HTMLElement | null): HTMLElement | null {
  let p: HTMLElement | null = el?.parentElement || null
  while (p) {
    const style = getComputedStyle(p)
    const oy = style.overflowY
    if (/(auto|scroll|overlay)/i.test(oy)) return p
    p = p.parentElement
  }
  return null
}

async function onIntersect(entries: IntersectionObserverEntry[]) {
  const e = entries[0]
  if (!e.isIntersecting) return
  if (!hasMore.value || isLoading.value) return
  await ctx.fetchMoreRoots()
}

function setupIO() {
  if (io) io.disconnect()
  const root = getScrollParent(rootEl.value)
  io = new IntersectionObserver(onIntersect, { root: root || null, rootMargin: "400px 0px 0px 0px", threshold: 0 })
  if (sentinel.value) io.observe(sentinel.value)
}

onMounted(async () => {
  await fetchFirst()
  setupIO()
})
onBeforeUnmount(() => { if (io) io.disconnect() })
</script>
