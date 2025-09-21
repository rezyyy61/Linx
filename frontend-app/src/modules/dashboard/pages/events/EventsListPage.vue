<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/dashboard/pages/events/EventsListPage.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { Icon } from "@iconify/vue";
import PageContainer from "@/modules/dashboard/pages/events/layout/PageContainer.vue";
import { useEventStore } from "@/stores/event";
import ShareModal from "@/modules/share/components/ShareModal.vue";

const store = useEventStore();

const q = ref<string>(store.filters.q || "");
const onlyPublished = ref<boolean>(!!store.filters.is_published);
const perPage = ref<number>(store.filters.per_page || 15);
const orderBy = ref<"starts_at" | "created_at" | "updated_at">(store.filters.order_by || "starts_at");
const orderDir = ref<"asc" | "desc">(store.filters.order_dir || "desc");

const loading = computed(() => store.loadingList);
const items = computed(() => store.items);
const meta = computed(() => store.meta);

const df = new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" });

function fmt(iso?: string | null) {
  if (!iso) return "";
  const d = new Date(iso);
  return Number.isNaN(d.getTime()) ? "" : df.format(d);
}

function apply(page = 1) {
  store.setFilters({
    q: q.value?.trim() || undefined,
    is_published: onlyPublished.value ? true : undefined,
    per_page: perPage.value,
    order_by: orderBy.value,
    order_dir: orderDir.value,
    page,
  });
  store.fetchList();
}

function reset() {
  q.value = "";
  onlyPublished.value = false;
  perPage.value = 15;
  orderBy.value = "starts_at";
  orderDir.value = "desc";
  apply(1);
}

function toggleOrder() {
  orderDir.value = orderDir.value === "asc" ? "desc" : "asc";
  apply(meta.value?.current_page || 1);
}

function goPage(p: number) {
  if (!meta.value) return;
  if (p < 1 || p > meta.value.last_page) return;
  apply(p);
}

function nextPage() {
  const p = meta.value?.current_page || 1;
  goPage(p + 1);
}

function prevPage() {
  const p = meta.value?.current_page || 1;
  goPage(p - 1);
}

async function remove(id: number) {
  const ok = window.confirm((window as any)?.$t ? ((window as any).$t("event.list.actions.confirmDelete") as string) : "Delete this event?");
  if (!ok) return;
  await store.destroy(id);
  if (!store.items.length && (store.meta?.current_page || 1) > 1) apply((store.meta?.current_page || 2) - 1);
}

let t: number | undefined;
watch([q, onlyPublished, perPage, orderBy, orderDir], () => {
  if (t) window.clearTimeout(t);
  t = window.setTimeout(() => apply(1), 300);
});

onMounted(() => {
  if (!items.value.length) apply(store.filters.page || 1);
});

const shareOpenId = ref<number | null>(null);
const shareOpenEvent = ref<any | null>(null);
const shareTitle = ref<string>("");
const shareText = ref<string>("");

function openShare(ev: any) {
  const id = Number(ev?.id ?? 0);
  if (!Number.isFinite(id) || id < 1) return;
  shareOpenId.value = id;
  shareOpenEvent.value = ev || null;
  shareTitle.value = ev.title || "";
  shareText.value = ev.description || "";
}


function closeShare() {
  shareOpenId.value = null;
  shareOpenEvent.value = null;
}
</script>

<template>
  <PageContainer>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-red-500 my-4">
          {{ $t("event.list.title") }}
        </h1>
        <router-link
          to="/dashboard/events/create"
          class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        >
          <Icon
            icon="mdi:plus-circle"
            class="w-5 h-5"
          />
          <span>{{ $t("event.list.actions.create") }}</span>
        </router-link>
      </div>

      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
          <div class="lg:col-span-5">
            <div class="relative">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <Icon
                  icon="mdi:magnify"
                  class="w-5 h-5"
                />
              </span>
              <input
                v-model="q"
                type="text"
                :placeholder="$t('event.list.searchPlaceholder')"
                class="w-full rounded-xl border border-gray-300 pl-10 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
              >
            </div>
          </div>

          <div class="lg:col-span-3 flex items-center gap-2">
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm border border-gray-300 dark:border-gray-700"
              :class="onlyPublished ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : ''"
              @click="onlyPublished = !onlyPublished"
            >
              <Icon
                :icon="onlyPublished ? 'mdi:toggle-switch' : 'mdi:toggle-switch-off-outline'"
                class="w-5 h-5"
              />
              <span>{{ $t('event.list.onlyPublished') }}</span>
            </button>
            <button
              type="button"
              class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm border border-gray-300 dark:border-gray-700"
              @click="reset"
            >
              <Icon
                icon="mdi:close"
                class="w-4 h-4"
              />
              <span>{{ $t('common.clear') }}</span>
            </button>
          </div>

          <div class="lg:col-span-2">
            <select
              v-model="orderBy"
              class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
            >
              <option value="starts_at">
                {{ $t("event.list.table.start") }}
              </option>
              <option value="created_at">
                Created
              </option>
              <option value="updated_at">
                Updated
              </option>
            </select>
          </div>

          <div class="lg:col-span-1">
            <button
              type="button"
              class="w-full inline-flex items-center justify-center gap-1 rounded-xl border px-3 py-2 text-sm dark:border-gray-700"
              @click="toggleOrder"
            >
              <Icon
                :icon="orderDir==='asc' ? 'mdi:arrow-up' : 'mdi:arrow-down'"
                class="w-4 h-4"
              />
              <span class="uppercase">{{ orderDir }}</span>
            </button>
          </div>

          <div class="lg:col-span-1">
            <select
              v-model.number="perPage"
              class="w-full rounded-xl border border-gray-300 p-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
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
              <option :value="100">
                100
              </option>
            </select>
          </div>
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
        <div
          v-if="loading"
          class="p-4 space-y-2"
        >
          <div class="animate-pulse h-10 bg-gray-100 dark:bg-gray-800 rounded" />
          <div class="animate-pulse h-10 bg-gray-100 dark:bg-gray-800 rounded" />
          <div class="animate-pulse h-10 bg-gray-100 dark:bg-gray-800 rounded" />
        </div>

        <template v-else>
          <div
            v-if="!items.length"
            class="p-12 text-center"
          >
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
              <Icon
                icon="mdi:calendar-blank"
                class="w-7 h-7 text-gray-500"
              />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              {{ $t("event.list.actions.noResults") }}
            </p>
          </div>

          <div v-else>
            <div class="hidden lg:block overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 w-16">
                      {{ $t("event.list.table.cover") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ $t("event.list.table.title") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ $t("event.list.table.start") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ $t("event.list.table.published") }}
                    </th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">
                      {{ $t("event.list.table.actions") }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="ev in items"
                    :key="ev.id"
                    class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition"
                  >
                    <td class="px-4 py-3">
                      <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <img
                          v-if="ev.cover_url"
                          :src="ev.cover_url"
                          class="h-full w-full object-cover"
                        >
                        <Icon
                          v-else
                          icon="mdi:image-off"
                          class="w-5 h-5 text-gray-400"
                        />
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex flex-col">
                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ ev.title }}</span>
                        <span
                          v-if="ev.location"
                          class="text-xs text-gray-500 dark:text-gray-400"
                        >{{ ev.location }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                      {{ fmt(ev.starts_at || ev.starts_at_local) }}
                    </td>
                    <td class="px-4 py-3">
                      <span
                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
                        :class="ev.is_published
                          ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                          : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'"
                      >
                        <Icon
                          :icon="ev.is_published ? 'mdi:check-circle' : 'mdi:clock-outline'"
                          class="w-4 h-4"
                        />
                        {{ ev.is_published ? $t("event.list.status.published") : $t("event.list.status.draft") }}
                      </span>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                          @click="openShare(ev)"
                        >
                          <Icon
                            icon="mdi:share-variant"
                            class="w-4 h-4"
                          />
                        </button>
                        <router-link
                          :to="`/dashboard/events/${ev.id}/edit`"
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                          <Icon
                            icon="mdi:pencil"
                            class="w-4 h-4"
                          />
                          <span>{{ $t("event.list.actions.edit") }}</span>
                        </router-link>
                        <button
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-800/60 dark:hover:bg-red-900/20"
                          @click="remove(ev.id)"
                        >
                          <Icon
                            icon="mdi:trash-can"
                            class="w-4 h-4"
                          />
                          <span>{{ $t("event.list.actions.delete") }}</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="ev in items"
                :key="ev.id"
                class="p-4 flex items-start gap-3"
              >
                <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                  <img
                    v-if="ev.cover_url"
                    :src="ev.cover_url"
                    class="h-full w-full object-cover"
                  >
                  <Icon
                    v-else
                    icon="mdi:image-off"
                    class="w-5 h-5 text-gray-400"
                  />
                </div>
                <div class="flex-1 min-w-0">
                  <div class="flex items-center justify-between gap-2">
                    <div class="truncate font-medium text-gray-900 dark:text-gray-100">
                      {{ ev.title }}
                    </div>
                    <span
                      class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium"
                      :class="ev.is_published
                        ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                        : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300'"
                    >
                      <Icon
                        :icon="ev.is_published ? 'mdi:check-circle' : 'mdi:clock-outline'"
                        class="w-3.5 h-3.5"
                      />
                      {{ ev.is_published ? $t("event.list.status.published") : $t("event.list.status.draft") }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                    {{ fmt(ev.starts_at || ev.starts_at_local) }}
                  </div>
                  <div
                    v-if="ev.location"
                    class="text-xs text-gray-500 dark:text-gray-400 mt-0.5"
                  >
                    {{ ev.location }}
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                      @click="openShare(ev)"
                    >
                      <Icon
                        icon="mdi:share-variant"
                        class="w-4 h-4"
                      />
                    </button>
                    <router-link
                      :to="`/dashboard/pages/events/${ev.id}/edit`"
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                    >
                      <Icon
                        icon="mdi:pencil"
                        class="w-4 h-4"
                      />
                      <span>{{ $t("event.list.actions.edit") }}</span>
                    </router-link>
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs text-red-600 border-red-300 dark:text-red-400 dark:border-red-800/60"
                      @click="remove(ev.id)"
                    >
                      <Icon
                        icon="mdi:trash-can"
                        class="w-4 h-4"
                      />
                      <span>{{ $t("event.list.actions.delete") }}</span>
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <div
              v-if="meta"
              class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800"
            >
              <div class="text-xs text-gray-600 dark:text-gray-400">
                {{ $t("event.list.pagination.label", { page: meta.current_page, pages: meta.last_page, total: meta.total }) }}
              </div>
              <div class="flex items-center gap-2">
                <button
                  class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
                  :disabled="(meta.current_page || 1) <= 1"
                  @click="prevPage"
                >
                  <Icon
                    icon="mdi:chevron-left"
                    class="w-5 h-5"
                  />
                  <span>{{ $t("event.list.actions.prev") }}</span>
                </button>
                <button
                  class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
                  :disabled="(meta.current_page || 1) >= (meta.last_page || 1)"
                  @click="nextPage"
                >
                  <span>{{ $t("event.list.actions.next") }}</span>
                  <Icon
                    icon="mdi:chevron-right"
                    class="w-5 h-5"
                  />
                </button>
              </div>
            </div>
          </div>
        </template>
      </div>
    </div>

    <ShareModal
      v-if="shareOpenId && shareOpenId > 0"
      :key="shareOpenId"
      :open="true"
      shareable-type="App\\Models\\Event\\Event"
      shareable-alias="event"
      :shareable-id="shareOpenId"
      :event="shareOpenEvent"
      :title="shareTitle"
      :text="shareText"
      @close="closeShare"
      @shared="closeShare"
    />
  </PageContainer>
</template>
