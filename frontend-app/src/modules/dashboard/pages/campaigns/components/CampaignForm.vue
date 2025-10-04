<script setup lang="ts">
import { reactive, computed, ref, onMounted, watch, nextTick } from "vue"
import type { CampaignRow, CreateCampaignPayload } from "../api/types"
import CampaignCoverUploader from "@/modules/dashboard/pages/campaigns/components/CampaignCoverUploader.vue"
import CampaignDocsUploader from "@/modules/dashboard/pages/campaigns/components/CampaignDocsUploader.vue"

const props = defineProps<{ initial?: Partial<CampaignRow> | null }>()
const emit = defineEmits<{
  (e: "submit", v: CreateCampaignPayload & { cover_id?: number | null; documents?: Array<{ id:number; url:string|null }> }): void
  (e: "cancel"): void
}>()

function toLocalInput(val?: string | null) {
  if (!val) return null
  return String(val).slice(0, 16) // "YYYY-MM-DDTHH:mm"
}

const form = reactive<CreateCampaignPayload>({
  title: "",
  excerpt: "",
  description: "",
  kind: "fundraising",
  status: "draft",
  visibility: "public",
  starts_at: null,
  ends_at: null,
  publish_at: null,
  meta: {
    goal_amount: null,
    goal_currency: null,
    raised_amount: null,
    signature_goal: null,
    signatures_count: null,
    needed_slots: null,
    filled_slots: null,
    target_reach: null,
    current_reach: null,
  }
})

const coverId = ref<number | null>(null)
const coverUrl = ref<string | null>(null)
const documents = ref<Array<{ id:number; url:string|null }>>([])

function applyInitial(v?: Partial<CampaignRow> | null) {
  const i = v || {}

  form.title       = i.title || ""
  form.excerpt     = i.excerpt ?? ""
  form.description = i.description ?? ""
  form.kind        = (i.kind as any) || "fundraising"
  form.status      = (i.status as any) || "draft"
  form.visibility  = (i.visibility as any) || "public"

  form.starts_at   = toLocalInput(i.starts_at)
  form.ends_at     = toLocalInput(i.ends_at)
  form.publish_at  = toLocalInput(i.publish_at)

  const m = i.meta || {}
  form.meta = {
    goal_amount:      m.goal_amount      ?? (i as any).goal_amount      ?? null,
    goal_currency:    m.goal_currency    ?? (i as any).goal_currency    ?? null,
    raised_amount:    m.raised_amount    ?? (i as any).raised_amount    ?? null,
    signature_goal:   m.signature_goal   ?? (i as any).signature_goal   ?? null,
    signatures_count: m.signatures_count ?? (i as any).signatures_count ?? null,
    needed_slots:     m.needed_slots     ?? (i as any).needed_slots     ?? null,
    filled_slots:     m.filled_slots     ?? (i as any).filled_slots     ?? null,
    target_reach:     m.target_reach     ?? (i as any).target_reach     ?? null,
    current_reach:    m.current_reach    ?? (i as any).current_reach    ?? null,
  }

  coverId.value = (i as any).cover_id ?? null
  coverUrl.value = i.cover_url ?? null
  documents.value = Array.isArray(i.documents) ? [...i.documents] : []
}

onMounted(() => {
  if (props.initial) applyInitial(props.initial)
})

// مهم: اگر parent بعداً initial را آپدیت کرد، فوراً hydrate کن
watch(
  () => props.initial,
  async (v) => {
    if (v) {
      applyInitial(v)
      await nextTick() // برای sync با DOM (اگر label شناور داری)
    }
  },
  { immediate: true } // اولین بار هم اجرا شود
)

const disabled = computed(() => !form.title.trim())

function submit() {
  emit("submit", {
    ...form,
    cover_id: coverId.value,
    documents: documents.value
  })
}

function onCoverSelected(v: { id: number; url: string | null } | null) { coverId.value = v?.id ?? null; coverUrl.value = v?.url ?? null }
function onCoverUpdated() {}
function onCoverCleared() { coverId.value = null; coverUrl.value = null }
function onDocsChanged(v: Array<{ id:number; url:string|null }>) { documents.value = v }
function onDocsUpdated() {}

function fieldClass() { return "peer w-full px-4 pt-6 pb-2 border rounded-xl bg-white dark:bg-gray-800/80 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-emerald-500/30" }
function labelClass() { return "absolute left-3 top-2.5 px-1 text-sm text-slate-500 dark:text-slate-400 transition-all pointer-events-none bg-white dark:bg-gray-800/80 peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-slate-500/70 peer-placeholder-shown:text-base peer-focus:top-2.5 peer-focus:text-sm" }
</script>

<template>
  <form
    class="space-y-8"
    @submit.prevent="submit"
  >
    <!-- Basic Info -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="relative">
        <input
          v-model.trim="form.title"
          :class="fieldClass()"
          placeholder=" "
          maxlength="120"
        >
        <label :class="labelClass()">Title</label>
      </div>
      <div class="relative">
        <select
          v-model="form.kind"
          :class="fieldClass()"
        >
          <option value="fundraising">
            Fundraising
          </option>
          <option value="petition">
            Petition
          </option>
          <option value="volunteer">
            Volunteer
          </option>
          <option value="awareness">
            Awareness
          </option>
        </select>
        <label :class="labelClass()">Kind</label>
      </div>
      <div class="relative">
        <select
          v-model="form.visibility"
          :class="fieldClass()"
        >
          <option value="public">
            Public
          </option>
          <option value="members">
            Members
          </option>
          <option value="private">
            Private
          </option>
        </select>
        <label :class="labelClass()">Visibility</label>
      </div>
      <div class="relative">
        <select
          v-model="form.status"
          :class="fieldClass()"
        >
          <option value="draft">
            Draft
          </option>
          <option value="published">
            Published
          </option>
          <option value="paused">
            Paused
          </option>
          <option value="completed">
            Completed
          </option>
          <option value="failed">
            Failed
          </option>
          <option value="archived">
            Archived
          </option>
        </select>
        <label :class="labelClass()">Status</label>
      </div>
    </section>

    <!-- Schedule -->
    <section class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div class="relative">
        <input
          v-model="form.starts_at"
          type="datetime-local"
          :class="fieldClass()"
          placeholder=" "
        >
        <label :class="labelClass()">Starts at</label>
      </div>
      <div class="relative">
        <input
          v-model="form.ends_at"
          type="datetime-local"
          :class="fieldClass()"
          placeholder=" "
        >
        <label :class="labelClass()">Ends at</label>
      </div>
      <div class="relative">
        <input
          v-model="form.publish_at"
          type="datetime-local"
          :class="fieldClass()"
          placeholder=" "
        >
        <label :class="labelClass()">Publish at</label>
      </div>
    </section>

    <!-- Goals (meta) -->
    <section>
      <div
        v-if="form.kind==='fundraising'"
        class="grid grid-cols-1 md:grid-cols-3 gap-6"
      >
        <div class="relative">
          <input
            v-model.number="form.meta!.goal_amount"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Goal Amount</label>
        </div>
        <div class="relative">
          <input
            v-model="form.meta!.goal_currency"
            type="text"
            maxlength="3"
            class="uppercase"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Currency</label>
        </div>
        <div class="relative">
          <input
            v-model.number="form.meta!.raised_amount"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Raised</label>
        </div>
      </div>

      <div
        v-else-if="form.kind==='petition'"
        class="grid grid-cols-1 md:grid-cols-2 gap-6"
      >
        <div class="relative">
          <input
            v-model.number="form.meta!.signature_goal"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Signature goal</label>
        </div>
        <div class="relative">
          <input
            v-model.number="form.meta!.signatures_count"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Signatures</label>
        </div>
      </div>

      <div
        v-else-if="form.kind==='volunteer'"
        class="grid grid-cols-1 md:grid-cols-2 gap-6"
      >
        <div class="relative">
          <input
            v-model.number="form.meta!.needed_slots"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Needed slots</label>
        </div>
        <div class="relative">
          <input
            v-model.number="form.meta!.filled_slots"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Filled slots</label>
        </div>
      </div>

      <div
        v-else
        class="grid grid-cols-1 md:grid-cols-2 gap-6"
      >
        <div class="relative">
          <input
            v-model.number="form.meta!.target_reach"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Target reach</label>
        </div>
        <div class="relative">
          <input
            v-model.number="form.meta!.current_reach"
            type="number"
            :class="fieldClass()"
            placeholder=" "
          >
          <label :class="labelClass()">Current reach</label>
        </div>
      </div>
    </section>

    <!-- Cover & Documents -->
    <section class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <CampaignCoverUploader
        :campaign-id="props.initial?.id"
        :current-cover-id="props.initial?.cover_id ?? null"
        :current-cover-url="props.initial?.cover_url ?? null"
        @selected="onCoverSelected"
        @updated="onCoverUpdated"
        @cleared="onCoverCleared"
      />
      <CampaignDocsUploader
        :campaign-id="props.initial?.id"
        :initial-docs="props.initial?.documents ?? []"
        @changed="onDocsChanged"
        @updated="onDocsUpdated"
      />
    </section>

    <!-- Actions -->
    <div class="flex items-center gap-3 pt-4">
      <button
        type="submit"
        :disabled="disabled"
        class="rounded-xl px-5 py-2.5 text-sm text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60"
      >
        Save
      </button>
      <button
        type="button"
        class="rounded-xl px-5 py-2.5 text-sm border dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-700"
        @click="$emit('cancel')"
      >
        Cancel
      </button>
    </div>
  </form>
</template>
