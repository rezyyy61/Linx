<template>
  <div
    v-if="loading"
    class="mt-3"
  >
    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 animate-pulse">
      <div class="h-4 w-44 rounded bg-zinc-200 dark:bg-zinc-700 mb-3" />
      <div class="h-3 w-full rounded bg-zinc-200 dark:bg-zinc-700 mb-2" />
      <div class="h-3 w-5/6 rounded bg-zinc-200 dark:bg-zinc-700" />
    </div>
  </div>

  <CampaignAttachmentCard
    v-else-if="model"
    :campaign="model"
  />
</template>

<script setup lang="ts">
import { ref, watchEffect } from 'vue'
import { api } from '@/lib/http'
import CampaignAttachmentCard, { type CampaignPreview } from './CampaignAttachmentCard.vue'

const props = defineProps<{
  preview?: CampaignPreview | null
  slug?: string | null
  post?: any
}>()

const loading = ref(false)
const model = ref<CampaignPreview | null>(props.preview ?? null)

function pick<T = any>(...vals: any[]): T | null {
  for (const v of vals) if (v !== undefined && v !== null && v !== '') return v as T
  return null
}

function detectKind(raw: any): string | null {
  const n = (x: any) => Number.isFinite(Number(x)) ? Number(x) : 0
  if (n(raw.goal_amount) || n(raw.raised_amount) || n(raw?.meta?.goal_amount) || n(raw?.meta?.raised_amount)) return 'fundraising'
  if (n(raw.signature_goal) || n(raw.signatures_count) || n(raw?.meta?.signature_goal) || n(raw?.meta?.signatures_count)) return 'petition'
  if (n(raw.needed_slots) || n(raw.filled_slots) || n(raw?.meta?.needed_slots) || n(raw?.meta?.filled_slots)) return 'volunteer'
  if (n(raw.target_reach) || n(raw.current_reach) || n(raw?.meta?.target_reach) || n(raw?.meta?.current_reach)) return 'awareness'
  return null
}

function normalizeCampaign(raw: any): CampaignPreview {
  const ownerObj = pick<any>(raw.owner, raw.owner_profile, raw.owner_user)
  const owner = {
    slug: pick<string>(raw.owner_slug, ownerObj?.slug) || null,
    name: pick<string>(raw.owner_name, ownerObj?.name, ownerObj?.title) || null,
    avatar: pick<string>(raw.owner_avatar, ownerObj?.avatar, ownerObj?.logo) || null,
  }
  const meta = raw.meta || {}
  const kind = pick<string>(raw.kind, meta.kind, detectKind(raw))

  return {
    id: Number(raw.id),
    slug: String(pick<string>(raw.slug, raw.id)!),
    title: String(raw.title || ''),
    excerpt: pick<string>(raw.excerpt, null),
    cover_url: pick<string>(raw.cover_url, null),
    visibility: pick<string>(raw.visibility, 'public') as any,
    kind: kind || null,
    status: pick<string>(raw.status, meta.status, null),
    publish_at: pick<string>(raw.publish_at, raw.published_at, null),
    created_at: pick<string>(raw.created_at, null),
    owner: owner.slug || owner.name || owner.avatar ? owner : null,
    goal_amount: Number(pick<number>(raw.goal_amount, meta.goal_amount, 0)),
    raised_amount: Number(pick<number>(raw.raised_amount, meta.raised_amount, 0)),
    goal_currency: pick<string>(raw.goal_currency, meta.goal_currency, ''),
    signature_goal: Number(pick<number>(raw.signature_goal, meta.signature_goal, 0)),
    signatures_count: Number(pick<number>(raw.signatures_count, meta.signatures_count, 0)),
    needed_slots: Number(pick<number>(raw.needed_slots, meta.needed_slots, 0)),
    filled_slots: Number(pick<number>(raw.filled_slots, meta.filled_slots, 0)),
    target_reach: Number(pick<number>(raw.target_reach, meta.target_reach, 0)),
    current_reach: Number(pick<number>(raw.current_reach, meta.current_reach, 0)),
  }
}

async function fetchBySlug(slug: string) {
  const { data } = await api.get(`/public/v1/campaigns/${slug}`)
  return (data?.data ?? data) as any
}

watchEffect(async () => {
  if (props.preview) {
    model.value = props.preview
    return
  }

  const p: any = props.post
  if (p?.postable && (p.postable.slug || p.postable.title)) {
    model.value = normalizeCampaign(p.postable)
    return
  }

  const slug = props.slug ?? p?.postable_slug ?? p?.postable?.slug ?? null
  if (!slug) return

  loading.value = true
  try {
    const raw = await fetchBySlug(slug)
    model.value = normalizeCampaign(raw)
  } catch {
    model.value = null
  } finally {
    loading.value = false
  }
})
</script>
