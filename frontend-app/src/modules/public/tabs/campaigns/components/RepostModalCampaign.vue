<script setup lang="ts">
import { ref, watchEffect } from "vue"
import { Icon } from "@iconify/vue"
import CampaignCard from "./CampaignCard.vue"
import { api } from "@/lib/http"
import { createRepostGeneric } from "@/modules/share/services/repostsApi"

interface Props {
  open: boolean
  campaignId?: number
  campaignSlug?: string
  initialCampaign?: any
}
const props = defineProps<Props>()
const emit = defineEmits<{(e:'close'):void; (e:'done', r:{id:number;url:string}):void }>()

const text = ref("")
const loading = ref(false)
const errorMsg = ref("")
const preview = ref<any|null>(null)

function normalizeCampaign(a:any){
  return {
    id: Number(a.id),
    slug: a.slug || String(a.id),
    title: a.title || "",
    excerpt: a.excerpt ?? null,
    description: a.description ?? null,
    visibility: a.visibility || "public",
    kind: a.kind || null,
    status: a.status || null,
    cover_url: a.cover_url ?? null,
    publish_at: a.publish_at ?? null,
    owner: a.owner ?? { name: "Owner" },
    goal_amount: a.goal_amount ?? null,
    raised_amount: a.raised_amount ?? null,
    goal_currency: a.goal_currency ?? null,
    signature_goal: a.signature_goal ?? null,
    signatures_count: a.signatures_count ?? null,
    needed_slots: a.needed_slots ?? null,
    filled_slots: a.filled_slots ?? null,
    target_reach: a.target_reach ?? null,
    current_reach: a.current_reach ?? null,
  }
}

watchEffect(() => {
  if (!props.open) return
  if (props.initialCampaign) {
    preview.value = normalizeCampaign(props.initialCampaign)
    return
  }
  if (props.campaignSlug) {
    api.get(`/public/v1/campaigns/${props.campaignSlug}`)
      .then(({data}) => {
        const payload = data?.data ?? data
        preview.value = normalizeCampaign(payload)
      })
      .catch(() => { preview.value = null })
    return
  }
  if (props.campaignId && !Number.isNaN(Number(props.campaignId))) {
    api.get(`/public/v1/campaigns/id/${props.campaignId}`)
      .then(({data}) => {
        const payload = data?.data ?? data
        preview.value = normalizeCampaign(payload)
      })
      .catch(() => { preview.value = null })
  }
})

async function onSubmit(){
  loading.value = true
  errorMsg.value = ""
  try {
    const idForApi = (preview.value?.id ?? props.campaignId)!
    const r = await createRepostGeneric({
      shareable_alias: "campaign",
      shareable_id: idForApi,
      text: text.value || null,
      visibility: "public",
    })
    emit("done", r)
  } catch {
    errorMsg.value = "Failed"
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <transition
    appear
    enter-active-class="transition duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-[60]"
    >
      <div
        class="absolute inset-0 bg-black/50 dark:bg-black/70"
        @click="$emit('close')"
      />
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 shadow-xl ring-1 ring-black/5 dark:ring-white/10 flex max-h-[85vh] flex-col">
          <div class="flex items-center justify-between px-5 py-2 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Repost Campaign
            </h3>
            <button
              class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="$emit('close')"
            >
              <Icon
                icon="mdi:close"
                class="h-5 w-5 text-gray-500 dark:text-gray-400"
              />
            </button>
          </div>

          <div class="px-5 py-4 space-y-4 flex-1 min-h-0 overflow-hidden">
            <div
              v-if="preview"
              class="flex-1 max-h-[45vh] overflow-y-auto rounded-xl border border-gray-200 dark:border-gray-800 preview-hide-share"
            >
              <CampaignCard :campaign="preview" />
            </div>

            <div class="space-y-2 shrink-0">
              <textarea
                v-model="text"
                rows="4"
                dir="auto"
                maxlength="5000"
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 resize-y"
                placeholder="Add a note…"
              />
              <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ text.length }}/5000</span>
                <span
                  v-if="errorMsg"
                  class="text-red-600 dark:text-red-400"
                >{{ errorMsg }}</span>
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
            <button
              class="px-3 py-2 text-sm rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-60"
              :disabled="loading || !preview"
              @click="onSubmit"
            >
              <Icon
                icon="mdi:repeat-variant"
                class="h-5 w-5"
              />
              <span>Repost</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<style scoped>
.preview-hide-share :deep([data-card-share]) { display: none !important; }
</style>
