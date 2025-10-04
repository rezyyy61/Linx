<!-- /src/modules/public/tabs/campaigns/pages/CampaignDetailsPage.vue -->
<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue"
import { Icon } from "@iconify/vue"
import { useRoute, useRouter } from "vue-router"

import { useCampaignDetails } from "../composables/useCampaignDetails"
import { useRecommendations } from "../composables/useRecommendations"
import { fetchCampaign } from "../api/campaigns"

import FundraisingDetails from "../components/details/FundraisingDetails.vue"
import PetitionDetails from "../components/details/PetitionDetails.vue"
import VolunteerDetails from "../components/details/VolunteerDetails.vue"
import AwarenessDetails from "../components/details/AwarenessDetails.vue"
import CampaignActions from "../components/CampaignActions.vue"
import RecommendedStrip from "../components/recommendations/RecommendedStrip.vue"
import CampaignSupport from "../components/CampaignSupport.vue"

import { ShareModal } from "@/modules/share"

const route = useRoute()
const router = useRouter()

/** Route slug */
const slug = computed(() => String(route.params.slug || ""))

/** Data */
const { item, loading, notFound, fetchOne } = useCampaignDetails(slug.value)

/** Local hard reload (when slug changes) */
const hardReloading = ref(false)
async function loadBySlug(s: string) {
  hardReloading.value = true
  notFound.value = false
  try {
    const data = await fetchCampaign(s)
    if (!data) {
      notFound.value = true
      item.value = null as any
    } else {
      item.value = data as any
    }
  } finally {
    hardReloading.value = false
  }
}

/** Initial load */
onMounted(async () => {
  if (!item.value) await fetchOne()
})

/** React to slug change */
watch(slug, async (s, p) => {
  if (s === p) return
  await loadBySlug(s)
  window.scrollTo({ top: 0, behavior: "auto" })
})

/** Nav */
function goBack() {
  router.push({ name: "home", query: { tab: "campaigns" } })
}

/** Share (opens our ShareModal with repost preview support) */
const isShareOpen = ref(false)

/** UI helpers */
const dateFmt = new Intl.DateTimeFormat(undefined, { dateStyle: "medium" })
const cover = computed(() => item.value?.cover_url || "")
const title = computed(() => item.value?.title || "")
const vis = computed(() => item.value?.visibility || "public")
const kind = computed(() => item.value?.kind || "")
const status = computed(() => item.value?.status || "")
const ownerName = computed(() => item.value?.owner?.name || "Owner")
const ownerAvatar = computed(() => item.value?.owner?.avatar || "")
const publishAt = computed(() => (item.value?.publish_at ? dateFmt.format(new Date(item.value.publish_at)) : ""))

/** Recommendations */
const { items: recs, loading: recLoading } = useRecommendations(() => item.value || null, 6)

/** When support component updates the campaign */
function onSupportUpdated(v: any) {
  item.value = v
}
</script>

<template>
  <section class="max-w-6xl mx-auto px-4 py-6 space-y-6">
    <!-- Top bar -->
    <div class="flex items-center justify-between">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
        @click="goBack"
      >
        <Icon
          icon="mdi:arrow-left"
          class="w-4 h-4"
        />
        <span>Back to campaigns</span>
      </button>

      <button
        v-if="item"
        type="button"
        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm hover:bg-gray-50 dark:hover:bg-zinc-800 dark:border-zinc-700"
        @click="isShareOpen = true"
      >
        <Icon
          icon="mdi:share-variant"
          class="w-4 h-4"
        />
        <span>Share</span>
      </button>
    </div>

    <!-- Loading -->
    <div
      v-if="loading || hardReloading"
      class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-8"
    >
      <div class="animate-pulse space-y-4">
        <div class="h-8 w-2/3 bg-neutral-200 dark:bg-neutral-800 rounded" />
        <div class="aspect-[16/9] w-full bg-neutral-200 dark:bg-neutral-800 rounded-2xl" />
        <div class="h-4 w-3/4 bg-neutral-200 dark:bg-neutral-800 rounded" />
      </div>
    </div>

    <!-- Not found -->
    <div
      v-else-if="notFound"
      class="rounded-2xl border border-red-200 bg-red-50 p-8 dark:border-red-900/40 dark:bg-red-950/40"
    >
      <div class="flex items-center gap-2 text-red-700 dark:text-red-300">
        <Icon
          icon="mdi:alert-circle-outline"
          class="w-5 h-5"
        />
        <span>Campaign not found</span>
      </div>
    </div>

    <!-- Content -->
    <div
      v-else-if="item"
      class="grid grid-cols-1 lg:grid-cols-3 gap-6"
    >
      <!-- Left: main -->
      <div class="lg:col-span-2 rounded-2xl overflow-hidden ring-1 ring-neutral-200/70 dark:ring-neutral-800/70 bg-white dark:bg-neutral-900">
        <div class="relative w-full overflow-hidden rounded-2xl bg-neutral-100 dark:bg-neutral-800 p-2 aspect-[4/3] sm:aspect-[16/9]">
          <img
            v-if="cover"
            :src="cover"
            alt=""
            class="w-full h-full object-contain block"
            loading="lazy"
            decoding="async"
          >
          <Icon
            v-else
            icon="mdi:image-off-outline"
            class="absolute inset-0 m-auto w-10 h-10 text-neutral-400"
          />
        </div>

        <div class="p-6 space-y-5">
          <!-- Badges -->
          <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="px-2 py-0.5 rounded-full bg-black/70 text-white capitalize">{{ vis }}</span>
            <span class="px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800 capitalize">{{ kind }}</span>
            <span
              class="px-2 py-0.5 rounded-full"
              :class="status==='published' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-neutral-100 dark:bg-neutral-800'"
            >
              {{ status }}
            </span>
            <span class="text-neutral-400">•</span>
            <span class="inline-flex items-center gap-1">
              <Icon
                icon="mdi:calendar"
                class="w-4 h-4"
              />
              {{ publishAt }}
            </span>
          </div>

          <!-- Title -->
          <h1 class="text-2xl md:text-3xl font-extrabold leading-snug tracking-tight text-neutral-900 dark:text-neutral-100">
            {{ title }}
          </h1>

          <!-- Owner + actions -->
          <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
              <img
                v-if="ownerAvatar"
                :src="ownerAvatar"
                class="h-10 w-10 rounded-full object-cover"
              >
              <div class="min-w-0">
                <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                  {{ ownerName }}
                </div>
                <div class="text-xs text-neutral-500 dark:text-neutral-400">
                  Organizer
                </div>
              </div>
            </div>

            <CampaignActions
              :campaign="item"
              @share="isShareOpen = true"
              @saved="() => {}"
            />
          </div>

          <!-- Description -->
          <!-- eslint-disable vue/no-v-html -->
          <div
            v-if="item.description"
            class="prose prose-sm dark:prose-invert max-w-none"
            v-html="item.description"
          />
        </div>
      </div>

      <!-- Right: support + details -->
      <div class="lg:col-span-1">
        <div class="rounded-2xl ring-1 ring-neutral-200/70 dark:ring-neutral-800/70 bg-white dark:bg-neutral-900 p-5 space-y-5">
          <div class="flex items-center gap-3">
            <img
              v-if="ownerAvatar"
              :src="ownerAvatar"
              class="h-10 w-10 rounded-full object-cover"
            >
            <div class="min-w-0">
              <div class="text-sm font-medium text-neutral-900 dark:text-neutral-100 truncate">
                {{ ownerName }}
              </div>
              <div class="text-xs text-neutral-500 dark:text-neutral-400">
                Organizer
              </div>
            </div>
          </div>

          <CampaignSupport
            v-if="item"
            :campaign="item"
            @updated="onSupportUpdated"
          />

          <FundraisingDetails
            v-if="item && item.kind === 'fundraising'"
            :campaign="item"
          />
          <PetitionDetails
            v-else-if="item && item.kind === 'petition'"
            :campaign="item"
          />
          <VolunteerDetails
            v-else-if="item && item.kind === 'volunteer'"
            :campaign="item"
          />
          <AwarenessDetails
            v-else-if="item && item.kind === 'awareness'"
            :campaign="item"
          />
        </div>
      </div>
    </div>

    <!-- Recommendations -->
    <RecommendedStrip
      :key="slug"
      :items="recs"
      :loading="recLoading"
    />

    <!-- Share Modal (with Campaign preview for Repost) -->
    <ShareModal
      v-if="item"
      :open="isShareOpen"
      shareable-type="App\\Models\\Campaign\\Campaign"
      shareable-alias="campaign"
      :shareable-id="Number(item?.id || 0)"
      :campaign="item"
      :title="item?.title || 'Campaign'"
      :text="item?.excerpt || item?.title || 'Campaign'"
      @close="isShareOpen = false"
      @shared="isShareOpen = false"
    />
  </section>
</template>
