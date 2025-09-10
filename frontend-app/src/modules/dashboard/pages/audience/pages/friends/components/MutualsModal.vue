<!-- src/modules/dashboard/pages/audience/components/MutualsModal.vue -->
<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center"
  >
    <div
      class="absolute inset-0 bg-black/40"
      @click="$emit('close')"
    />
    <div class="relative z-10 w-full max-w-lg rounded-2xl bg-white dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-800 p-4">
      <div class="flex items-center justify-between mb-3">
        <h3 class="font-semibold text-sm">
          Mutual friends
        </h3>
        <button
          class="text-sm px-2 py-1 rounded border dark:border-zinc-700"
          @click="$emit('close')"
        >
          Close
        </button>
      </div>

      <div
        v-if="error"
        class="text-sm text-red-600 mb-2"
      >
        {{ error }}
      </div>

      <div class="max-h-[70vh] overflow-y-auto">
        <div
          v-if="loadingInitial"
          class="space-y-2"
        >
          <div class="h-8 rounded bg-zinc-100 dark:bg-zinc-800 animate-pulse" />
          <div class="h-8 rounded bg-zinc-100 dark:bg-zinc-800 animate-pulse" />
          <div class="h-8 rounded bg-zinc-100 dark:bg-zinc-800 animate-pulse" />
        </div>

        <div
          v-else-if="items.length===0"
          class="text-sm text-zinc-500"
        >
          No mutual friends
        </div>

        <div
          v-else
          class="space-y-3"
        >
          <div
            v-for="u in items"
            :key="u.id"
            class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-800 p-2"
          >
            <AvatarUser
              :src="u.avatar"
              :name="u.name"
              :color="u.avatar_color"
              size="sm"
              zoomable
            />
            <div class="min-w-0">
              <div class="text-sm font-medium truncate">
                {{ u.name || '—' }}
              </div>
              <div class="text-xs text-zinc-500 truncate">
                @{{ u.slug || '—' }}
              </div>
            </div>
          </div>
        </div>

        <div
          ref="sentinel"
          class="h-6"
        />

        <div
          v-if="loadingMore"
          class="mt-3 text-xs text-zinc-500"
        >
          Loading…
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AvatarUser from "@/components/shared/AvatarUser.vue"
import { ref, watch, onMounted, onBeforeUnmount } from "vue"
import type { ProfileLite, Paginated } from "../../../types"
import { listMutuals } from "../../../api/follow"

const props = defineProps<{ userId: number; open: boolean }>()
defineEmits<{ (e:'close'):void }>()

const items = ref<ProfileLite[]>([])
const page = ref(1)
const perPage = ref(24)
const lastPage = ref(1)
const loadingInitial = ref(false)
const loadingMore = ref(false)
const error = ref<string | null>(null)

async function fetchPage(p = 1) {
  const isInitial = p === 1
  if (isInitial) {
    loadingInitial.value = true
    items.value = []
  } else {
    loadingMore.value = true
  }
  error.value = null
  try {
    const res: Paginated<ProfileLite> = await listMutuals(props.userId, { page: p, per_page: perPage.value })
    if (isInitial) {
      items.value = res.data ?? []
    } else {
      items.value = [...items.value, ...(res.data ?? [])]
    }
    page.value = res.meta?.current_page ?? p
    lastPage.value = res.meta?.last_page ?? 1
  } catch (e: any) {
    error.value = e?.message || "Failed to load mutuals"
  } finally {
    loadingInitial.value = false
    loadingMore.value = false
  }
}

const sentinel = ref<HTMLElement | null>(null)
let observer: IntersectionObserver | null = null

function ensureObserver() {
  if (observer) return
  observer = new IntersectionObserver((entries) => {
    const entry = entries[0]
    if (!entry?.isIntersecting) return
    if (loadingMore.value || loadingInitial.value) return
    if (page.value >= lastPage.value) return
    fetchPage(page.value + 1)
  }, { root: null, rootMargin: "0px", threshold: 0.1 })
}

watch(() => props.open, (v) => {
  if (v) {
    page.value = 1
    lastPage.value = 1
    items.value = []
    fetchPage(1)
    ensureObserver()
    setTimeout(() => {
      if (observer && sentinel.value) observer.observe(sentinel.value)
    }, 0)
  } else {
    if (observer && sentinel.value) observer.unobserve(sentinel.value)
  }
}, { immediate: true })

onMounted(() => {
  ensureObserver()
  if (observer && sentinel.value) observer.observe(sentinel.value)
})
onBeforeUnmount(() => {
  if (observer) observer.disconnect()
  observer = null
})
</script>
