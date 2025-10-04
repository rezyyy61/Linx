<script setup lang="ts">
import { onMounted } from "vue"
import { Icon } from "@iconify/vue"
import CampaignCard from "../components/CampaignCard.vue"
import { useCampaigns } from "../composables/useCampaigns"
import CampaignsFilterHeader from "../components/CampaignsFilterHeader.vue"
import intersect from "@/directives/intersect"

const { items, meta, q, loading, loadingMore, fetchList, fetchMore, reset, hasMore } =
  useCampaigns({ status: "published", per_page: 12, order_by: "publish_at", order_dir: "desc" })

function applyFilters() { q.value.page = 1; fetchList(true) }
function clearFilters() { reset() }

function onSentinel(entry: IntersectionObserverEntry) {
  if (entry.isIntersecting) fetchMore()
}

const vIntersect = intersect

onMounted(fetchList)
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
      <h1 class="text-2xl font-semibold text-emerald-600">
        Campaigns
      </h1>
      <div class="text-sm text-neutral-500">
        Total: {{ meta.total }}
      </div>
    </div>

    <CampaignsFilterHeader
      v-model="q"
      :loading="loading"
      @apply="applyFilters"
      @clear="clearFilters"
    />

    <div class="min-h-[200px]">
      <div
        v-if="loading && !items.length"
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4"
      >
        <div
          v-for="i in 6"
          :key="i"
          class="h-64 rounded-2xl bg-neutral-100 dark:bg-neutral-800 animate-pulse"
        />
      </div>

      <div
        v-else-if="!items.length"
        class="p-12 text-center"
      >
        <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-neutral-100 dark:bg-neutral-800">
          <Icon
            icon="mdi:bullhorn"
            class="w-7 h-7 text-neutral-500"
          />
        </div>
        <p class="text-sm text-neutral-600 dark:text-neutral-300">
          No campaigns found
        </p>
      </div>

      <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 items-stretch"
      >
        <CampaignCard
          v-for="c in items"
          :key="c.id"
          :campaign="c"
        />
      </div>

      <div
        v-if="items.length"
        class="mt-4 flex items-center justify-center"
      >
        <div
          v-if="loadingMore"
          class="inline-flex items-center gap-2 text-sm text-neutral-500 dark:text-neutral-400"
        >
          <svg
            class="animate-spin h-4 w-4"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
              fill="none"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
          </svg>
          Loading…
        </div>
        <div
          v-else-if="hasMore"
          v-intersect="onSentinel"
          class="h-8"
        />
        <div
          v-else
          class="text-xs text-neutral-400 dark:text-neutral-600"
        >
          No more results
        </div>
      </div>
    </div>
  </section>
</template>
