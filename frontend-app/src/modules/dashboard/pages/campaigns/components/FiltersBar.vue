<!-- /src/modules/dashboard/pages/campaigns/components/FiltersBar.vue -->
<script setup lang="ts">
import { computed } from "vue"
import type { CampaignQuery, CampaignKind, CampaignStatus, CampaignVisibility } from "../api/types"

const props = defineProps<{
  modelValue: CampaignQuery
  loading?: boolean
}>()

const emit = defineEmits<{
  (e: "update:modelValue", v: CampaignQuery): void
  (e: "reset"): void
  (e: "toggleOrder"): void
}>()

const KINDS = ["", "fundraising", "petition", "volunteer", "awareness"] as const
const STATUSES = ["", "draft", "published", "paused", "completed", "failed", "archived"] as const
const VISIBILITIES = ["", "public", "members", "private"] as const

const ORDER_BY_OPTIONS = ["publish_at", "created_at", "updated_at", "title", "status"] as const
type OrderBy = typeof ORDER_BY_OPTIONS[number]
const ORDER_BY_LABEL: Record<OrderBy, string> = {
  publish_at: "Publish date",
  created_at: "Created date",
  updated_at: "Updated date",
  title: "Title",
  status: "Status",
}

function update<K extends keyof CampaignQuery>(k: K, v: CampaignQuery[K]) {
  const next: CampaignQuery = { ...props.modelValue }
  if (v === "" || v === null || v === undefined) delete next[k]
  else next[k] = v
  next.page = 1
  emit("update:modelValue", next)
}

const qModel = computed({
  get: () => props.modelValue.q ?? "",
  set: v => update("q", v as any),
})
const kindModel = computed({
  get: () => (props.modelValue.kind ?? "") as CampaignKind | "",
  set: v => update("kind", v as any),
})
const visibilityModel = computed({
  get: () => (props.modelValue.visibility ?? "") as CampaignVisibility | "",
  set: v => update("visibility", v as any),
})
const statusModel = computed({
  get: () => (props.modelValue.status ?? "") as CampaignStatus | "",
  set: v => update("status", v as any),
})
const orderByModel = computed({
  get: () => (props.modelValue.order_by ?? "publish_at") as OrderBy,
  set: v => update("order_by", v as any),
})
</script>

<template>
  <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex items-center gap-2 flex-1">
      <input
        v-model="qModel"
        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
        type="text"
        placeholder="Search campaigns"
        :disabled="loading"
      >

      <select
        v-model="kindModel"
        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 capitalize"
        :disabled="loading"
      >
        <option :value="KINDS[0]">
          All kinds
        </option>
        <option
          v-for="k in KINDS.slice(1)"
          :key="k"
          :value="k"
          class="capitalize"
        >
          {{ k }}
        </option>
      </select>

      <select
        v-model="visibilityModel"
        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 capitalize"
        :disabled="loading"
      >
        <option :value="VISIBILITIES[0]">
          All visibility
        </option>
        <option
          v-for="v in VISIBILITIES.slice(1)"
          :key="v"
          :value="v"
          class="capitalize"
        >
          {{ v }}
        </option>
      </select>

      <select
        v-model="statusModel"
        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100 capitalize"
        :disabled="loading"
      >
        <option :value="STATUSES[0]">
          All status
        </option>
        <option
          v-for="s in STATUSES.slice(1)"
          :key="s"
          :value="s"
          class="capitalize"
        >
          {{ s }}
        </option>
      </select>
    </div>

    <div class="flex items-center gap-2">
      <select
        v-model="orderByModel"
        class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-emerald-600 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100"
        :disabled="loading"
      >
        <option
          v-for="ob in ORDER_BY_OPTIONS"
          :key="ob"
          :value="ob"
        >
          {{ ORDER_BY_LABEL[ob] }}
        </option>
      </select>

      <button
        class="rounded-xl border px-3 py-2 text-sm dark:border-zinc-700"
        :disabled="loading"
        @click="$emit('toggleOrder')"
      >
        <span class="uppercase">{{ (modelValue.order_dir ?? 'desc') }}</span>
      </button>

      <button
        class="rounded-xl border px-3 py-2 text-sm dark:border-zinc-700"
        :disabled="loading"
        @click="$emit('reset')"
      >
        Reset
      </button>
    </div>
  </div>
</template>
