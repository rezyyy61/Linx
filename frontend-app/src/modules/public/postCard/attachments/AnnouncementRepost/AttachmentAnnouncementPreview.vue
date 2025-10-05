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

  <AnnouncementAttachmentCard
    v-else-if="model"
    :announcement="model"
  />
</template>

<script setup lang="ts">
import { ref, watchEffect } from 'vue'
import { api } from '@/lib/http'
import AnnouncementAttachmentCard, { type AnnouncementPreview } from './AnnouncementAttachmentCard.vue'

const props = defineProps<{
  preview?: AnnouncementPreview | null
  slug?: string | null
  post?: any
}>()

const loading = ref(false)
const model = ref<AnnouncementPreview | null>(props.preview ?? null)

function pick<T = any>(...vals: any[]): T | null {
  for (const v of vals) if (v !== undefined && v !== null && v !== '') return v as T
  return null
}

function normalizeAnnouncement(raw: any): AnnouncementPreview {
  const ownerObj = pick<any>(raw.owner, raw.owner_profile, raw.owner_user)
  const owner = {
    slug: pick<string>(raw.owner_slug, ownerObj?.slug) || null,
    name: pick<string>(raw.owner_name, ownerObj?.name, ownerObj?.title) || null,
    avatar: pick<string>(raw.owner_avatar, ownerObj?.avatar, ownerObj?.logo) || null,
  }
  return {
    id: Number(raw.id),
    slug: String(pick<string>(raw.slug, raw.id)!),
    title: String(raw.title || ''),
    body: pick<string>(raw.body, raw.excerpt, ''),
    excerpt: pick<string>(raw.excerpt, null),
    cover_url: pick<string>(raw.cover_url, null),
    visibility: pick<string>(raw.visibility, 'public') as any,
    publish_at: pick<string>(raw.publish_at, raw.published_at, null),
    created_at: pick<string>(raw.created_at, null),
    owner: owner.slug || owner.name || owner.avatar ? owner : null,
  }
}

async function fetchBySlug(slug: string) {
  const { data } = await api.get(`/public/v1/announcements/${slug}`)
  return (data?.data ?? data) as any
}

watchEffect(async () => {
  if (props.preview) {
    model.value = props.preview
    return
  }

  const p: any = props.post
  if (p?.postable && (p.postable.slug || p.postable.title)) {
    model.value = normalizeAnnouncement(p.postable)
    return
  }

  const slug = props.slug ?? p?.postable_slug ?? p?.postable?.slug ?? null
  if (!slug) return

  loading.value = true
  try {
    const raw = await fetchBySlug(slug)
    model.value = normalizeAnnouncement(raw)
  } catch {
    model.value = null
  } finally {
    loading.value = false
  }
})
</script>
