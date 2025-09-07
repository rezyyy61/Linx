<template>
  <div class="p-4 md:p-6 space-y-4">
    <div class="flex items-center justify-between">
      <h1 class="text-lg font-semibold">
        {{ t("announcement.list.title") }}
      </h1>
      <router-link
        :to="{ name:'announcements.create' }"
        class="rounded-xl px-3 py-2 text-sm bg-primary-600 text-white"
      >
        {{ t("announcement.list.create") }}
      </router-link>
    </div>

    <div class="flex flex-wrap items-center gap-2">
      <input
        v-model="search"
        type="text"
        class="rounded-xl border px-3 py-2"
        :placeholder="t('announcement.list.searchPlaceholder')"
      >
      <select
        v-model="visibility"
        class="rounded-xl border px-3 py-2"
      >
        <option value="">
          {{ t('announcement.visibility.all') }}
        </option>
        <option value="public">
          {{ t('announcement.visibility.public') }}
        </option>
        <option value="members">
          {{ t('announcement.visibility.members') }}
        </option>
        <option value="supporters">
          {{ t('announcement.visibility.supporters') }}
        </option>
        <option value="private">
          {{ t('announcement.visibility.private') }}
        </option>
      </select>
      <label class="inline-flex items-center gap-2 text-sm">
        <input
          v-model="pinnedOnly"
          type="checkbox"
        >
        <span>{{ t('announcement.list.onlyPinned') }}</span>
      </label>
      <select
        v-model="orderBy"
        class="rounded-xl border px-3 py-2"
      >
        <option value="publish_at">
          {{ t('announcement.list.sort.publish') }}
        </option>
        <option value="created_at">
          {{ t('announcement.list.sort.created') }}
        </option>
        <option value="updated_at">
          {{ t('announcement.list.sort.updated') }}
        </option>
        <option value="is_pinned">
          {{ t('announcement.list.sort.pinned') }}
        </option>
      </select>
      <select
        v-model="orderDir"
        class="rounded-xl border px-3 py-2"
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
        class="rounded-xl border px-3 py-2"
      >
        <option :value="10">
          10
        </option><option :value="15">
          15
        </option><option :value="25">
          25
        </option><option :value="50">
          50
        </option>
      </select>
    </div>

    <div v-if="loading">
      {{ t('announcement.loading') }}
    </div>

    <div
      v-else-if="items.length === 0"
      class="rounded-2xl border border-dashed p-10 text-center"
    >
      <p class="font-medium">
        {{ t('announcement.empty.noItems') }}
      </p>
      <router-link
        :to="{ name:'announcements.create' }"
        class="mt-3 inline-block rounded-xl px-3 py-2 text-sm bg-primary-600 text-white"
      >
        {{ t('announcement.empty.createFirst') }}
      </router-link>
    </div>

    <div
      v-else
      class="overflow-x-auto rounded-2xl border"
    >
      <table class="min-w-full divide-y">
        <thead>
          <tr class="text-left text-xs uppercase">
            <th class="px-4 py-3">
              {{ t('announcement.table.cover') }}
            </th>
            <th class="px-4 py-3">
              {{ t('announcement.table.title') }}
            </th>
            <th class="px-4 py-3">
              {{ t('announcement.table.visibility') }}
            </th>
            <th class="px-4 py-3">
              {{ t('announcement.table.publishAt') }}
            </th>
            <th class="px-4 py-3 text-right">
              {{ t('announcement.table.actions') }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="a in items"
            :key="a.id"
            class="border-t"
          >
            <td class="px-4 py-3">
              <div class="h-9 w-9 rounded bg-slate-200 overflow-hidden flex items-center justify-center">
                <img
                  v-if="coverThumb(a)"
                  :src="coverThumb(a)!"
                  alt=""
                  class="h-full w-full object-cover"
                >
                <span
                  v-else
                  class="text-xs text-slate-500"
                >—</span>
              </div>
            </td>
            <td class="px-4 py-3">
              <div class="font-medium truncate max-w-[280px]">
                {{ a.title }}
              </div>
              <div class="text-xs text-slate-500">
                @{{ a.slug || '—' }}
              </div>
            </td>
            <td class="px-4 py-3 text-sm">
              {{ t('announcement.visibility.' + a.visibility) }}
            </td>
            <td class="px-4 py-3 text-sm">
              {{ fmtDateTime(a.publish_at) || '—' }}
            </td>
            <td class="px-4 py-3">
              <div class="flex justify-end gap-2">
                <router-link
                  :to="{ name:'announcements.edit', params:{ id:a.id } }"
                  class="rounded-xl px-2.5 py-1.5 text-xs border"
                >
                  {{ t('announcement.actions.edit') }}
                </router-link>
                <button
                  class="rounded-xl px-2.5 py-1.5 text-xs bg-red-600 text-white"
                  @click="destroy(a.id)"
                >
                  {{ t('announcement.actions.delete') }}
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <div class="flex items-center justify-between px-4 py-3 text-xs">
        <div>{{ t('announcement.list.pageInfo', { page: meta.current_page, last: lastPage, total: meta.total }) }}</div>
        <div class="flex items-center gap-2">
          <button
            class="rounded-lg border px-2.5 py-1 disabled:opacity-50"
            :disabled="meta.current_page <= 1"
            @click="goPrev"
          >
            {{ t('common.prev') }}
          </button>
          <button
            class="rounded-lg border px-2.5 py-1 disabled:opacity-50"
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
import { ref, computed, watch, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import type { Announcement } from "../types";
import { deleteAnnouncement } from "../api";
import { api } from "@/lib/http";

const { t } = useI18n();
const items = ref<Announcement[]>([]);
const loading = ref(false);

const search = ref("");
const visibility = ref<string>("");
const pinnedOnly = ref(false);
const orderBy = ref<"publish_at"|"created_at"|"updated_at"|"is_pinned">("publish_at");
const orderDir = ref<"asc"|"desc">("desc");
const perPage = ref(15);
const page = ref(1);

const meta = ref({ current_page: 1, per_page: 15, total: 0, last_page: 1 });
const lastPage = computed(() => meta.value.last_page || Math.ceil((meta.value.total || 0) / (meta.value.per_page || perPage.value)) || 1);

function coverThumb(a: Announcement): string | undefined {
  const url = a.cover_url ?? a.covers?.[0]?.url ?? undefined;
  return typeof url === "string" && url.length ? url : undefined;
}
function fmtDateTime(iso: string | null): string | undefined {
  if (!iso) return undefined;
  try { return new Date(iso).toLocaleString(); } catch { return undefined; }
}

async function fetchList() {
  loading.value = true;
  try {
    const { data } = await api.get("/announcements", {
      params: {
        page: page.value,
        per_page: perPage.value,
        q: (search.value.trim() || undefined),
        visibility: (visibility.value || undefined),
        pinned: (pinnedOnly.value ? true : undefined),
        order_by: orderBy.value,
        order_dir: orderDir.value
      }
    });
    items.value = (data.data as Announcement[]) || [];
    meta.value = data.meta || { current_page: 1, per_page: perPage.value, total: items.value.length, last_page: 1 };
  } finally {
    loading.value = false;
  }
}

async function destroy(id:number){
  if (!confirm(t("announcement.confirm.delete"))) return;
  await deleteAnnouncement(id);
  if (items.value.length === 1 && page.value > 1) page.value -= 1;
  await fetchList();
}

function goPrev(){ if (page.value > 1) { page.value -= 1; fetchList(); } }
function goNext(){ if (page.value < lastPage.value) { page.value += 1; fetchList(); } }

watch([visibility, orderBy, orderDir, perPage], () => { page.value = 1; fetchList(); });
watch(search, () => { window.clearTimeout((window as any)._aDeb); (window as any)._aDeb = window.setTimeout(() => { page.value = 1; fetchList(); }, 350); });
watch(pinnedOnly, () => { page.value = 1; fetchList(); });

onMounted(fetchList);
</script>
