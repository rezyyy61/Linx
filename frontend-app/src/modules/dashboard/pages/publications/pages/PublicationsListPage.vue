<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/dashboard/pages/publications/pages/PublicationsListPage.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { Icon } from "@iconify/vue"
import PageContainer from "@/modules/dashboard/pages/events/layout/PageContainer.vue"
import ShareModal from "@/modules/share/components/ShareModal.vue"
import PublicationListFilters from "@/modules/dashboard/pages/publications/components/PublicationListFilters.vue"
import type { Publication } from "../types"
import { deletePublication } from "../api"
import { api } from "@/lib/http"

const { t } = useI18n()

const items = ref<Publication[]>([])
const loading = ref(false)

const search = ref("")
const published = ref<string>("")
const orderBy = ref<"publish_at"|"created_at"|"updated_at">("publish_at")
const orderDir = ref<"asc"|"desc">("desc")
const perPage = ref(15)
const page = ref(1)

const meta = ref({ current_page: 1, per_page: 15, total: 0, last_page: 1 })
const lastPage = computed(() => meta.value.last_page || Math.ceil((meta.value.total || 0) / (meta.value.per_page || perPage.value)) || 1)

const df = new Intl.DateTimeFormat(undefined, { dateStyle: "medium", timeStyle: "short" })
function fmt(iso?: string | null) {
  if (!iso) return ""
  const d = new Date(iso)
  return Number.isNaN(d.getTime()) ? "" : df.format(d)
}
function coverThumb(p: Publication): string | undefined {
  const url = p.cover_url ?? undefined
  return typeof url === "string" && url.length ? url : undefined
}

async function fetchList() {
  loading.value = true
  try {
    const { data } = await api.get("/publications", {
      params: {
        page: page.value,
        per_page: perPage.value,
        q: search.value.trim() || undefined,
        published: published.value === "" ? undefined : published.value === "1",
        order_by: orderBy.value,
        order_dir: orderDir.value,
      },
    })
    items.value = (data.data as Publication[]) || []
    meta.value = data.meta || { current_page: 1, per_page: perPage.value, total: items.value.length, last_page: 1 }
  } finally {
    loading.value = false
  }
}

function reset() {
  search.value = ""
  published.value = ""
  orderBy.value = "publish_at"
  orderDir.value = "desc"
  perPage.value = 15
  page.value = 1
  fetchList()
}
function goPrev() { if (page.value > 1) { page.value -= 1; fetchList() } }
function goNext() { if (page.value < lastPage.value) { page.value += 1; fetchList() } }

async function destroyRow(id:number){
  if (!confirm(t("publication.confirm.delete") || "Delete this publication?")) return
  await deletePublication(id)
  if (items.value.length === 1 && page.value > 1) page.value -= 1
  await fetchList()
}

let deb: number | undefined
watch([published, orderBy, orderDir, perPage], () => { page.value = 1; fetchList() })
watch(search, () => { if (deb) window.clearTimeout(deb); deb = window.setTimeout(() => { page.value = 1; fetchList() }, 300) })

onMounted(fetchList)

const shareOpenId = ref<number | null>(null)
const shareTitle = ref<string>("")
const shareText = ref<string>("")
const sharePayload = ref<any | null>(null)
function stripHtml(x: string) {
  const d = new DOMParser().parseFromString(x || "", "text/html")
  return d.body.textContent || ""
}

function openShare(p: Publication) {
  const id = Number(p?.id ?? 0)
  if (!Number.isFinite(id) || id < 1) return
  shareOpenId.value = id
  shareTitle.value = p.title || ""
  shareText.value = stripHtml(p.description || "")
  sharePayload.value = p || null
}

function closeShare() {
  shareOpenId.value = null
  sharePayload.value = null
}

function statusKey(p: Publication): "draft" | "scheduled" | "published" {
  if (!p.is_published) return "draft"
  if (p.publish_at) {
    const d = new Date(p.publish_at)
    if (!Number.isNaN(d.getTime()) && d.getTime() > Date.now()) return "scheduled"
  }
  return "published"
}
function statusLabel(p: Publication): string {
  const k = statusKey(p)
  if (k === "draft") return (t("publication.status.draft") as string) || "Draft"
  if (k === "scheduled") return (t("publication.status.scheduled") as string) || "Scheduled"
  return (t("publication.status.published") as string) || "Published"
}
function statusIcon(p: Publication): string {
  const k = statusKey(p)
  if (k === "draft") return "mdi:file-document-edit-outline"
  if (k === "scheduled") return "mdi:calendar-clock"
  return "mdi:check-decagram"
}
function statusClass(p: Publication): string {
  const k = statusKey(p)
  if (k === "draft") return "bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700"
  if (k === "scheduled") return "bg-amber-50 text-amber-700 border border-amber-200 dark:bg-amber-950/30 dark:text-amber-300 dark:border-amber-900/40"
  return "bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900/40"
}
</script>

<template>
  <PageContainer>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <h1 class="text-2xl font-semibold text-red-500 my-4">
          {{ t("publication.list.title") || "Publications" }}
        </h1>
        <router-link
          :to="{ name:'dashboard.publications.create' }"
          class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        >
          <Icon
            icon="mdi:plus-circle"
            class="w-5 h-5"
          />
          <span>{{ t("publication.list.create") || "Create publication" }}</span>
        </router-link>
      </div>

      <PublicationListFilters
        v-model:search="search"
        v-model:published="published"
        v-model:order-by="orderBy"
        v-model:order-dir="orderDir"
        v-model:per-page="perPage"
        @reset="reset"
      />

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
                icon="mdi:newspaper-variant-outline"
                class="w-7 h-7 text-gray-500"
              />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              {{ t("publication.list.noResults") || "No publications found" }}
            </p>
          </div>

          <div v-else>
            <div class="hidden lg:block overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 w-16">
                      {{ t("publication.table.cover") || "Cover" }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("publication.table.title") || "Title" }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("publication.table.issue") || "Issue" }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("publication.table.status") || "Status" }}
                    </th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("publication.table.actions") || "Actions" }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="p in items"
                    :key="p.id"
                    class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition"
                  >
                    <td class="px-4 py-3">
                      <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <img
                          v-if="coverThumb(p)"
                          :src="coverThumb(p)!"
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
                        <span class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[420px]">{{ p.title }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">@{{ p.slug || "—" }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm">
                      {{ p.issue || "—" }}
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center gap-2">
                        <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium', statusClass(p)]">
                          <Icon
                            :icon="statusIcon(p)"
                            class="w-4 h-4"
                          />
                          {{ statusLabel(p) }}
                        </span>
                        <span
                          v-if="p.is_published && p.publish_at"
                          class="text-xs text-gray-600 dark:text-gray-400"
                        >
                          {{ fmt(p.publish_at) }}
                        </span>
                      </div>
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                          @click="openShare(p)"
                        >
                          <Icon
                            icon="mdi:share-variant"
                            class="w-4 h-4"
                          />
                        </button>
                        <router-link
                          :to="{ name:'dashboard.publications.edit', params:{ id:p.id } }"
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                          <Icon
                            icon="mdi:pencil"
                            class="w-4 h-4"
                          />
                          <span>{{ t("publication.actions.edit") || "Edit" }}</span>
                        </router-link>
                        <button
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-800/60 dark:hover:bg-red-900/20"
                          @click="destroyRow(p.id)"
                        >
                          <Icon
                            icon="mdi:trash-can"
                            class="w-4 h-4"
                          />
                          <span>{{ t("publication.actions.delete") || "Delete" }}</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="p in items"
                :key="p.id"
                class="p-4 flex items-start gap-3"
              >
                <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                  <img
                    v-if="coverThumb(p)"
                    :src="coverThumb(p)!"
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
                      {{ p.title }}
                    </div>
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                      @{{ p.slug || "—" }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-300 mt-1 flex items-center gap-2">
                    <span :class="['inline-flex items-center gap-1 px-2 py-0.5 rounded-full font-medium', statusClass(p)]">
                      <Icon
                        :icon="statusIcon(p)"
                        class="w-4 h-4"
                      />
                      {{ statusLabel(p) }}
                    </span>
                    <span v-if="p.is_published && p.publish_at">• {{ fmt(p.publish_at) }}</span>
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                      @click="openShare(p)"
                    >
                      <Icon
                        icon="mdi:share-variant"
                        class="w-4 h-4"
                      />
                    </button>
                    <router-link
                      :to="{ name:'dashboard.publications.edit', params:{ id:p.id } }"
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                    >
                      <Icon
                        icon="mdi:pencil"
                        class="w-4 h-4"
                      />
                      <span>{{ t("publication.actions.edit") || "Edit" }}</span>
                    </router-link>
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs text-red-600 border-red-300 dark:text-red-400 dark:border-red-800/60"
                      @click="destroyRow(p.id)"
                    >
                      <Icon
                        icon="mdi:trash-can"
                        class="w-4 h-4"
                      />
                      <span>{{ t("publication.actions.delete") || "Delete" }}</span>
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
                {{ (t('publication.list.pageInfo') || 'Page {page} of {last} • {total} items')
                  .replace('{page}', String(meta.current_page))
                  .replace('{last}', String(lastPage))
                  .replace('{total}', String(meta.total)) }}
              </div>
              <div class="flex items-center gap-2">
                <button
                  class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
                  :disabled="(meta.current_page || 1) <= 1"
                  @click="goPrev"
                >
                  <Icon
                    icon="mdi:chevron-left"
                    class="w-5 h-5"
                  />
                  <span>{{ t("publication.common.prev") || "Prev" }}</span>
                </button>
                <button
                  class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
                  :disabled="(meta.current_page || 1) >= lastPage"
                  @click="goNext"
                >
                  <span>{{ t("publication.common.next") || "Next" }}</span>
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
      shareable-type="App\\Models\\Publication\\Publication"
      shareable-alias="publication"
      :shareable-id="shareOpenId"
      :title="shareTitle"
      :text="shareText"
      :publication="sharePayload"
      @close="closeShare"
      @shared="closeShare"
    />
  </PageContainer>
</template>
