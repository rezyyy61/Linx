<script setup lang="ts">
import { computed, ref } from "vue"
import { Icon } from "@iconify/vue"
import ProgressBar from "./ProgressBar.vue"
import CampaignActions from "./CampaignActions.vue"
import type { CampaignPublic } from "../api/types"
import { isFundraising, isPetition, isVolunteer, isAwareness } from "../api/types"
import { ShareModal } from "@/modules/share"

const props = defineProps<{ campaign: CampaignPublic }>()

const isShareOpen = ref(false)

const toNum = (x: any) => {
  const n = Number(x)
  return Number.isFinite(n) ? n : 0
}

const hasProgress = computed(() => {
  if (isFundraising(props.campaign)) return toNum(props.campaign.goal_amount) > 0
  if (isPetition(props.campaign))   return toNum(props.campaign.signature_goal) > 0
  if (isVolunteer(props.campaign))  return toNum(props.campaign.needed_slots) > 0
  if (isAwareness(props.campaign))  return toNum(props.campaign.target_reach) > 0
  return false
})

const progressPair = computed(() => {
  if (isFundraising(props.campaign)) return { value: toNum(props.campaign.raised_amount),    max: toNum(props.campaign.goal_amount) }
  if (isPetition(props.campaign))    return { value: toNum(props.campaign.signatures_count), max: toNum(props.campaign.signature_goal) }
  if (isVolunteer(props.campaign))   return { value: toNum(props.campaign.filled_slots),     max: toNum(props.campaign.needed_slots) }
  if (isAwareness(props.campaign))   return { value: toNum(props.campaign.current_reach),    max: toNum(props.campaign.target_reach) }
  return { value: 0, max: 0 }
})

const progressPct = computed(() => {
  const v = progressPair.value.value
  const m = progressPair.value.max
  if (m <= 0) return 0
  return Math.min(100, Math.round((v / m) * 100))
})

const metaLabel = computed(() => {
  if (isFundraising(props.campaign)) {
    const r = toNum(props.campaign.raised_amount)
    const g = toNum(props.campaign.goal_amount)
    const cur = props.campaign.goal_currency || ""
    return `${r.toLocaleString()} / ${g.toLocaleString()} ${cur}`.trim()
  }
  if (isPetition(props.campaign)) {
    const r = toNum(props.campaign.signatures_count)
    const g = toNum(props.campaign.signature_goal)
    return `${r.toLocaleString()} / ${g.toLocaleString()} signatures`
  }
  if (isVolunteer(props.campaign)) {
    const r = toNum(props.campaign.filled_slots)
    const g = toNum(props.campaign.needed_slots)
    return `${r.toLocaleString()} / ${g.toLocaleString()} volunteers`
  }
  if (isAwareness(props.campaign)) {
    const r = toNum(props.campaign.current_reach)
    const g = toNum(props.campaign.target_reach)
    return `${r.toLocaleString()} / ${g.toLocaleString()} reach`
  }
  return ""
})

const dateFmt = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" })
</script>

<template>
  <article class="rounded-2xl overflow-hidden ring-1 ring-neutral-200/70 dark:ring-neutral-800/70 bg-white dark:bg-neutral-900 flex flex-col">
    <div class="w-full aspect-[16/9] bg-neutral-100 dark:bg-neutral-800 overflow-hidden">
      <img
        v-if="campaign.cover_url"
        :src="campaign.cover_url"
        alt=""
        class="w-full h-full object-cover"
      >
      <Icon
        v-else
        icon="mdi:image-off-outline"
        class="w-10 h-10 text-neutral-400"
      />
    </div>

    <div class="p-4 flex-1 flex flex-col gap-3">
      <div class="flex items-center gap-2 text-xs">
        <span class="px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800 capitalize">{{ campaign.kind }}</span>
      </div>

      <h3 class="text-lg font-semibold leading-tight line-clamp-2">
        {{ campaign.title }}
      </h3>
      <p
        v-if="campaign.excerpt"
        class="text-sm text-neutral-600 dark:text-neutral-400 line-clamp-2"
        v-text="campaign.excerpt"
      />

      <div
        v-if="hasProgress"
        class="space-y-1"
      >
        <ProgressBar
          :value="progressPair.value"
          :max="progressPair.max"
        />
        <div class="flex items-center justify-between text-xs text-neutral-600 dark:text-neutral-400">
          <span>{{ metaLabel }}</span>
          <span>{{ progressPct }}%</span>
        </div>
      </div>

      <div class="flex items-center justify-between text-xs text-neutral-500 dark:text-neutral-400">
        <div class="inline-flex items-center gap-2">
          <img
            v-if="campaign.owner?.avatar"
            :src="campaign.owner.avatar"
            class="h-6 w-6 rounded-full object-cover"
          >
          <span>{{ campaign.owner?.name || 'Owner' }}</span>
        </div>
        <div class="inline-flex items-center gap-1">
          <Icon
            icon="mdi:calendar"
            class="w-4 h-4"
          />
          <span>{{ campaign.publish_at ? dateFmt.format(new Date(campaign.publish_at)) : '' }}</span>
        </div>
      </div>

      <CampaignActions
        :campaign="campaign"
        @share="isShareOpen = true"
        @saved="() => {}"
      />

      <router-link
        :to="{ name: 'campaigns.details', params: { slug: campaign.slug } }"
        class="mt-2 inline-flex items-center gap-2 text-sm font-medium text-emerald-600 dark:text-emerald-400 hover:underline"
      >
        <span>Details</span>
        <Icon
          icon="mdi:arrow-right"
          class="w-4 h-4"
        />
      </router-link>
    </div>

    <ShareModal
      :open="isShareOpen"
      shareable-type="App\\Models\\Campaign\\Campaign"
      shareable-alias="campaign"
      :shareable-id="Number(campaign.id)"
      :campaign="campaign"
      :title="campaign.title"
      :text="campaign.excerpt || campaign.title"
      @close="isShareOpen=false"
      @shared="isShareOpen=false"
    />
  </article>
</template>

<style scoped>
.line-clamp-2{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}
</style>
