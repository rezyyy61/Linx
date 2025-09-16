<template>
  <div
    :id="`c-${item.id}`"
    class="flex gap-3"
  >
    <AvatarUser
      :src="item.user?.avatar || undefined"
      :name="item.user?.name || ''"
      :color="item.user?.avatarColor || undefined"
      size="sm"
      rounded="full"
      ring
      zoomable
    />

    <div class="min-w-0 flex-1">
      <div class="relative inline-block">
        <div
          class="z-10 inline-block max-w-full rounded-2xl bg-zinc-100 px-3 py-3 dark:bg-zinc-700"
          :class="isChild ? 'rounded-tl-md' : ''"
        >
          <div class="flex items-center gap-2">
            <span class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ item.user?.name || 'User' }}</span>
            <span class="text-xs text-zinc-500">{{ timeAgo }}</span>
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
                class="flex-1 rounded-full border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-900"
              >
              <button
                type="submit"
                class="rounded-full bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
              >
                Save
              </button>
              <button
                type="button"
                class="rounded-full px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                @click="cancelEdit"
              >
                Cancel
              </button>
            </form>
          </div>

          <div
            v-else
            class="mt-1 mb-1 px-4 text-[15px] leading-relaxed text-zinc-800 dark:text-zinc-100"
          >
            <ClampText :lines="6">
              <p class="whitespace-pre-wrap break-words">
                <template
                  v-for="(seg, i) in tokens"
                  :key="i"
                >
                  <a
                    v-if="seg.t === 'm'"
                    :href="`/u/${seg.s}`"
                    class="text-indigo-600 dark:text-indigo-400 font-medium hover:underline"
                  >
                    @{{ seg.s }}
                  </a>
                  <span v-else>{{ seg.s }}</span>
                </template>
              </p>
            </ClampText>
          </div>

          <button
            v-if="likesCount>0"
            class="absolute -bottom-3 right-2 inline-flex items-center gap-1 rounded-full bg-zinc-200 px-1.5 py-0.5 text-[11px] font-medium text-zinc-700 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-200 dark:ring-zinc-900"
            @click="$emit('show-likers', item)"
          >
            👍 <span>{{ likesCount }}</span>
          </button>
        </div>

        <div class="absolute top-1.5 right-0 z-20 translate-x-full pl-2">
          <button
            ref="menuBtn"
            class="rounded-lg p-1 text-zinc-500 hover:bg-zinc-200 hover:text-zinc-700 dark:hover:bg-zinc-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
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
              />
              <circle
                cx="12"
                cy="12"
                r="1.5"
              />
              <circle
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
              class="absolute right-0 mt-1 w-40 overflow-hidden rounded-xl border border-zinc-200 bg-white/95 shadow-xl backdrop-blur dark:border-zinc-800 dark:bg-zinc-900/95"
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
                Edit
              </button>
              <button
                v-if="isMine"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50 dark:text-rose-400 dark:hover:bg-rose-900/20"
                role="menuitem"
                @click="emit('delete', item); closeMenu()"
              >
                Delete
              </button>
              <button
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
                role="menuitem"
                @click="emit('report', item); closeMenu()"
              >
                Report
              </button>
            </div>
          </transition>
        </div>
      </div>

      <div class="mt-1 flex items-center gap-3 pl-1 text-xs text-zinc-600 dark:text-zinc-300">
        <button
          class="rounded px-1 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          :class="liked ? 'text-indigo-600 dark:text-indigo-400 font-medium' : ''"
          @click="$emit('like', item)"
        >
          Like
        </button>
        <button
          class="rounded px-1 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          @click="$emit('reply', item)"
        >
          Reply
        </button>
      </div>

      <div
        v-if="replying"
        class="mt-3 ml-10"
      >
        <slot name="composer" />
      </div>

      <div
        v-if="$slots.children"
        class="mt-3"
      >
        <slot name="children" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue"
import type { Comment } from "@/modules/public/postCard/types/comment.types"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import ClampText from "@/modules/public/postCard/footer/comments/ClampText.vue"

const props = defineProps<{
  item: Comment
  isMine: boolean
  replying: boolean
  isChild: boolean
  postId: number | string
}>()
const emit = defineEmits<{
  (e: "reply", item: Comment): void
  (e: "like", item: Comment): void
  (e: "delete", item: Comment): void
  (e: "report", item: Comment): void
  (e: "edit", payload: { id: number; body: string }): void
  (e: "show-likers", item: Comment): void
}>()

const mentionRegex = /@([A-Za-z0-9_.-]{2,64})/g
type Seg = { t: "t" | "m"; s: string }

const item = computed(() => props.item)
const likesCount = computed(() => item.value.reactionsCount || 0)
const liked = computed(() => Boolean(item.value.likedByMe))
const menuOpen = ref(false)
const editing = ref(false)
const text = ref(item.value.body)

const tokens = computed<Seg[]>(() => {
  const input = item.value.body || ""
  const out: Seg[] = []
  let last = 0
  for (const m of input.matchAll(mentionRegex)) {
    const start = m.index ?? 0
    const end = start + m[0].length
    if (start > last) out.push({ t: "t", s: input.slice(last, start) })
    out.push({ t: "m", s: m[1] })
    last = end
  }
  if (last < input.length) out.push({ t: "t", s: input.slice(last) })
  return out.length ? out : [{ t: "t", s: "" }]
})

const timeAgo = computed(() => {
  if (!item.value.createdAt) return ""
  const d = new Date(item.value.createdAt).getTime()
  const diff = Math.max(0, Date.now() - d)
  const m = Math.floor(diff / 60000)
  if (m < 60) return m <= 1 ? "1m" : `${m}m`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h`
  const dyy = Math.floor(h / 24)
  if (dyy < 7) return `${dyy}d`
  const w = Math.floor(dyy / 7)
  return `${w}w`
})

function startEdit() { editing.value = true; text.value = item.value.body; closeMenu() }
function cancelEdit() { editing.value = false }
function submitEdit() { emit("edit", { id: item.value.id, body: text.value }); editing.value = false }
function toggleMenu() { menuOpen.value = !menuOpen.value }
function closeMenu() { menuOpen.value = false }
</script>
