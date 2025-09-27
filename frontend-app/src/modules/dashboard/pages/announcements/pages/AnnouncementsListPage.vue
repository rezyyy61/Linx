<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/dashboard/pages/announcements/AnnouncementsListPage.vue -->
<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { Icon } from "@iconify/vue"
import PageContainer from "@/modules/dashboard/pages/events/layout/PageContainer.vue"
import ShareModal from "@/modules/share/components/ShareModal.vue"
import type { Announcement } from "../types"
import { deleteAnnouncement } from "../api"
import { api } from "@/lib/http"

const { t } = useI18n()

const items = ref<Announcement[]>([])
const loading = ref(false)

const search = ref("")
const visibility = ref<string>("")
const pinnedOnly = ref(false)
const orderBy = ref<"publish_at"|"created_at"|"updated_at"|"is_pinned">("publish_at")
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
function coverThumb(a: Announcement): string | undefined {
  const url = a.cover_url ?? a.covers?.[0]?.url ?? undefined
  return typeof url === "string" && url.length ? url : undefined
}

async function fetchList() {
  loading.value = true
  try {
    const { data } = await api.get("/announcements", {
      params: {
        page: page.value,
        per_page: perPage.value,
        q: search.value.trim() || undefined,
        visibility: visibility.value || undefined,
        pinned: pinnedOnly.value ? true : undefined,
        order_by: orderBy.value,
        order_dir: orderDir.value,
      },
    })
    items.value = (data.data as Announcement[]) || []
    meta.value = data.meta || { current_page: 1, per_page: perPage.value, total: items.value.length, last_page: 1 }
  } finally {
    loading.value = false
  }
}

function reset() {
  search.value = ""
  visibility.value = ""
  pinnedOnly.value = false
  orderBy.value = "publish_at"
  orderDir.value = "desc"
  perPage.value = 15
  page.value = 1
  fetchList()
}

function toggleOrder() {
  orderDir.value = orderDir.value === "asc" ? "desc" : "asc"
  fetchList()
}
function goPrev() { if (page.value > 1) { page.value -= 1; fetchList() } }
function goNext() { if (page.value < lastPage.value) { page.value += 1; fetchList() } }

async function destroyRow(id:number){
  if (!confirm(t("announcement.confirm.delete"))) return
  await deleteAnnouncement(id)
  if (items.value.length === 1 && page.value > 1) page.value -= 1
  await fetchList()
}

let deb: number | undefined
watch([visibility, orderBy, orderDir, perPage], () => { page.value = 1; fetchList() })
watch(pinnedOnly, () => { page.value = 1; fetchList() })
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

function openShare(a: Announcement) {
  const id = Number(a?.id ?? 0)
  if (!Number.isFinite(id) || id < 1) return
  shareOpenId.value = id
  shareTitle.value = a.title || ""
  shareText.value = (a as any).excerpt || stripHtml((a as any).body || "")
  sharePayload.value = a || null
}

function closeShare() {
  shareOpenId.value = null
  sharePayload.value = null
}
</script>

<template>
  <PageContainer>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
      <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <!-- match EventsListPage: red title -->
        <h1 class="text-2xl font-semibold text-red-500 my-4">
          {{ t("announcement.list.title") }}
        </h1>
        <!-- match EventsListPage: emerald CTA -->
        <router-link
          :to="{ name:'announcements.create' }"
          class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        >
          <Icon
            icon="mdi:plus-circle"
            class="w-5 h-5"
          />
          <span>{{ t("announcement.list.create") }}</span>
        </router-link>
      </div>

      <!-- filters card: neutral borders + blue focus rings like events page -->
      <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
          <div class="lg:col-span-5">
            <div class="relative h-10">
              <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                <Icon
                  icon="mdi:magnify"
                  class="w-5 h-5"
                />
              </span>
              <input
                v-model="search"
                type="text"
                :placeholder="t('announcement.list.searchPlaceholder')"
                class="h-10 w-full rounded-xl border border-gray-300 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
              >
            </div>
          </div>

          <div class="lg:col-span-3 flex items-center gap-2">
            <select
              v-model="visibility"
              class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
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

            <button
              type="button"
              class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-gray-300 dark:border-gray-700"
              :class="pinnedOnly ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-300' : 'bg-white dark:bg-gray-900 text-gray-700 dark:text-gray-200'"
              @click="pinnedOnly = !pinnedOnly"
            >
              <Icon
                :icon="pinnedOnly ? 'mdi:pin' : 'mdi:pin-outline'"
                class="w-5 h-5"
              />
              <span>{{ t('announcement.list.onlyPinned') }}</span>
            </button>

            <button
              type="button"
              class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
              @click="reset"
            >
              <Icon
                icon="mdi:close"
                class="w-4 h-4"
              />
              <span>{{ t('common.clear') }}</span>
            </button>
          </div>

          <div class="lg:col-span-2">
            <select
              v-model="orderBy"
              class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
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
          </div>

          <div class="lg:col-span-1">
            <button
              type="button"
              class="h-10 w-full inline-flex items-center justify-center gap-1 rounded-xl border px-3 text-sm dark:border-gray-700"
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
              class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
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

      <!-- table/card list and actions identical tone to events -->
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
                icon="mdi:bullhorn"
                class="w-7 h-7 text-gray-500"
              />
            </div>
            <p class="text-sm text-gray-600 dark:text-gray-300">
              {{ t("announcement.list.noResults") }}
            </p>
          </div>

          <div v-else>
            <div class="hidden lg:block overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800/60">
                  <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200 w-16">
                      {{ t("announcement.table.cover") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("announcement.table.title") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("announcement.table.visibility") }}
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("announcement.table.publishAt") }}
                    </th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">
                      {{ t("announcement.table.actions") }}
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr
                    v-for="a in items"
                    :key="a.id"
                    class="border-t border-gray-100 dark:border-gray-800 hover:bg-gray-50/60 dark:hover:bg-gray-800/40 transition"
                  >
                    <td class="px-4 py-3">
                      <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                        <img
                          v-if="coverThumb(a)"
                          :src="coverThumb(a)!"
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
                        <span class="font-medium text-gray-900 dark:text-gray-100 truncate max-w-[420px]">{{ a.title }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">@{{ a.slug || "—" }}</span>
                      </div>
                    </td>
                    <td class="px-4 py-3 text-sm">
                      {{ t("announcement.visibility." + a.visibility) }}
                    </td>
                    <td class="px-4 py-3 text-gray-800 dark:text-gray-200">
                      {{ fmt(a.publish_at) || "—" }}
                    </td>
                    <td class="px-4 py-3">
                      <div class="flex items-center justify-end gap-2">
                        <button
                          class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                          @click="openShare(a)"
                        >
                          <Icon
                            icon="mdi:share-variant"
                            class="w-4 h-4"
                          />
                        </button>
                        <router-link
                          :to="{ name:'announcements.edit', params:{ id:a.id } }"
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                        >
                          <Icon
                            icon="mdi:pencil"
                            class="w-4 h-4"
                          />
                          <span>{{ t("announcement.actions.edit") }}</span>
                        </router-link>
                        <button
                          class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-xs text-red-600 border-red-300 hover:bg-red-50 dark:text-red-400 dark:border-red-800/60 dark:hover:bg-red-900/20"
                          @click="destroyRow(a.id)"
                        >
                          <Icon
                            icon="mdi:trash-can"
                            class="w-4 h-4"
                          />
                          <span>{{ t("announcement.actions.delete") }}</span>
                        </button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <!-- cards on mobile, matching events page tones -->
            <div class="lg:hidden divide-y divide-gray-100 dark:divide-gray-800">
              <div
                v-for="a in items"
                :key="a.id"
                class="p-4 flex items-start gap-3"
              >
                <div class="h-12 w-12 rounded-md overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
                  <img
                    v-if="coverThumb(a)"
                    :src="coverThumb(a)!"
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
                      {{ a.title }}
                    </div>
                    <span class="flex-shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-700 dark:bg-gray-800/60 dark:text-gray-300">
                      @{{ a.slug || "—" }}
                    </span>
                  </div>
                  <div class="text-xs text-gray-600 dark:text-gray-300 mt-0.5">
                    {{ fmt(a.publish_at) || "—" }}
                  </div>
                  <div class="mt-2 flex items-center gap-2">
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                      @click="openShare(a)"
                    >
                      <Icon
                        icon="mdi:share-variant"
                        class="w-4 h-4"
                      />
                    </button>
                    <router-link
                      :to="{ name:'announcements.edit', params:{ id:a.id } }"
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs dark:border-gray-700"
                    >
                      <Icon
                        icon="mdi:pencil"
                        class="w-4 h-4"
                      />
                      <span>{{ t("announcement.actions.edit") }}</span>
                    </router-link>
                    <button
                      class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-xs text-red-600 border-red-300 dark:text-red-400 dark:border-red-800/60"
                      @click="destroyRow(a.id)"
                    >
                      <Icon
                        icon="mdi:trash-can"
                        class="w-4 h-4"
                      />
                      <span>{{ t("announcement.actions.delete") }}</span>
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
                {{ t('announcement.list.pageInfo', { page: meta.current_page, last: lastPage, total: meta.total }) }}
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
                  <span>{{ t("announcement.common.prev") }}</span>
                </button>
                <button
                  class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
                  :disabled="(meta.current_page || 1) >= lastPage"
                  @click="goNext"
                >
                  <span>{{ t("announcement.common.next") }}</span>
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
      shareable-type="App\\Models\\Announcement\\Announcement"
      shareable-alias="announcement"
      :shareable-id="shareOpenId"
      :title="shareTitle"
      :text="shareText"
      :announcement="sharePayload"
      @close="closeShare"
      @shared="closeShare"
    />
  </PageContainer>
</template>
