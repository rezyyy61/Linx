<template>
  <div class="space-y-3">
    <div class="flex items-center gap-2 mx-auto px-2 py-2 rounded-xl bg-white/70 dark:bg-zinc-900/45 border border-zinc-200/70 dark:border-white/10 shadow-sm">
      <div class="inline-flex rounded-lg overflow-hidden border border-zinc-300/80 dark:border-white/10">
        <button
          :class="tab===FILTER_ALL ? activeTab : baseTab"
          @click="tab=FILTER_ALL"
        >
          All
        </button>
        <button
          :class="tab===FILTER_UNREAD ? activeTab : baseTab"
          @click="tab=FILTER_UNREAD"
        >
          Unread
        </button>
      </div>

      <select
        v-model="kind"
        class="px-3 py-2 rounded-md border border-zinc-300/80 dark:border-white/10 bg-white/90 dark:bg-zinc-900/60 text-sm text-zinc-800 dark:text-zinc-100"
      >
        <option value="all">
          All types
        </option>
        <option
          v-for="k in kinds"
          :key="k"
          :value="k"
        >
          {{ k }}
        </option>
      </select>

      <button
        class="px-3 py-2 rounded-md border border-zinc-300/80 dark:border-white/10 bg-white/90 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-900/70 text-sm"
        @click="markAll"
      >
        Mark all
      </button>
    </div>

    <!-- List -->
    <div class="space-y-2">
      <div
        v-for="n in itemsVM"
        :key="n.id"
        :class="[
          'group relative p-3 rounded-2xl border flex items-start gap-3 transition-colors cursor-pointer',
          'text-sm shadow-[0_8px_20px_-12px_rgba(0,0,0,0.55)]',
          'bg-white/90 hover:bg-white dark:bg-gradient-to-br dark:from-zinc-900/70 dark:to-zinc-800/60 dark:hover:from-zinc-900/80 dark:hover:to-zinc-800/75',
          'border-zinc-200/80 dark:border-white/10',
          !n.read ? 'ring-1 ring-indigo-400/25 dark:ring-indigo-400/30' : 'ring-0'
        ]"
        role="button"
        tabindex="0"
        @click="toggle(n.id)"
        @keydown="onKeyToggle($event,n.id)"
      >
        <!-- Avatar / link -->
        <RouterLink
          v-if="n.href"
          :to="n.href"
          class="shrink-0"
          @click.stop
        >
          <img
            v-if="n.avatar"
            :src="n.avatar"
            alt=""
            class="w-9 h-9 rounded-full object-cover ring-2 ring-white/70 dark:ring-white/10"
          >
          <div
            v-else
            class="w-9 h-9 rounded-full bg-zinc-300 dark:bg-zinc-600 ring-2 ring-white/60 dark:ring-white/10"
          />
        </RouterLink>

        <!-- Text -->
        <div class="flex-1 min-w-0">
          <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
            {{ n.title }}
          </p>
          <p
            :class="[
              'text-zinc-600 dark:text-zinc-200/90',
              expanded[n.id] ? 'whitespace-normal break-words' : 'truncate'
            ]"
          >
            {{ n.body }}
          </p>
        </div>

        <!-- Actions -->
        <div
          class="flex items-center gap-2"
          @click.stop
        >
          <span
            v-if="!n.read"
            class="inline-block w-2 h-2 rounded-full bg-indigo-400 ring-2 ring-indigo-400/25"
          />
          <span class="text-xs text-zinc-500 dark:text-zinc-300 whitespace-nowrap">{{ n.time }}</span>
          <button
            v-if="!n.read"
            class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
            aria-label="Mark as read"
            @click="mark(n.id)"
          >
            <Icon
              icon="mdi:check"
              width="16"
              height="16"
            />
          </button>
          <button
            class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
            aria-label="Delete"
            @click="remove(n.id)"
          >
            <Icon
              icon="mdi:trash-can-outline"
              width="16"
              height="16"
            />
          </button>
        </div>
      </div>
    </div>

    <!-- Infinite scroll sentinel + states -->
    <div
      ref="sentinel"
      class="h-8"
    />
    <div
      v-if="loading"
      class="text-center text-sm text-zinc-600 dark:text-zinc-300"
    >
      Loading…
    </div>
    <div
      v-else-if="!hasMore && itemsVM.length===0"
      class="text-center text-sm text-zinc-600 dark:text-zinc-300"
    >
      No notifications
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue"
import { ref, computed, onMounted, onBeforeUnmount } from "vue"
import { useNotificationsStore } from "@/modules/notifications/store"
import { storeToRefs } from "pinia"

const FILTER_ALL = "all"
const FILTER_UNREAD = "unread"

const props = defineProps<{
  items: Array<{ id:number; title:string; body:string; time:string; read:boolean; avatar?:string|null; href?:string|null }>
}>()

const store = useNotificationsStore()
const { items: storeItems, hasMore } = storeToRefs(store)

const tab = ref<typeof FILTER_ALL | typeof FILTER_UNREAD>(FILTER_ALL)
const kind = ref<string>("all")

const kinds = computed(() => Array.from(new Set(storeItems.value.map(i => i.kind))).sort())

const byIdKind = computed<Record<number,string>>(() => {
  const m: Record<number,string> = {}
  for (const it of storeItems.value) m[it.id] = it.kind
  return m
})

const expanded = ref<Record<number, boolean>>({})
function toggle(id: number) { expanded.value[id] = !expanded.value[id] }
function onKeyToggle(e: KeyboardEvent, id: number) {
  if (e.key === "Enter" || e.key === " ") { e.preventDefault(); toggle(id) }
}

const itemsVM = computed(() => {
  return props.items.filter(n => {
    if (tab.value === FILTER_UNREAD && n.read) return false
    if (kind.value !== "all") {
      const k = byIdKind.value[n.id]
      if (!k || k !== kind.value) return false
    }
    return true
  })
})

const loading = ref(false)
const sentinel = ref<HTMLElement|null>(null)
let io: IntersectionObserver | null = null

async function onIntersect(entries: IntersectionObserverEntry[]) {
  const entry = entries[0]
  if (!entry.isIntersecting || loading.value || !hasMore.value) return
  loading.value = true
  await store.fetchNextPage()
  loading.value = false
}
function setupIO() {
  if (io) io.disconnect()
  io = new IntersectionObserver(onIntersect, { root: null, rootMargin: "400px 0px 0px 0px", threshold: 0 })
  if (sentinel.value) io.observe(sentinel.value)
}
onMounted(() => { setupIO() })
onBeforeUnmount(() => { if (io) io.disconnect() })

async function mark(id:number){ await store.markOneAsRead(id) }
async function remove(id:number){ await store.remove(id) }
async function markAll(){ await store.markAll() }

const baseTab = "px-3 py-1 text-sm bg-white/90 dark:bg-zinc-900/60 text-zinc-700 dark:text-zinc-100 hover:bg-white dark:hover:bg-zinc-900/70"
const activeTab = baseTab + " border border-indigo-500/30 dark:border-indigo-400/30 text-zinc-900 dark:text-white"
</script>
