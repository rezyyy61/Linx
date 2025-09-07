<template>
  <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
        {{ t("campaign.list.title") }}
      </h1>
      <router-link
        :to="{ name:'campaigns.create' }"
        class="rounded-xl px-3 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700"
      >
        {{ t("campaign.list.create") }}
      </router-link>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <div class="flex-1 min-w-[240px]">
        <input
          v-model="search"
          type="text"
          :placeholder="t('campaign.list.searchPlaceholder')"
          class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        >
      </div>

      <button
        type="button"
        :class="onlyRunning ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
        class="px-3 py-1.5 rounded-lg text-xs"
        @click="toggleOnlyRunning"
      >
        {{ t('campaign.list.onlyRunning') }}
      </button>

      <button
        v-if="hasActiveFilters"
        type="button"
        class="px-3 py-1.5 rounded-lg text-xs bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
        @click="clearFilters"
      >
        {{ t('campaign.list.clear') }}
      </button>

      <select
        v-model="orderBy"
        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        style="min-width: 120px"
      >
        <option value="starts_at">
          {{ t('campaign.list.sort.starts') }}
        </option>
        <option value="created_at">
          {{ t('campaign.list.sort.created') }}
        </option>
        <option value="updated_at">
          {{ t('campaign.list.sort.updated') }}
        </option>
      </select>

      <select
        v-model="orderDir"
        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        style="min-width: 100px"
      >
        <option value="desc">
          ▼ DESC
        </option>
        <option value="asc">
          ▲ ASC
        </option>
      </select>

      <select
        v-model.number="perPage"
        class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 dark:bg-slate-900 dark:text-slate-100 dark:border-slate-700"
        style="min-width: 80px"
      >
        <option :value="10">
          10
        </option>
        <option :value="15">
          15
        </option>
        <option :value="25">
          25
        </option>
        <option :value="50">
          50
        </option>
      </select>
    </div>

    <div
      v-if="loading"
      class="text-slate-500 dark:text-slate-400"
    >
      {{ t('campaign.loading') }}
    </div>

    <div
      v-else-if="items.length === 0"
      class="rounded-2xl border border-dashed border-slate-300 p-10 text-center dark:border-slate-700"
    >
      <p class="text-slate-700 dark:text-slate-200 font-medium">
        {{ t('campaign.empty.noCampaigns') }}
      </p>
      <router-link
        :to="{ name:'campaigns.create' }"
        class="mt-3 inline-block rounded-xl px-3 py-2 text-sm bg-primary-600 text-white hover:bg-primary-700"
      >
        {{ t('campaign.empty.createFirst') }}
      </router-link>
    </div>

    <div
      v-else
      class="overflow-x-auto rounded-2xl border border-slate-200 dark:border-slate-700"
    >
      <table class="min-w-full divide-y divide-slate-200 dark:divide-slate-700">
        <thead class="bg-slate-50 dark:bg-slate-800/50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.cover') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.title') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.start') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.status') }}
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.donation') }}
            </th>
            <th class="px-4 py-3 text-right text-xs font-medium text-slate-500 dark:text-slate-400 uppercase">
              {{ t('campaign.table.actions') }}
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-200 dark:divide-slate-700 bg-white dark:bg-slate-900">
          <tr
            v-for="c in items"
            :key="c.id"
          >
            <td class="px-4 py-3">
              <div class="h-9 w-9 rounded bg-slate-200 dark:bg-slate-800 overflow-hidden flex items-center justify-center">
                <img
                  v-if="coverThumb(c)"
                  :src="coverThumb(c)"
                  alt=""
                  class="h-full w-full object-cover"
                >
                <Icon
                  v-else
                  icon="mdi:image-off"
                  class="w-5 h-5 text-slate-500"
                />
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-900 dark:text-slate-100">
              <div class="font-medium truncate max-w-[280px]">
                {{ c.title }}
              </div>
              <div class="text-xs text-slate-500 dark:text-slate-400">
                @{{ c.slug || '—' }}
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              {{ fmtDateTime(c.starts_at) || '—' }}
            </td>
            <td class="px-4 py-3 text-sm">
              <span
                class="px-2 py-0.5 text-xs rounded-full border"
                :class="statusClass(c.status)"
              >
                {{ t('campaign.status.' + c.status) }}
              </span>
            </td>
            <td class="px-4 py-3 text-sm text-slate-700 dark:text-slate-300">
              {{ c.donation_enabled ? t('campaign.table.enabled') : t('campaign.table.disabled') }}
            </td>
            <td class="px-4 py-3 text-sm">
              <div class="flex justify-end gap-2">
                <router-link
                  :to="{ name:'campaigns.edit', params:{ id:c.id } }"
                  class="rounded-xl px-2.5 py-1.5 text-xs border border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-700"
                >
                  {{ t('campaign.actions.view') }}
                </router-link>
                <router-link
                  :to="{ name:'campaigns.edit', params:{ id:c.id } }"
                  class="rounded-xl px-2.5 py-1.5 text-xs border border-slate-200 bg-white dark:bg-slate-900 dark:border-slate-700"
                >
                  {{ t('campaign.actions.edit') }}
                </router-link>
                <button
                  class="rounded-xl px-2.5 py-1.5 text-xs bg-red-600 text-white hover:bg-red-700"
                  @click="destroy(c.id)"
                >
                  {{ t('campaign.actions.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="flex items-center justify-between px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
        <div>
          {{ t('campaign.list.pageInfo', { page: meta.current_page, last: lastPage, total: meta.total }) }}
        </div>
        <div class="flex items-center gap-2">
          <button
            class="rounded-lg border px-2.5 py-1 dark:border-slate-700 disabled:opacity-50"
            :disabled="meta.current_page <= 1"
            @click="goPrev"
          >
            {{ t('common.prev') }}
          </button>
          <button
            class="rounded-lg border px-2.5 py-1 dark:border-slate-700 disabled:opacity-50"
            :disabled="meta.current_page >= lastPage"
            @click="goNext"
          >
            {{ t('common.next') }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, watch, computed } from "vue";
import { useI18n } from "vue-i18n";
import { Icon } from "@iconify/vue";
import type { Campaign } from "@/modules/dashboard/pages/campaigns/types";
import { listCampaigns, deleteCampaign } from "@/modules/dashboard/pages/campaigns/api";

const { t } = useI18n();

const items = ref<Campaign[]>([]);
const loading = ref(false);

const search = ref("");
const onlyRunning = ref(false);
const orderBy = ref<"starts_at" | "created_at" | "updated_at">("starts_at");
const orderDir = ref<"asc" | "desc">("desc");
const perPage = ref(15);
const page = ref(1);

const meta = ref({ current_page: 1, per_page: 15, total: 0, last_page: 1 });
const lastPage = computed(() => meta.value.last_page || Math.ceil((meta.value.total || 0) / (meta.value.per_page || perPage.value)) || 1);
const hasActiveFilters = computed(() => !!search.value.trim() || onlyRunning.value || orderBy.value !== "starts_at" || orderDir.value !== "desc" || perPage.value !== 15);

let searchDebounce: number | undefined;

async function fetchList() {
  loading.value = true;
  try {
    await listCampaigns(page.value);
    const { data } = await (await import("@/lib/http")).api.get("/campaigns", {
      params: {
        page: page.value,
        per_page: perPage.value,
        q: (search.value.trim() || undefined),
        status: (onlyRunning.value ? "running" : undefined),
        order_by: orderBy.value,
        order_dir: orderDir.value
      }
    });
    items.value = data.data as Campaign[];
    meta.value = data.meta || { current_page: 1, per_page: perPage.value, total: items.value.length, last_page: 1 };
  } finally {
    loading.value = false;
  }
}

function coverThumb(c: Campaign): string | undefined {
  const url = (c as any).cover_url ?? (c as any).covers?.[0]?.url;
  return (typeof url === "string" && url.length) ? url : undefined;
}

function fmtDateTime(iso: string | null): string | undefined {
  if (!iso) return undefined;
  try {
    const d = new Date(iso);
    return d.toLocaleString();
  } catch {
    return undefined;
  }
}

function statusClass(s: string) {
  const base = "text-xs";
  if (s === "running") return base + " bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800";
  if (s === "paused") return base + " bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800";
  if (s === "ended") return base + " bg-slate-100 text-slate-700 border border-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-700";
  return base + " bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800";
}

function toggleOnlyRunning() {
  onlyRunning.value = !onlyRunning.value;
  page.value = 1;
  fetchList();
}

function clearFilters() {
  search.value = "";
  onlyRunning.value = false;
  orderBy.value = "starts_at";
  orderDir.value = "desc";
  perPage.value = 15;
  page.value = 1;
  fetchList();
}

async function destroy(id: number) {
  if (!confirm(t("campaign.confirm.delete"))) return;
  await deleteCampaign(id);
  if (items.value.length === 1 && page.value > 1) page.value -= 1;
  await fetchList();
}

function goPrev() {
  if (page.value <= 1) return;
  page.value -= 1;
  fetchList();
}
function goNext() {
  if (page.value >= lastPage.value) return;
  page.value += 1;
  fetchList();
}

watch(perPage, () => { page.value = 1; fetchList(); });
watch([orderBy, orderDir], () => { page.value = 1; fetchList(); });
watch(search, () => {
  window.clearTimeout(searchDebounce);
  searchDebounce = window.setTimeout(() => {
    page.value = 1;
    fetchList();
  }, 400);
});

onMounted(fetchList);
</script>
