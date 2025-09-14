<template>
  <div class="flex gap-3">
    <AvatarUser
      :src="item.author.avatarUrl"
      :name="item.author.name"
      :color="item.author.avatarColor"
      size="sm"
      rounded="full"
      ring
      zoomable
    />

    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <span :class="nameCls">{{ item.author.name }}</span>
            <span class="text-xs text-zinc-400">· {{ time }}</span>
          </div>
        </div>

        <div class="relative">
          <button
            ref="menuBtn"
            class="rounded-lg p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
            aria-haspopup="menu"
            :aria-expanded="menuOpen ? 'true' : 'false'"
            aria-label="More"
            @click="toggleMenu"
            @keydown.enter.prevent="toggleMenu"
            @keydown.space.prevent="toggleMenu"
            @keydown.escape.stop.prevent="closeMenu"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="currentColor"
            >
              <circle
                cx="12"
                cy="6"
                r="1.5"
              /><circle
                cx="12"
                cy="12"
                r="1.5"
              /><circle
                cx="12"
                cy="18"
                r="1.5"
              />
            </svg>
          </button>

          <transition name="fade-scale">
            <div
              v-if="menuOpen"
              ref="menuRef"
              class="absolute right-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-zinc-200 bg-white/95 shadow-xl backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95"
              role="menu"
              tabindex="-1"
              @keydown.escape.stop.prevent="closeMenu"
            >
              <button
                v-if="isMine"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                role="menuitem"
                @click="startEdit"
              >
                <svg
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M16.862 3.487a2.1 2.1 0 0 1 2.97 2.97L8.999 17.29 5 18l.71-3.999L16.862 3.487z"
                  />
                </svg>
                Edit
              </button>

              <button
                v-if="isMine"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20"
                role="menuitem"
                @click="emit('delete', item); closeMenu()"
              >
                <svg
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m3 0V5.5A2.5 2.5 0 0 1 10.5 3h3A2.5 2.5 0 0 1 16 5.5V7M4 7h16M10 11v6m4-6v6"
                  />
                </svg>
                Delete
              </button>

              <button
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                role="menuitem"
                @click="emit('report', item); closeMenu()"
              >
                <svg
                  viewBox="0 0 24 24"
                  class="h-4 w-4"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.6"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M12 9v4m0 4h.01M3.055 6.326c.13-.303.451-.49.79-.49h16.31c.34 0 .66.187.79.49.13.303.078.666-.13.92L13.27 16.73a1 1 0 0 1-.77.37 1 1 0 0 1-.77-.37L3.186 7.246c-.208-.254-.26-.617-.13-.92z"
                  />
                </svg>
                Report
              </button>
            </div>
          </transition>
        </div>
      </div>

      <div
        v-if="editing"
        class="mt-2"
      >
        <form
          class="flex gap-2"
          @submit.prevent="submitEdit"
        >
          <input
            v-model="text"
            type="text"
            class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-900"
          >
          <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          >
            Save
          </button>
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="cancelEdit"
          >
            Cancel
          </button>
        </form>
      </div>

      <div
        v-else
        class="mt-1 text-[15px] leading-relaxed text-zinc-800 dark:text-zinc-100"
      >
        <ClampText>
          <p class="whitespace-pre-wrap">
            {{ item.body }}
          </p>
        </ClampText>
      </div>

      <div class="mt-2 flex items-center justify-between text-xs text-zinc-500">
        <div class="flex items-center gap-3">
          <button
            class="rounded px-1.5 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="$emit('reply', item)"
          >
            Reply
          </button>
          <button
            class="rounded px-1.5 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800"
            :class="{'text-indigo-600 dark:text-indigo-400 font-medium': item.liked}"
            @click="$emit('like', item)"
          >
            Like
          </button>
        </div>

        <button
          v-if="likesCount>0"
          class="group inline-flex items-center gap-2 rounded-lg px-2 py-1 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          aria-label="Show who liked"
          title="Show who liked"
          @click="$emit('show-likers', item)"
        >
          <div class="-space-x-2 flex">
            <AvatarUser
              v-for="(av, idx) in preview"
              :key="idx"
              :src="av.avatar"
              :name="av.name"
              size="xs"
            />
            <div
              v-if="remaining>0"
              class="flex h-5 w-5 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-medium text-zinc-700 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-200 dark:ring-zinc-900"
            >
              +{{ remaining }}
            </div>
          </div>
          <span class="text-[11px] text-zinc-500 group-hover:text-zinc-700 dark:group-hover:text-zinc-300">{{ likesCount }} likes</span>
        </button>
      </div>

      <div
        v-if="replying"
        class="mt-3"
      >
        <slot name="composer" />
      </div>

      <div
        v-if="children && children.length"
        class="mt-3 space-y-3 rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <slot name="children" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Comment } from '@/modules/public/postCard/types/comment.types'
import { computed, ref, watch, onMounted, onBeforeUnmount, nextTick } from 'vue'
import ClampText from './ClampText.vue'
import { listLikers, type MiniUser } from '@/modules/public/postCard/api/comments'
import AvatarUser from '@/components/shared/AvatarUser.vue'

const props = defineProps<{
  item: Comment
  isMine: boolean
  replying?: boolean
  children?: Comment[]
  isChild?: boolean
  postId?: string
}>()
const emit = defineEmits<{ (e: 'reply', c: Comment): void; (e: 'like', c: Comment): void; (e: 'delete', c: Comment): void; (e: 'report', c: Comment): void; (e: 'edit', payload: { id: string; text: string }): void; (e:'show-likers', c: Comment): void }>()

const menuOpen = ref(false)
const menuRef = ref<HTMLElement | null>(null)
const menuBtn = ref<HTMLElement | null>(null)
function toggleMenu() { menuOpen.value = !menuOpen.value; if (menuOpen.value) nextTick(() => menuRef.value?.focus()) }
function closeMenu() { menuOpen.value = false }
function onDocClick(e: MouseEvent) {
  const t = e.target as Node
  if (!menuOpen.value) return
  if (menuRef.value && menuRef.value.contains(t)) return
  if (menuBtn.value && menuBtn.value.contains(t)) return
  closeMenu()
}
onMounted(() => document.addEventListener('click', onDocClick, { capture: true }))
onBeforeUnmount(() => document.removeEventListener('click', onDocClick, { capture: true }))

const editing = ref(false)
const text = ref(props.item.body)
watch(() => props.item.body, v => { if (!editing.value) text.value = v })

function startEdit() { editing.value = true; closeMenu() }
function cancelEdit() { editing.value = false; text.value = props.item.body }
function submitEdit() { emit('edit', { id: props.item.id, text: text.value }); editing.value = false }

const time = computed(() => {
  const diff = Date.now() - new Date(props.item.createdAt).getTime()
  const s = Math.floor(diff / 1000)
  if (s < 60) return `${s}s`
  const m = Math.floor(s / 60)
  if (m < 60) return `${m}m`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h`
  const d = Math.floor(h / 24)
  return `${d}d`
})

const nameCls = computed(() => props.isChild ? 'text-[13px] font-semibold' : 'text-sm font-semibold')

const likesCount = computed(() => Number(props.item.likes || 0))
const previewCache = (window as any).__comment_likers_cache__ || ((window as any).__comment_likers_cache__ = new Map<string, MiniUser[]>())
const preview = ref<MiniUser[]>([])

async function fetchPreview() {
  if (!props.postId) return
  if (likesCount.value <= 0) { preview.value = []; return }
  const key = `${props.postId}:${props.item.id}`
  if (previewCache.has(key)) {
    preview.value = previewCache.get(key) || []
    return
  }
  try {
    const { users } = await listLikers(props.postId, props.item.id, { per_page: 3 })
    preview.value = users
    previewCache.set(key, users)
  } catch { /* empty */ }
}

onMounted(fetchPreview)
watch([likesCount, () => props.postId], () => { void fetchPreview() }, { immediate: false })

const remaining = computed(() => Math.max(0, likesCount.value - preview.value.length))
</script>

<style scoped>
.fade-scale-enter-active,.fade-scale-leave-active{transition:opacity .12s ease, transform .12s ease}
.fade-scale-enter-from,.fade-scale-leave-to{opacity:0; transform:scale(.98)}
</style>
