<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth/auth'
import EmptyState from '@/modules/dashboard/pages/posts/components/postList/EmptyState.vue'
import PostCard from '@/modules/dashboard/pages/posts/components/postList/PostCard.vue'
import PostHeader from '@/modules/dashboard/pages/posts/components/header/PostHeader.vue'
import { usePostStore } from '@/stores/post/post'

const { t, te } = useI18n()
const tr = (k: string) => (te(`post.${k}`) ? t(`post.${k}`) : t(k))
defineEmits<{ create: [] }>()

const router = useRouter()
const auth = useAuthStore()
const store = usePostStore()

const userId = computed(() => auth.user?.id ?? null)
const authLoading = computed(() => auth.loading && !auth.bootstrapDone)

const posts = computed(() => store.list)
const hasMore = computed(() => store.hasMore)
const loading = computed(() => store.loadingList)
const loadingMore = ref(false)

const filters = ref<Record<string, any>>({})
const sentinel = ref<HTMLElement | null>(null)
let io: IntersectionObserver | null = null

const confirmOpen = ref(false)
const deleting = ref(false)
const targetId = ref<number | null>(null)

async function fetchFirst() {
  if (!userId.value) return
  await store.fetchList({ userId: userId.value, limit: 20, replace: true, ...filters.value })
}
async function loadMore() {
  if (!store.nextCursor || !userId.value) return
  loadingMore.value = true
  try {
    await store.fetchList({
      userId: userId.value,
      cursor: store.nextCursor,
      limit: 20,
      replace: false,
      ...filters.value,
    })
  } finally { loadingMore.value = false }
}

function setupIO() {
  if (!sentinel.value) return
  io = new IntersectionObserver((entries) => {
    for (const e of entries)
      if (e.isIntersecting && hasMore.value && !loading.value && !loadingMore.value) loadMore()
  }, { rootMargin: '400px 0px' })
  io.observe(sentinel.value)
}
function destroyIO() {
  if (io && sentinel.value) io.unobserve(sentinel.value)
  io = null
}

function askDelete(p: any) { targetId.value = p.id; confirmOpen.value = true }
async function confirmDelete() {
  if (!targetId.value) return
  deleting.value = true
  try { await store.remove(targetId.value) }
  finally { deleting.value = false; confirmOpen.value = false; targetId.value = null }
}
function goOpen(id: number) { router.push(`/dashboard/posts/${id}`) }
function goEdit(id: number) { router.push(`/dashboard/posts/${id}/edit`) }

function onApply(p: Record<string, any>) {
  console.log('apply filters =>', p)
  filters.value = p || {}
  fetchFirst()
}

onMounted(async () => { await fetchFirst(); setupIO() })
watch(() => auth.user?.id, fetchFirst)
onBeforeUnmount(() => { destroyIO() })
</script>

<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:post-outline"
          class="w-6 h-6 text-gray-700 dark:text-gray-200"
        />
        <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
          {{ tr('postlist.title') }}
        </h2>
      </div>
      <slot name="actions" />
    </div>

    <PostHeader
      class="sticky top-2 z-10"
      @apply="onApply"
    />

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
          {{ tr('postlist.loading.auth') }}
        </div>
      </div>
    </div>

    <EmptyState
      v-else-if="!userId"
      icon="mdi:lock-outline"
      :title="tr('postlist.empty.auth.title')"
      :subtitle="tr('postlist.empty.auth.subtitle')"
    />

    <div v-else>
      <div
        v-if="loading && posts.length === 0"
        class="grid items-stretch grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 auto-rows-[1fr]"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700"
        >
          <div class="aspect-[4/3] w-full bg-gray-100 animate-pulse dark:bg-gray-800" />
          <div class="flex flex-1 flex-col p-4">
            <div class="space-y-2">
              <div class="h-4 w-3/4 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
              <div class="h-4 w-full bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
              <div class="h-4 w-5/6 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
            </div>
            <div class="mt-auto flex items-center justify-between pt-4">
              <div class="h-4 w-24 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
              <div class="h-4 w-16 bg-gray-100 rounded animate-pulse dark:bg-gray-800" />
            </div>
          </div>
        </div>
      </div>

      <EmptyState
        v-else-if="posts.length === 0"
        icon="mdi:inbox-outline"
        :title="tr('postlist.empty.none.title')"
        :subtitle="tr('postlist.empty.none.subtitle')"
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
            <span>{{ tr('postlist.actions.create') }}</span>
          </button>
        </template>
      </EmptyState>

      <ul
        v-else
        class="grid items-stretch grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3 auto-rows-[1fr]"
      >
        <li
          v-for="p in posts"
          :key="p.id"
          class="h-full"
        >
          <PostCard
            class="h-full"
            :post="p"
            :clickable="false"
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
          <span>{{ loadingMore ? tr('postlist.actions.loading') : tr('postlist.actions.load_more') }}</span>
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
              {{ tr('postlist.confirm.title') }}
            </div>
          </div>
          <div class="p-4 text-sm text-gray-700 dark:text-gray-300">
            {{ tr('postlist.confirm.message') }}
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
              <span>{{ tr('postlist.confirm.cancel') }}</span>
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
              <span>{{ deleting ? tr('postlist.confirm.deleting') : tr('postlist.confirm.delete') }}</span>
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
