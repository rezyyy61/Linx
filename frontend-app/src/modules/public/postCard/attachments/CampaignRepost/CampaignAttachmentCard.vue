<script setup lang="ts">
import {computed} from 'vue'
import { Icon } from '@iconify/vue'
import { useRouter } from 'vue-router'
import AvatarUser from "@/components/shared/AvatarUser.vue"

export type CampaignPreview = {
  id: number
  slug: string
  title: string
  excerpt?: string | null
  cover_url?: string | null
  publish_at?: string | null
  created_at?: string | null
  visibility?: 'public' | 'members' | 'supporters' | 'private' | 'unlisted'
  kind?: 'fundraising' | 'petition' | 'volunteer' | 'awareness' | string | null
  status?: string | null
  owner?: { name?: string | null; slug?: string | null; avatar?: string | null } | null
  goal_amount?: number | null
  raised_amount?: number | null
  goal_currency?: string | null
  signature_goal?: number | null
  signatures_count?: number | null
  needed_slots?: number | null
  filled_slots?: number | null
  target_reach?: number | null
  current_reach?: number | null
}

const props = defineProps<{ campaign: CampaignPreview }>()
const router = useRouter()

const ownerName = computed(() => props.campaign.owner?.name || 'Owner')
const ownerAvatar = computed(() => props.campaign.owner?.avatar || null)
const ownerInitials = computed(() => {
  const n = (ownerName.value || '').trim()
  const parts = n ? n.split(/\s+/).slice(0, 2) : []
  return parts.map(p => p[0]?.toUpperCase()).join('') || 'CM'
})

const publishedAt = computed(() => {
  const src = props.campaign.publish_at || props.campaign.created_at || null
  if (!src) return ''
  const d = new Date(src)
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: '2-digit' })
})

const status = computed(() => (props.campaign.status || '').toString())
const statusLower = computed(() => status.value.toLowerCase())

const n = (x: any) => Number.isFinite(Number(x)) ? Number(x) : 0

const resolvedKind = computed(() => {
  const k = String(props.campaign.kind || '').toLowerCase()
  if (k) return k
  if (n(props.campaign.goal_amount) || n(props.campaign.raised_amount)) return 'fundraising'
  if (n(props.campaign.signature_goal) || n(props.campaign.signatures_count)) return 'petition'
  if (n(props.campaign.needed_slots) || n(props.campaign.filled_slots)) return 'volunteer'
  if (n(props.campaign.target_reach) || n(props.campaign.current_reach)) return 'awareness'
  return ''
})

const ringClass = computed(() => {
  if (statusLower.value === 'published' || statusLower.value === 'active') return 'ring-emerald-200/80 dark:ring-emerald-900/40'
  if (statusLower.value === 'draft' || statusLower.value === 'pending') return 'ring-amber-200/80 dark:ring-amber-900/40'
  return 'ring-zinc-200/70 dark:ring-zinc-800/70'
})

const auraClass = computed(() => {
  if (statusLower.value === 'published' || statusLower.value === 'active') return 'shadow-emerald-200/50 hover:shadow-emerald-300/60'
  if (statusLower.value === 'draft' || statusLower.value === 'pending') return 'shadow-amber-200/50 hover:shadow-amber-300/60'
  return 'shadow-zinc-200/50 hover:shadow-zinc-300/60'
})

function isRTLString(s: string) {
  return /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\u0590-\u05FF]/.test(s)
}
const titleDir = computed(() => isRTLString(props.campaign.title || '') ? 'rtl' : 'ltr')
const excerptText = computed(() => {
  const raw = (props.campaign.excerpt || '').trim()
  return raw.length > 220 ? raw.slice(0, 217) + '…' : raw
})
const isBodyRTL = computed(() => isRTLString(excerptText.value || ''))

const hasProgress = computed(() => {
  const k = resolvedKind.value
  if (k === 'fundraising') return n(props.campaign.goal_amount) > 0
  if (k === 'petition')    return n(props.campaign.signature_goal) > 0
  if (k === 'volunteer')   return n(props.campaign.needed_slots) > 0
  if (k === 'awareness')   return n(props.campaign.target_reach) > 0
  return false
})

const progressPair = computed(() => {
  const k = resolvedKind.value
  if (k === 'fundraising') return { value: n(props.campaign.raised_amount),    max: n(props.campaign.goal_amount) }
  if (k === 'petition')    return { value: n(props.campaign.signatures_count), max: n(props.campaign.signature_goal) }
  if (k === 'volunteer')   return { value: n(props.campaign.filled_slots),     max: n(props.campaign.needed_slots) }
  if (k === 'awareness')   return { value: n(props.campaign.current_reach),    max: n(props.campaign.target_reach) }
  return { value: 0, max: 0 }
})

const progressPct = computed(() => {
  const v = progressPair.value.value
  const m = progressPair.value.max
  if (m <= 0) return 0
  return Math.min(100, Math.round((v / m) * 100))
})

const metaLabel = computed(() => {
  const k = resolvedKind.value
  if (k === 'fundraising') {
    const r = n(props.campaign.raised_amount)
    const g = n(props.campaign.goal_amount)
    const cur = props.campaign.goal_currency || ''
    return `${r.toLocaleString()} / ${g.toLocaleString()} ${cur}`.trim()
  }
  if (k === 'petition') {
    const r = n(props.campaign.signatures_count)
    const g = n(props.campaign.signature_goal)
    return `${r.toLocaleString()} / ${g.toLocaleString()} signatures`
  }
  if (k === 'volunteer') {
    const r = n(props.campaign.filled_slots)
    const g = n(props.campaign.needed_slots)
    return `${r.toLocaleString()} / ${g.toLocaleString()} volunteers`
  }
  if (k === 'awareness') {
    const r = n(props.campaign.current_reach)
    const g = n(props.campaign.target_reach)
    return `${r.toLocaleString()} / ${g.toLocaleString()} reach`
  }
  return ''
})

const progressToneClass = computed(() => {
  const k = resolvedKind.value
  if (k === 'fundraising') return 'from-amber-400 to-rose-400'
  if (k === 'petition')    return 'from-indigo-400 to-sky-400'
  if (k === 'volunteer')   return 'from-teal-400 to-emerald-400'
  if (k === 'awareness')   return 'from-fuchsia-400 to-violet-400'
  return 'from-zinc-400 to-indigo-400'
})

function openDetails() {
  if (!props.campaign?.slug) return
  router.push({ name: 'campaigns.details', params: { slug: props.campaign.slug } })
}
</script>

<template>
  <div class="mt-3">
    <div
      class="group relative overflow-hidden rounded-2xl bg-white dark:bg-zinc-900 transition-all duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500"
      role="button"
      tabindex="0"
      @click="openDetails"
      @keyup.enter="openDetails"
    >
      <div
        class="absolute -inset-0.5 rounded-3xl blur-xl opacity-0 group-hover:opacity-100 transition-opacity"
        :class="statusLower==='published'||statusLower==='active' ? 'bg-gradient-to-tr from-emerald-300/30 via-indigo-300/30 to-sky-300/30' : 'bg-gradient-to-tr from-zinc-300/20 via-indigo-300/20 to-sky-300/20'"
      />
      <div
        class="relative rounded-2xl ring bg-gradient-to-br from-white to-zinc-50/80 dark:from-zinc-900 dark:to-zinc-900/80 shadow-sm hover:shadow-xl"
        :class="[ringClass, auraClass]"
      >
        <div class="flex flex-col md:flex-row">
          <div class="relative md:w-60 w-full">
            <div class="relative h-full aspect-[16/9] md:aspect-auto overflow-hidden">
              <img
                v-if="campaign.cover_url"
                :src="campaign.cover_url"
                alt=""
                class="h-full w-full object-cover object-center transition-transform duration-500 group-hover:scale-[1.035]"
              >
              <div
                v-else
                class="h-full w-full grid place-items-center bg-zinc-100 dark:bg-zinc-800"
              >
                <Icon
                  icon="mdi:image-off-outline"
                  class="w-9 h-9 text-zinc-400"
                />
              </div>
              <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/60 via-black/25 to-transparent" />
              <div class="absolute top-2 left-2 right-2 flex items-center gap-1.5">
                <span
                  v-if="resolvedKind"
                  class="px-2 py-0.5 rounded-full text-[10px] uppercase tracking-wide bg-white/90 dark:bg-black/60 text-zinc-800 dark:text-zinc-100 ring-1 ring-zinc-200/70 dark:ring-zinc-700/60"
                >
                  {{ resolvedKind }}
                </span>
              </div>
            </div>
          </div>

          <div class="flex-1 p-4 md:p-5">
            <div class="flex items-center gap-3">
              <div class="h-11 w-11 rounded-xl overflow-hidden ring-1 ring-zinc-200 dark:ring-zinc-700 grid place-items-center bg-white/70 dark:bg-zinc-800/70">
                <AvatarUser
                  v-if="ownerAvatar"
                  :src="ownerAvatar"
                  size="md"
                  ring
                />
                <span
                  v-else
                  class="text-[12px] font-semibold text-zinc-600 dark:text-zinc-300"
                >{{ ownerInitials }}</span>
              </div>
              <div class="min-w-0">
                <div class="text-[12px] text-zinc-600 dark:text-zinc-400 truncate max-w-[12rem]">
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
                <span class="px-2 py-1 rounded-full text-[11px] ring-1 ring-indigo-200/70 dark:ring-indigo-900/40 text-indigo-700 dark:text-indigo-300 bg-indigo-50/70 dark:bg-indigo-900/20">Campaign</span>
              </div>
            </div>

            <h3
              class="mt-3 text-[17.5px] md:text-[19px] font-extrabold leading-snug text-zinc-900 dark:text-zinc-100 line-clamp-2"
              :dir="titleDir"
            >
              {{ campaign.title }}
            </h3>

            <p
              v-if="excerptText"
              class="mt-2 text-[13.5px] md:text-[14px] text-zinc-700/90 dark:text-zinc-300/90 line-clamp-3"
              :dir="isBodyRTL ? 'rtl' : 'ltr'"
            >
              {{ excerptText }}
            </p>

            <div
              v-if="hasProgress"
              class="mt-3 space-y-1.5"
            >
              <div class="h-1.5 rounded-full bg-zinc-200 dark:bg-zinc-800 overflow-hidden">
                <div
                  class="h-full rounded-full bg-gradient-to-r transition-all duration-500 ease-out"
                  :class="progressToneClass"
                  :style="{ width: progressPct + '%' }"
                />
              </div>
              <div class="flex items-center justify-between text-[11.5px] text-zinc-600 dark:text-zinc-400">
                <span>{{ metaLabel }}</span>
                <span>{{ progressPct }}%</span>
              </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
              <div class="inline-flex items-center gap-2 text-indigo-600 dark:text-indigo-400 text-[13px] font-semibold">
                <span>Details</span>
                <Icon
                  icon="mdi:arrow-right"
                  class="w-4 h-4 translate-x-0 group-hover:translate-x-0.5 transition-transform"
                />
              </div>
              <div class="flex items-center gap-2">
                <span class="px-2.5 py-1.5 rounded-lg ring-1 ring-zinc-300 dark:ring-zinc-700 text-[12px] hover:bg-zinc-50 dark:hover:bg-zinc-800">Share</span>
                <span class="hidden sm:inline px-2.5 py-1.5 rounded-lg ring-1 ring-zinc-300 dark:ring-zinc-700 text-[12px]">Save</span>
              </div>
            </div>
          </div>
        </div>

        <div
          class="pointer-events-none absolute -bottom-10 -right-10 h-28 w-28 rounded-full blur-2xl opacity-0 group-hover:opacity-60 transition-opacity duration-500"
          :class="`bg-gradient-to-tr ${progressToneClass}`"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.line-clamp-3{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;overflow:hidden}
</style>
