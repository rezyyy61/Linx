<script setup lang="ts">
import { computed } from "vue"
import { Icon } from "@iconify/vue"
import { useRouter } from "vue-router"
import AvatarUser from "@/components/shared/AvatarUser.vue"

export type PublicationPreview = {
  id: number
  slug: string
  title: string
  issue?: string
  description?: string | null
  cover_url?: string | null
  is_published?: boolean
  publish_at?: string | null
  created_at?: string | null
  language?: string
  documents_count?: number
  owner?: { name?: string | null; slug?: string | null; avatar?: string | null; verified?: boolean } | null
}

const props = defineProps<{ publication: PublicationPreview }>()
const router = useRouter()

function stripHtml(x: string) {
  const d = new DOMParser().parseFromString(x || "", "text/html")
  return d.body.textContent || ""
}
function isRTLString(s: string) {
  return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/.test(s)
}

const ownerName = computed(() => props.publication.owner?.name || "Publisher")
const ownerAvatar = computed(() => props.publication.owner?.avatar || null)
const ownerVerified = computed(() => !!props.publication.owner?.verified)
const ownerInitials = computed(() => {
  const n = (ownerName.value || "").trim()
  const parts = n ? n.split(/\s+/).slice(0, 2) : []
  return parts.map(p => p[0]?.toUpperCase()).join("") || "PB"
})

const publishedAt = computed(() => {
  const src = props.publication.publish_at || props.publication.created_at || null
  if (!src) return ""
  const d = new Date(src)
  return d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "2-digit" })
})

const descText = computed(() => {
  const raw = (props.publication.description || "").trim()
  const clean = stripHtml(raw)
  return clean.length > 220 ? clean.slice(0, 217) + "…" : clean
})
const isBodyRTL = computed(() => isRTLString(descText.value || ""))
const titleDir = computed(() => isRTLString(props.publication.title || "") ? "rtl" : "ltr")
const lang = computed(() => (props.publication.language || "en").toUpperCase())
const issue = computed(() => props.publication.issue || "")
const cover = computed(() => props.publication.cover_url || "")
const statusBadgeText = computed(() => (props.publication.is_published ? "Published" : "Draft"))
const statusBadgeClass = computed(() =>
  props.publication.is_published
    ? "bg-emerald-50 text-emerald-700 ring-emerald-200/70 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-900/40"
    : "bg-zinc-100 text-zinc-700 ring-zinc-300/70 dark:bg-zinc-800/60 dark:text-zinc-300 dark:ring-zinc-700/60"
)

function openDetails() {
  if (!props.publication?.slug) return
  router.push({ name: "public.publication.detail", params: { slug: props.publication.slug } })
}
</script>

<template>
  <div class="mt-3">
    <div
      class="group relative overflow-hidden rounded-2xl ring-1 ring-zinc-200/70 dark:ring-zinc-800/70 bg-white dark:bg-zinc-900 shadow-sm hover:shadow-xl transition-all duration-300"
      role="button"
      tabindex="0"
      @click="openDetails"
      @keyup.enter="openDetails"
    >
      <div class="flex flex-col lg:flex-row">
        <div class="relative lg:w-64 w-full">
          <div class="relative h-full aspect-[16/9] lg:aspect-auto overflow-hidden">
            <img
              v-if="cover"
              :src="cover"
              alt=""
              class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            >
            <div
              v-else
              class="h-full w-full grid place-items-center bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700"
            >
              <Icon
                icon="mdi:newspaper-variant-outline"
                class="w-8 h-8 text-zinc-400"
              />
            </div>
            <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/60 via-black/25 to-transparent" />
            <div class="absolute top-2 left-2 inline-flex items-center gap-1">
              <span class="px-2 py-0.5 rounded-full text-[10px] tracking-wide bg-white/90 dark:bg-black/60 text-zinc-900 dark:text-zinc-100 ring-1 ring-zinc-200/70 dark:ring-zinc-700/60">
                {{ lang }}
              </span>
              <span
                v-if="issue"
                class="px-2 py-0.5 rounded-full text-[10px] tracking-wide bg-white/90 dark:bg-black/60 text-zinc-900 dark:text-zinc-100 ring-1 ring-zinc-200/70 dark:ring-zinc-700/60"
              >
                {{ issue }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex-1 p-4 lg:p-6">
          <div class="flex items-center gap-3">
            <div class="h-11 w-11 rounded-xl overflow-hidden ring-1 ring-zinc-200 dark:ring-zinc-700 grid place-items-center bg-white/70 dark:bg-zinc-800/70">
              <AvatarUser
                v-if="ownerAvatar"
                :src="ownerAvatar"
                size="sm"
                ring
              />
              <span
                v-else
                class="text-[11px] font-semibold text-zinc-600 dark:text-zinc-300"
              >{{ ownerInitials }}</span>
            </div>
            <div class="min-w-0">
              <div class="flex items-center gap-1 text-sm text-zinc-700 dark:text-zinc-300 truncate max-w-[14rem]">
                <span class="truncate">{{ ownerName }}</span>
                <Icon
                  v-if="ownerVerified"
                  icon="mdi:check-decagram"
                  class="w-4 h-4 text-emerald-600"
                />
              </div>
              <div
                v-if="publishedAt"
                class="text-[11px] text-zinc-400"
              >
                {{ publishedAt }}
              </div>
            </div>
            <div class="ms-auto hidden md:flex items-center gap-2">
              <span
                class="px-2.5 py-1 rounded-full text-[11px] ring-1"
                :class="statusBadgeClass"
              >{{ statusBadgeText }}</span>
              <span class="px-2.5 py-1 rounded-full text-[11px] ring-1 ring-indigo-200/70 dark:ring-indigo-900/40 text-indigo-700 dark:text-indigo-300 bg-indigo-50/70 dark:bg-indigo-900/20">Publication</span>
            </div>
          </div>

          <h3
            class="mt-3 text-[18px] lg:text-[19px] font-extrabold leading-snug text-zinc-900 dark:text-zinc-100 line-clamp-2"
            :dir="titleDir"
          >
            {{ publication.title }}
          </h3>

          <p
            v-if="descText"
            class="mt-2 text-[13.5px] lg:text-[14px] text-zinc-700/90 dark:text-zinc-300/90 line-clamp-3"
            :dir="isBodyRTL ? 'rtl' : 'ltr'"
          >
            {{ descText }}
          </p>

          <div class="mt-4 flex items-center justify-between">
            <div class="inline-flex items-center gap-2 text-emerald-700 dark:text-emerald-300 text-[13px] font-semibold">
              <span>Open</span>
              <Icon
                icon="mdi:arrow-right"
                class="w-4 h-4 translate-x-0 group-hover:translate-x-0.5 transition-transform"
              />
            </div>

            <div class="flex items-center gap-2">
              <button class="px-2.5 py-1.5 rounded-lg ring-1 ring-zinc-300 dark:ring-zinc-700 text-[12px] hover:bg-zinc-50 dark:hover:bg-zinc-800">
                Share
              </button>
              <span class="hidden sm:inline px-2.5 py-1.5 rounded-lg ring-1 ring-zinc-300 dark:ring-zinc-700 text-[12px]">Save</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-2{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden}
.line-clamp-3{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden}
</style>
