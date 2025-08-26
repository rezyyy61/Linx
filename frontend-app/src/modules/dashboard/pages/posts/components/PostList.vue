<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:post-outline"
          class="w-6 h-6 text-gray-700 dark:text-gray-200"
        />
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          My Posts
        </h2>
      </div>
      <slot name="actions" />
    </div>

    <div
      v-if="authLoading"
      class="rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 p-6"
    >
      <div class="flex items-center gap-3">
        <Icon
          icon="mdi:loading"
          class="w-5 h-5 animate-spin text-emerald-600"
        />
        <div class="text-sm text-gray-700 dark:text-gray-300">
          Loading your account…
        </div>
      </div>
    </div>

    <EmptyState
      v-else-if="!userId"
      icon="mdi:lock-outline"
      title="Sign in to see your posts"
      subtitle="Dashboard shows only your own posts"
    />

    <div v-else>
      <div
        v-if="loading && posts.length === 0"
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="rounded-2xl overflow-hidden border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700"
        >
          <div class="h-36 bg-gray-100 animate-pulse dark:bg-gray-800" />
          <div class="p-4 space-y-3">
            <div class="h-4 w-3/4 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
            <div class="h-3 w-full bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
            <div class="h-3 w-5/6 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
            <div class="flex items-center gap-2 pt-2">
              <div class="h-6 w-16 bg-gray-100 rounded-full animate-pulse dark:bg-gray-800" />
              <div class="h-6 w-16 bg-gray-100 rounded-full animate-pulse dark:bg-gray-800" />
            </div>
          </div>
        </div>
      </div>

      <EmptyState
        v-else-if="posts.length === 0"
        icon="mdi:inbox-outline"
        title="No posts yet"
        subtitle="Create your first post"
      >
        <template #action>
          <button
            type="button"
            class="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
            @click="$emit('create')"
          >
            <Icon
              icon="mdi:plus"
              class="w-5 h-5"
            />
            <span>Create Post</span>
          </button>
        </template>
      </EmptyState>

      <ul
        v-else
        class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 auto-rows-fr gap-4"
      >
        <li
          v-for="p in posts"
          :key="p.id"
          class="h-full"
        >
          <PostCard
            class="h-full"
            :post="p"
            :mine="true"
            @open="goOpen(p.id)"
            @edit="goEdit(p.id)"
            @delete="askDelete(p)"
          />
        </li>
      </ul>

      <div
        v-if="hasMore && posts.length"
        class="flex items-center justify-center"
      >
        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-gray-300 text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:opacity-50 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-800"
          :disabled="loadingMore"
          @click="loadMore"
        >
          <Icon
            v-if="loadingMore"
            icon="mdi:loading"
            class="w-5 h-5 animate-spin"
          />
          <Icon
            v-else
            icon="mdi:chevron-down"
            class="w-5 h-5"
          />
          <span>{{ loadingMore ? 'Loading...' : 'Load more' }}</span>
        </button>
      </div>

      <div
        ref="sentinel"
        class="h-4"
      />
    </div>

    <transition name="fade">
      <div
        v-if="confirmOpen"
        class="fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4"
        @click.self="confirmOpen=false"
      >
        <div class="w-full max-w-md rounded-2xl bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 shadow-xl overflow-hidden">
          <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <Icon
              icon="mdi:trash-can-outline"
              class="w-5 h-5 text-red-600"
            />
            <div class="font-semibold text-gray-900 dark:text-gray-100">
              Delete post
            </div>
          </div>
          <div class="p-4 text-sm text-gray-700 dark:text-gray-300">
            Are you sure you want to delete this post?
          </div>
          <div class="p-4 flex items-center justify-end gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-gray-800 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-800"
              :disabled="deleting"
              @click="confirmOpen=false"
            >
              <Icon
                icon="mdi:close"
                class="w-4 h-4"
              />
              <span>Cancel</span>
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500/30 disabled:opacity-50"
              :disabled="deleting"
              @click="confirmDelete"
            >
              <Icon
                v-if="deleting"
                icon="mdi:loading"
                class="w-4 h-4 animate-spin"
              />
              <Icon
                v-else
                icon="mdi:trash-can-outline"
                class="w-4 h-4"
              />
              <span>{{ deleting ? 'Deleting...' : 'Delete' }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePostStore } from '@/stores/post/post'
import { useAuthStore } from '@/stores/auth/auth'
import EmptyState from '@/modules/dashboard/pages/posts/components/EmptyState.vue'
import PostCard from "@/modules/dashboard/pages/posts/components/PostCard.vue";

const router = useRouter()
const auth = useAuthStore()
const store = usePostStore()

defineEmits<{ create: [] }>()


const userId = computed(() => auth.user?.id ?? null)
const authLoading = computed(() => auth.loading && !auth.bootstrapDone)

const posts = computed(() => store.list)
const hasMore = computed(() => store.hasMore)
const loading = computed(() => store.loadingList)
const loadingMore = ref(false)

const sentinel = ref<HTMLElement | null>(null)
let io: IntersectionObserver | null = null

const confirmOpen = ref(false)
const deleting = ref(false)
const targetId = ref<number | null>(null)

async function fetchFirst() {
  if (!userId.value) return
  await store.fetchList({ userId: userId.value, limit: 20, replace: true })
}
async function loadMore() {
  if (!store.nextCursor || !userId.value) return
  loadingMore.value = true
  try { await store.fetchList({ userId: userId.value, cursor: store.nextCursor, limit: 20, replace: false }) }
  finally { loadingMore.value = false }
}
function setupIO() {
  if (!sentinel.value) return
  io = new IntersectionObserver((entries) => {
    for (const e of entries) if (e.isIntersecting && hasMore.value && !loading.value && !loadingMore.value) loadMore()
  }, { rootMargin: '400px 0px' })
  io.observe(sentinel.value)
}
function destroyIO() { if (io && sentinel.value) io.unobserve(sentinel.value); io = null }
function askDelete(p: any) { targetId.value = p.id; confirmOpen.value = true }
async function confirmDelete() { if (!targetId.value) return; deleting.value = true; try { await store.remove(targetId.value) } finally { deleting.value = false; confirmOpen.value = false; targetId.value = null } }
function goOpen(id: number) { router.push(`/dashboard/posts/${id}`) }
function goEdit(id: number) { router.push(`/dashboard/posts/${id}/edit`) }

onMounted(async () => { await fetchFirst(); setupIO() })
watch(() => auth.user?.id, async () => { await fetchFirst() }, { immediate: false })
onBeforeUnmount(() => { destroyIO() })
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
