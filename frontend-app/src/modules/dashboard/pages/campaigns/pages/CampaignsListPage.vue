<!-- /src/modules/dashboard/pages/campaigns/pages/CampaignsListPage.vue -->
<script setup lang="ts">
import { ref, watch } from "vue"
import { Icon } from "@iconify/vue"
import { useRouter } from "vue-router"
import FiltersBar from "../components/FiltersBar.vue"
import { useCampaigns } from "../composables/useCampaigns"
import type { CampaignQuery, CampaignRow } from "../api/types"
import { deleteCampaign } from "../api/campaigns"
import { useToast } from "@/modules/toast/useToast"
import ShareModal from "@/modules/share/components/ShareModal.vue"

const router = useRouter()
const toast = useToast()

const { items, meta, q, loading, fetchList, setPage, reset } = useCampaigns()

function debounce<T extends (...args: any[]) => any>(fn: T, ms = 300) {
  let t: number | null = null
  return (...args: Parameters<T>) => {
    if (t) window.clearTimeout(t)
    t = window.setTimeout(() => fn(...args), ms)
  }
}
const triggerFetch = debounce(() => fetchList(), 300)

watch(q, () => { triggerFetch() }, { deep: true, immediate: true })

function sanitizeQuery(v: CampaignQuery): CampaignQuery {
  const next: CampaignQuery = { ...v }
  if (!next.q) delete next.q
  if (!next.kind) delete next.kind
  if (!next.visibility) delete next.visibility
  if (!next.status) delete next.status
  if (!next.order_by) delete next.order_by
  if (!next.order_dir) delete next.order_dir
  if (!next.page) next.page = 1
  return next
}
function onUpdateFilters(v: CampaignQuery) {
  q.value = sanitizeQuery(v)
}

function toggleOrder() {
  q.value = sanitizeQuery({ ...q.value, order_dir: q.value.order_dir === "asc" ? "desc" : "asc", page: 1 })
}

const deletingId = ref<number | null>(null)
async function removeConfirmed(row: CampaignRow) {
  deletingId.value = row.id
  try {
    await deleteCampaign(row.id)
    toast.success("Campaign deleted")
    if (!items.value.length && meta.value.page > 1) {
      setPage(meta.value.page - 1)
    } else {
      fetchList()
    }
  } catch {
    toast.error("Failed to delete campaign")
  } finally {
    deletingId.value = null
  }
}
function remove(row: CampaignRow) {
  toast.confirm(`Delete "${row.title}"?`, {
    confirmLabel: "Remove",
    cancelLabel: "Cancel",
    destructive: true,
    onConfirm: () => removeConfirmed(row),
  })
}

const shareOpenId = ref<number | null>(null)
const shareOpenItem = ref<CampaignRow | null>(null)
const shareTitle = ref<string>("")
const shareText = ref<string>("")
function openShare(row: CampaignRow) {
  shareOpenId.value = row.id
  shareOpenItem.value = row
  shareTitle.value = row.title || ""
  shareText.value = row.description || ""
}
function closeShare() {
  shareOpenId.value = null
  shareOpenItem.value = null
  shareTitle.value = ""
  shareText.value = ""
}
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-semibold text-red-500">
        Campaigns
      </h1>
      <button
        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        @click="router.push({name:'dashboard.campaigns.create'})"
      >
        <Icon
          icon="mdi:plus-circle"
          class="w-5 h-5"
        />
        <span>Create</span>
      </button>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
      <FiltersBar
        :model-value="q"
        :loading="loading"
        @update:model-value="onUpdateFilters"
        @toggle-order="toggleOrder"
        @reset="reset"
      />
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 overflow-hidden">
      <div
        v-if="loading"
        class="p-4 space-y-2"
      >
        <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" />
        <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" />
      </div>

      <table
        v-else
        class="min-w-full text-sm"
      >
        <thead class="bg-gray-50 dark:bg-gray-800/60">
          <tr>
            <th class="px-4 py-3 text-left font-semibold">
              Title
            </th>
            <th class="px-4 py-3 text-left font-semibold">
              Kind
            </th>
            <th class="px-4 py-3 text-left font-semibold">
              Status
            </th>
            <th class="px-4 py-3 text-left font-semibold">
              Visibility
            </th>
            <th class="px-4 py-3 text-right font-semibold">
              Actions
            </th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="r in items"
            :key="r.id"
            class="border-t border-gray-100 dark:border-gray-800"
          >
            <td class="px-4 py-3">
              {{ r.title }}
            </td>
            <td class="px-4 py-3 capitalize">
              {{ r.kind }}
            </td>
            <td class="px-4 py-3 capitalize">
              {{ r.status }}
            </td>
            <td class="px-4 py-3 capitalize">
              {{ r.visibility }}
            </td>
            <td class="px-4 py-3 text-right">
              <div class="inline-flex items-center gap-2">
                <button
                  class="inline-flex items-center justify-center rounded-lg border px-2.5 py-1.5 text-xs hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
                  @click="openShare(r)"
                >
                  <Icon
                    icon="mdi:share-variant"
                    class="w-4 h-4"
                  />
                </button>
                <button
                  class="rounded-lg border px-2.5 py-1.5 text-xs dark:border-gray-700"
                  @click="router.push({name:'dashboard.campaigns.edit', params:{id:r.id}})"
                >
                  Edit
                </button>
                <button
                  class="rounded-lg border px-2.5 py-1.5 text-xs text-red-600 border-red-300 dark:text-red-400 dark:border-red-800/60 disabled:opacity-60"
                  :disabled="deletingId === r.id"
                  @click="remove(r)"
                >
                  <span v-if="deletingId === r.id">Deleting…</span>
                  <span v-else>Delete</span>
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!items.length">
            <td
              colspan="5"
              class="px-4 py-10 text-center text-gray-500"
            >
              No results
            </td>
          </tr>
        </tbody>
      </table>

      <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100 dark:border-gray-800">
        <div class="text-xs text-gray-600 dark:text-gray-400">
          Page {{ meta.page }} of {{ meta.last_page }} • Total {{ meta.total }}
        </div>
        <div class="flex items-center gap-2">
          <button
            class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
            :disabled="meta.page<=1"
            @click="setPage(meta.page-1)"
          >
            <Icon
              icon="mdi:chevron-left"
              class="w-5 h-5"
            />
            <span>Prev</span>
          </button>
          <button
            class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
            :disabled="meta.page>=meta.last_page"
            @click="setPage(meta.page+1)"
          >
            <span>Next</span>
            <Icon
              icon="mdi:chevron-right"
              class="w-5 h-5"
            />
          </button>
        </div>
      </div>
    </div>

    <ShareModal
      v-if="shareOpenId"
      :key="shareOpenId"
      :open="true"
      shareable-type="App\\Models\\Campaign\\Campaign"
      shareable-alias="campaign"
      :shareable-id="shareOpenId"
      :campaign="shareOpenItem"
      :title="shareTitle"
      :text="shareText"
      @close="closeShare"
      @shared="closeShare"
    />
  </section>
</template>
