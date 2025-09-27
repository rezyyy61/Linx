<script setup lang="ts">
import { computed, ref, onMounted } from "vue"
import { useRouter } from "vue-router"
import { Icon } from "@iconify/vue"
import { fetchAnnouncement, type AnnouncementPublic } from "../api/api"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import { ShareModal } from "@/modules/share"

const props = defineProps<{ slug: string }>()
const router = useRouter()

const loading = ref(true)
const notFound = ref(false)
const item = ref<AnnouncementPublic | null>(null)
const shareOpen = ref(false)
const copied = ref(false)

function sanitizeHtml(html: string) {
  const doc = new DOMParser().parseFromString(html || "", "text/html")
  const allowed = new Set(["b","strong","i","em","u","br","p","ul","ol","li","a","blockquote","span"])
  const walk = (node: Node) => {
    const children = Array.from(node.childNodes)
    for (const ch of children) {
      if (ch.nodeType === 1) {
        const el = ch as HTMLElement
        if (!allowed.has(el.tagName.toLowerCase())) {
          const parent = el.parentNode
          if (!parent) continue
          while (el.firstChild) parent.insertBefore(el.firstChild, el)
          parent.removeChild(el)
          continue
        }
        for (const attr of Array.from(el.attributes)) {
          const n = attr.name.toLowerCase()
          if (el.tagName.toLowerCase() === "a" && n === "href") {
            const v = attr.value || ""
            const ok = /^https?:\/\//i.test(v) || v.startsWith("#") || v.startsWith("/")
            if (!ok) el.removeAttribute(attr.name)
          } else {
            el.removeAttribute(attr.name)
          }
        }
        if (el.tagName.toLowerCase() === "a" && el.getAttribute("href")) {
          el.setAttribute("target", "_blank")
          el.setAttribute("rel", "noopener noreferrer")
        }
        walk(el)
      }
    }
  }
  walk(doc.body)
  return doc.body.innerHTML
}

function stripHtml(x: string) {
  const d = new DOMParser().parseFromString(x || "", "text/html")
  return d.body.textContent || ""
}

function isRTLString(s: string) {
  return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/.test(s)
}

const titleDir = computed(() => isRTLString(item.value?.title || "") ? "rtl" : "ltr")
const bodyDir = computed(() => isRTLString(stripHtml(item.value?.body || "")) ? "rtl" : "ltr")

const publishedAt = computed(() => {
  const src = (item.value as any)?.publish_at || (item.value as any)?.created_at || new Date().toISOString()
  const d = new Date(src)
  return d.toLocaleDateString(undefined, { year: "numeric", month: "long", day: "2-digit" })
})

const bodyHtml = computed(() => sanitizeHtml(item.value?.body || ""))

const readTime = computed(() => {
  const words = stripHtml(item.value?.body || "").trim().split(/\s+/).filter(Boolean).length
  const m = Math.max(1, Math.round(words / 200))
  return `${m} min read`
})

function goBackToList() {
  router.push({ name: "home", query: { tab: "announcements" } })
}

function visibilityBadgeClass(v?: string | null) {
  if (v === "private") return "bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-200 ring-red-200/60 dark:ring-red-800/40"
  if (v === "members") return "bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200 ring-blue-200/60 dark:ring-blue-800/40"
  if (v === "supporters") return "bg-amber-50 text-amber-800 dark:bg-amber-900/30 dark:text-amber-200 ring-amber-200/60 dark:ring-amber-800/40"
  return "bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-200 ring-emerald-200/60 dark:ring-emerald-800/40"
}

async function copyLink() {
  try {
    const u = new URL(window.location.href)
    await navigator.clipboard.writeText(u.toString())
    copied.value = true
    setTimeout(() => (copied.value = false), 1500)
  } catch { /* empty */ }
}

onMounted(async () => {
  try {
    loading.value = true
    const a = await fetchAnnouncement(props.slug)
    item.value = a
  } catch {
    notFound.value = true
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-8 space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <button
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
        @click="goBackToList"
      >
        <Icon
          icon="mdi:arrow-left"
          class="w-4 h-4"
        />
        <span>Back to announcements</span>
      </button>

      <div class="flex items-center gap-2">
        <button
          v-if="!loading && !notFound"
          class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
          @click="shareOpen = true"
        >
          <Icon
            icon="mdi:share-variant"
            class="w-4 h-4"
          />
          <span>Share</span>
        </button>
        <button
          v-if="!loading && !notFound"
          class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
          @click="copyLink"
        >
          <Icon
            :icon="copied ? 'mdi:check' : 'mdi:link-variant'"
            class="w-4 h-4"
          />
          <span>{{ copied ? "Copied" : "Copy link" }}</span>
        </button>
      </div>
    </div>

    <div
      v-if="loading"
      class="rounded-2xl border border-gray-200 bg-white p-8 shadow-md dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div class="animate-pulse space-y-5">
        <div class="h-7 w-2/3 bg-gray-200 dark:bg-zinc-700 rounded" />
        <div class="h-4 w-1/4 bg-gray-200 dark:bg-zinc-700 rounded" />
        <div class="aspect-[16/9] w-full bg-gray-200 dark:bg-zinc-800 rounded-2xl" />
        <div class="space-y-2">
          <div class="h-4 bg-gray-200 dark:bg-zinc-700 rounded" />
          <div class="h-4 bg-gray-200 dark:bg-zinc-700 rounded" />
          <div class="h-4 bg-gray-200 dark:bg-zinc-700 rounded w-2/3" />
        </div>
      </div>
    </div>

    <div
      v-else-if="notFound"
      class="rounded-2xl border border-red-200 bg-red-50 p-8 dark:border-red-900/40 dark:bg-red-950/40"
    >
      <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
        <Icon
          icon="mdi:alert-circle-outline"
          class="w-5 h-5"
        />
        <span>Announcement not found</span>
      </div>
    </div>

    <div
      v-else
      class="rounded-2xl overflow-hidden ring-1 ring-gray-200/80 dark:ring-zinc-800 bg-white dark:bg-zinc-900 shadow"
    >
      <div class="p-5 md:p-7">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-3 text-sm text-neutral-700 dark:text-neutral-300">
            <AvatarUser
              :src="item?.owner?.avatar || undefined"
              :name="item?.owner?.name || 'Owner'"
              size="sm"
              ring
            />
            <span v-if="item?.owner?.name">{{ item?.owner?.name }}</span>
            <span class="text-neutral-400">•</span>
            <span
              class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-[11px] capitalize ring-1"
              :class="visibilityBadgeClass(item?.visibility)"
            >
              {{ item?.visibility }}
            </span>
            <span class="text-neutral-400">•</span>
            <span>{{ publishedAt }}</span>
            <span
              v-if="item?.body"
              class="text-neutral-400"
            >•</span>
            <span v-if="item?.body">{{ readTime }}</span>
          </div>

          <span
            v-if="item?.is_pinned"
            class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 text-xs"
          >Pinned</span>
        </div>

        <h1
          class="mt-4 text-2xl md:text-3xl font-extrabold leading-snug tracking-tight text-neutral-900 dark:text-neutral-100"
          :dir="titleDir"
        >
          {{ item?.title }}
        </h1>
      </div>

      <div class="w-full bg-neutral-50 dark:bg-neutral-800/60">
        <div class="aspect-[16/9] w-full flex items-center justify-center">
          <img
            v-if="item?.cover_url"
            :src="item?.cover_url"
            alt=""
            class="h-full w-full object-contain"
          >
          <div
            v-else
            class="h-full w-full flex items-center justify-center text-neutral-400"
          >
            <Icon
              icon="mdi:image-off-outline"
              class="w-12 h-12"
            />
          </div>
        </div>
      </div>

      <div class="p-5 md:p-7">
        <!-- eslint-disable vue/no-v-html -->
        <div
          v-if="bodyHtml"
          class="prose max-w-none prose-p:my-3 prose-ul:my-3 prose-ol:my-3 dark:prose-invert"
          :dir="bodyDir"
          v-html="bodyHtml"
        />
        <p
          v-else
          class="text-neutral-600 dark:text-neutral-400"
        >
          No content.
        </p>
      </div>
    </div>
  </section>

  <ShareModal
    v-if="item"
    :open="shareOpen"
    :shareable-alias="'announcement'"
    :shareable-type="'App\\\\Models\\\\Announcement\\\\Announcement'"
    :shareable-id="item.id"
    :announcement="item"
    :title="item.title"
    :text="(item.excerpt || stripHtml(item.body || '') || item.title)"
    @close="shareOpen = false"
    @shared="shareOpen = false"
  />
</template>

<style scoped>
.prose :where(p):first-child { margin-top: 0 }
.prose :where(p):last-child { margin-bottom: 0 }
</style>
