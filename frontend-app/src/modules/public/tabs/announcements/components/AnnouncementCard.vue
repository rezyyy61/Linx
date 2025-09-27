<script setup lang="ts">
import { computed, ref } from "vue"
import { useRouter } from "vue-router"
import { Icon } from "@iconify/vue"
import AvatarUser from "@/components/shared/AvatarUser.vue"
import { ShareModal } from "@/modules/share"

type Visibility = "public" | "members" | "supporters" | "private"

export type AnnouncementCardModel = {
  id: number
  slug: string
  title: string
  excerpt?: string | null
  body?: string | null
  is_pinned: boolean
  visibility: Visibility
  publish_at?: string | null
  created_at?: string | null
  cover_url?: string | null
  owner?: { name?: string | null; slug?: string | null; avatar?: string | null } | null
}

const props = defineProps<{ announcement?: AnnouncementCardModel; item?: AnnouncementCardModel }>()
const announcement = computed(() => (props.announcement ?? props.item)!)
const router = useRouter()
const hasProfileRoute = computed(() => router.hasRoute("profile.public") && !!announcement.value?.owner?.slug)
const hasDetailsRoute = computed(() => router.hasRoute("announcements.details"))

function isRTLString(s: string) {
  return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/.test(s)
}

const isTitleRTL = computed(() => isRTLString(announcement.value?.title || ""))
const isBodyRTL = computed(() => isRTLString((announcement.value?.body || "").replace(/<[^>]*>/g, "")))

const publishedAt = computed(() => {
  const src = announcement.value?.publish_at || announcement.value?.created_at || new Date().toISOString()
  const d = new Date(src)
  return d.toLocaleDateString(undefined, { year: "numeric", month: "long", day: "2-digit" })
})

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

const sanitizedBody = computed(() => sanitizeHtml(announcement.value?.body || ""))

const shareOpen = ref(false)
</script>

<template>
  <article class="rounded-2xl bg-white dark:bg-neutral-900 ring-1 ring-neutral-200/60 dark:ring-neutral-800 hover:shadow-md transition overflow-hidden flex flex-col">
    <div class="relative">
      <div class="w-full h-52 bg-neutral-100 dark:bg-neutral-800 flex items-center justify-center">
        <img
          v-if="announcement.cover_url"
          :src="announcement.cover_url"
          alt=""
          class="max-h-full max-w-full object-contain"
        >
        <Icon
          v-else
          icon="mdi:image-off-outline"
          class="w-12 h-12 text-neutral-400 dark:text-neutral-500"
        />
      </div>

      <div class="absolute top-3 left-3 flex items-center gap-2">
        <span
          v-if="announcement.is_pinned"
          class="inline-flex items-center rounded-full bg-amber-100 text-amber-800 px-2.5 py-1 text-xs"
        >Pinned</span>
        <span class="inline-flex items-center rounded-full bg-black/70 text-white px-2 py-0.5 text-[10px] backdrop-blur capitalize">
          {{ announcement.visibility }}
        </span>
      </div>

      <button
        class="absolute top-3 right-3 rounded-full bg-white/90 dark:bg-black/60 backdrop-blur p-2 ring-1 ring-neutral-200 dark:ring-neutral-700"
        @click.stop="shareOpen = true"
      >
        <Icon
          icon="mdi:share-variant"
          class="w-5 h-5 text-indigo-600 dark:text-indigo-400"
        />
      </button>
    </div>

    <div class="p-5 flex-1 flex flex-col gap-3">
      <div class="flex items-center gap-2 text-sm text-neutral-700 dark:text-neutral-300">
        <AvatarUser
          :src="announcement.owner?.avatar || undefined"
          :name="announcement.owner?.name || 'Owner'"
          size="sm"
          ring
        />
        <template v-if="hasProfileRoute">
          <router-link
            :to="{ name: 'profile.public', params: { slug: announcement.owner!.slug } }"
            class="hover:underline"
          >
            {{ announcement.owner?.name || 'Owner' }}
          </router-link>
        </template>
        <template v-else>
          <span>{{ announcement.owner?.name || 'Owner' }}</span>
        </template>
      </div>

      <h3
        class="text-lg md:text-xl font-semibold leading-tight text-neutral-900 dark:text-neutral-100 min-h-[3.25rem]"
        :dir="isTitleRTL ? 'rtl' : 'ltr'"
        :class="isTitleRTL ? 'rtl-line-clamp' : 'line-clamp-2'"
      >
        {{ announcement.title }}
      </h3>

      <!-- eslint-disable vue/no-v-html -->
      <div
        v-if="sanitizedBody"
        class="text-sm text-neutral-600 dark:text-neutral-400 clamped-html"
        :dir="isBodyRTL ? 'rtl' : 'ltr'"
        v-html="sanitizedBody"
      />
      <!-- eslint-enable vue/no-v-html -->
      <div
        v-else
        class="min-h-[2.5rem]"
      />

      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-neutral-600 dark:text-neutral-400">
        <div class="inline-flex items-center gap-1">
          <Icon
            icon="mdi:calendar-outline"
            class="w-4 h-4"
          />
          <span>{{ publishedAt }}</span>
        </div>
      </div>

      <div class="mt-auto pt-1 flex items-center justify-between">
        <template v-if="hasDetailsRoute">
          <router-link
            :to="{ name: 'announcements.details', params: { slug: announcement.slug } }"
            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
          >
            Details
          </router-link>
        </template>
        <template v-else>
          <a
            class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
            :href="`/announcements/${announcement.slug}`"
          >
            Details
          </a>
        </template>

        <button
          data-card-share
          class="px-3 py-1.5 rounded-lg text-sm ring-1 ring-neutral-300 dark:ring-neutral-700 hover:bg-neutral-100 dark:hover:bg-neutral-800"
          @click.stop="shareOpen = true"
        >
          Share
        </button>
      </div>
    </div>
  </article>

  <ShareModal
    :open="shareOpen"
    :shareable-alias="'announcement'"
    :shareable-type="'App\\\\Models\\\\Announcement\\\\Announcement'"
    :shareable-id="announcement.id"
    :announcement="announcement"
    :title="announcement.title"
    :text="announcement.excerpt || announcement.title"
    @close="shareOpen = false"
    @shared="shareOpen = false"
  />
</template>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.rtl-line-clamp {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
  text-align: right;
}
.clamped-html {
  position: relative;
  max-height: 3.8rem;
  overflow: hidden;
}
.clamped-html {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.clamped-html p { margin: 0; }
.clamped-html ul, .clamped-html ol { margin: 0; padding-inline-start: 1rem; }
.clamped-html a { text-decoration: underline; }

.clamped-html p { margin: 0; }
.clamped-html ul, .clamped-html ol { margin: 0; padding-inline-start: 1rem; }
.clamped-html a { text-decoration: underline; }
</style>
