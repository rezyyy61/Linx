<script setup lang="ts">
import { computed } from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter } from 'vue-router'
import AvatarUser from "@/components/shared/AvatarUser.vue";

export type AnnouncementPreview = {
  id: number
  slug: string
  title: string
  body?: string | null
  excerpt?: string | null
  cover_url?: string | null
  publish_at?: string | null
  created_at?: string | null
  visibility?: 'public' | 'members' | 'supporters' | 'private'
  owner?: { name?: string | null; slug?: string | null; avatar?: string | null } | null
}

const props = defineProps<{ announcement: AnnouncementPreview }>()
const router = useRouter()

function stripHtml(x: string) {
  const d = new DOMParser().parseFromString(x || '', 'text/html')
  return d.body.textContent || ''
}
function isRTLString(s: string) {
  return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/.test(s)
}

const ownerName = computed(() => props.announcement.owner?.name || 'Owner')
const ownerAvatar = computed(() => props.announcement.owner?.avatar || null)
const ownerInitials = computed(() => {
  const n = (ownerName.value || '').trim()
  const parts = n ? n.split(/\s+/).slice(0, 2) : []
  return parts.map(p => p[0]?.toUpperCase()).join('') || 'AN'
})
const publishedAt = computed(() => {
  const src = props.announcement.publish_at || props.announcement.created_at || null
  if (!src) return ''
  const d = new Date(src)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
})
const excerptText = computed(() => {
  const raw = props.announcement.excerpt?.trim() || stripHtml(props.announcement.body || '')
  return raw.length > 260 ? raw.slice(0, 257) + '…' : raw
})
const isBodyRTL = computed(() => isRTLString(excerptText.value || ''))
const titleDir = computed(() => isRTLString(props.announcement.title || '') ? 'rtl' : 'ltr')
const bgImage = computed(() => props.announcement.cover_url || '')
const visibility = computed(() => props.announcement.visibility || 'public')

function openDetails() {
  if (!props.announcement?.slug) return
  router.push({ name: 'announcements.details', params: { slug: props.announcement.slug } })
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
      <div class="flex flex-col md:flex-row">
        <div class="relative md:w-56 w-full">
          <div class="relative h-full aspect-[16/9] md:aspect-auto overflow-hidden">
            <img
              v-if="bgImage"
              :src="bgImage"
              alt=""
              class="h-full w-full object-contain object-center transition-transform duration-300 group-hover:scale-[1.03]"
            >
            <div
              v-else
              class="h-full w-full grid place-items-center bg-zinc-100 dark:bg-zinc-800"
            >
              <Icon
                icon="mdi:image-off-outline"
                class="w-8 h-8 text-zinc-400"
              />
            </div>

            <div class="absolute inset-x-0 bottom-0 h-20 bg-gradient-to-t from-black/60 via-black/30 to-transparent" />
            <div class="absolute top-2 left-2 inline-flex items-center gap-1">
              <span class="px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide bg-white/90 dark:bg-black/60 text-zinc-800 dark:text-zinc-100 ring-1 ring-zinc-200/70 dark:ring-zinc-700/60">
                {{ visibility }}
              </span>
            </div>
          </div>
        </div>

        <div class="flex-1 p-4 md:p-5">
          <div class="flex items-center gap-3">
            <div class="h-10 w-10 rounded-xl overflow-hidden ring-1 ring-zinc-200 dark:ring-zinc-700 grid place-items-center bg-white/70 dark:bg-zinc-800/70">
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
              <div class="text-xs text-zinc-600 dark:text-zinc-400 truncate max-w-[12rem]">
                {{ ownerName }}
              </div>
              <div
                v-if="publishedAt"
                class="text-[11px] text-zinc-400"
              >
                {{ publishedAt }}
              </div>
            </div>
            <div class="ms-auto hidden md:flex items-center gap-1.5">
              <span class="px-2 py-1 rounded-full text-[11px] ring-1 ring-indigo-200/70 dark:ring-indigo-900/40 text-indigo-700 dark:text-indigo-300 bg-indigo-50/70 dark:bg-indigo-900/20">Announcement</span>
            </div>
          </div>

          <h3
            class="mt-3 text-[17px] md:text-[18px] font-extrabold leading-snug text-zinc-900 dark:text-zinc-100 line-clamp-2"
            :dir="titleDir"
          >
            {{ props.announcement.title }}
          </h3>

          <p
            v-if="excerptText"
            class="mt-2 text-[13.5px] md:text-[14px] text-zinc-700/90 dark:text-zinc-300/90 line-clamp-3"
            :dir="isBodyRTL ? 'rtl' : 'ltr'"
          >
            {{ excerptText }}
          </p>

          <div class="mt-4 flex items-center justify-between">
            <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-[13px] font-semibold">
              <span>Read</span>
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
.line-clamp-3{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden}
</style>
