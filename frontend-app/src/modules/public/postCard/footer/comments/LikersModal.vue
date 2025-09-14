<template>
  <transition name="fade">
    <div
      v-if="open"
      class="fixed inset-0 z-50 flex items-end sm:items-center justify-center"
    >
      <div
        class="absolute inset-0 bg-black/40"
        @click="$emit('close')"
      />
      <div class="relative z-10 w-full sm:max-w-md rounded-2xl bg-white p-4 shadow-xl dark:bg-zinc-900">
        <header class="mb-3 flex items-center justify-between">
          <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            Liked by
          </h3>
          <button
            class="rounded-lg px-2 py-1 text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800"
            @click="$emit('close')"
          >
            Close
          </button>
        </header>

        <div
          ref="scroller"
          class="max-h-[60vh] overflow-auto"
          @scroll.passive="onScroll"
        >
          <ul
            v-if="users.length"
            class="divide-y divide-zinc-100 dark:divide-zinc-800"
          >
            <li
              v-for="u in users"
              :key="u.id"
              class="flex items-center justify-between gap-3 py-2"
            >
              <div class="flex items-center gap-3 min-w-0 p-2">
                <AvatarUser
                  :src="u.avatar || undefined"
                  :color="u.avatarColor || undefined"
                  :name="u.name"
                  size="md"
                  rounded="full"
                  ring
                  zoomable
                />
                <div class="min-w-0">
                  <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                    {{ u.name }}
                  </div>
                  <div
                    v-if="u.slug"
                    class="truncate text-xs text-zinc-500"
                  >
                    @{{ u.slug }}
                  </div>
                </div>
              </div>

              <button
                v-if="String(u.id)!==meId"
                class="rounded-full px-3 py-1 text-xs font-medium text-white hover:opacity-95 dark:text-white"
                :class="following[String(u.id)] ? 'bg-zinc-800 dark:bg-white/10' : 'bg-indigo-600'"
                @click="toggleFollow(u.id)"
              >
                {{ following[String(u.id)] ? 'Unfollow' : 'Follow' }}
              </button>
            </li>
          </ul>

          <div
            v-if="loading"
            class="py-3 text-center text-xs text-zinc-500"
          >
            Loading…
          </div>
          <div
            v-if="!loading && users.length===0"
            class="py-6 text-center text-sm text-zinc-500"
          >
            No likes yet
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue'
import type { MiniUser } from '@/modules/public/postCard/api/comments'
import { listLikers } from '@/modules/public/postCard/api/comments'
import AvatarUser from '@/components/shared/AvatarUser.vue'

const props = defineProps<{ open: boolean; postId: string; commentId: string; meId?: string }>()
defineEmits<{ (e: 'close'): void }>()

const users = ref<MiniUser[]>([])
const nextCursor = ref<string | null>(null)
const loading = ref(false)
const scroller = ref<HTMLElement | null>(null)
const following = ref<Record<string, boolean>>({})
const meId = computed(() => String(props.meId || ''))

let token = 0
const key = computed(() => `${props.postId}:${props.commentId}`)

async function load(reset = false) {
  if (loading.value) return
  loading.value = true
  const myToken = ++token
  try {
    const { users: rows, meta } = await listLikers(props.postId, props.commentId, {
      cursor: reset ? undefined : nextCursor.value || undefined,
      per_page: 20,
    })
    if (myToken !== token) return
    if (reset) users.value = rows
    else users.value.push(...rows)
    nextCursor.value = meta?.next_cursor ?? null
  } finally {
    if (myToken === token) loading.value = false
  }
}

function onScroll() {
  const el = scroller.value
  if (!el) return
  const near = el.scrollTop + el.clientHeight >= el.scrollHeight - 48
  if (near && nextCursor.value && !loading.value) void load(false)
}

function toggleFollow(id: string | number) {
  const key = String(id)
  following.value[key] = !following.value[key]
}

watch(
  () => [props.open, key.value] as const,
  ([isOpen]) => {
    if (!isOpen) return
    users.value = []
    nextCursor.value = null
    void load(true)
  },
  { immediate: true }
)
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
