<template>
  <div
    v-if="loading"
    class="mt-3"
  >
    <div class="rounded-2xl border border-zinc-200 dark:border-zinc-800 p-4 animate-pulse">
      <div class="h-4 w-40 rounded bg-zinc-200 dark:bg-zinc-700 mb-3" />
      <div class="h-3 w-full rounded bg-zinc-200 dark:bg-zinc-700 mb-2" />
      <div class="h-3 w-5/6 rounded bg-zinc-200 dark:bg-zinc-700" />
    </div>
  </div>

  <AnnouncementAttachmentCard
    v-else-if="preview"
    :announcement="preview"
  />
</template>

<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { api } from '@/lib/http'
import AnnouncementAttachmentCard, { type AnnouncementPreview } from './AnnouncementAttachmentCard.vue'

const props = defineProps<{ post: any }>()

const loading = ref(false)
const preview = ref<AnnouncementPreview | null>(null)
const fullPost = ref<any | null>(null)

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

const looksLikeAnnouncement = (p: any) =>
  p?.postable_alias === 'announcement' ||
  (p?.postable_type && String(p.postable_type).includes('Announcement')) ||
  (p?.postable && p.postable.kind === 'announcement')

const isAnnouncementAttachment = computed(() => {
  const p: any = props.post || {}
  return looksLikeAnnouncement(p) || looksLikeAnnouncement(fullPost.value || {})
})

const announcementSlug = computed<string | null>(() => {
  const p: any = props.post || {}
  const fp: any = fullPost.value || {}
  return p?.postable_slug ?? fp?.postable_slug ?? p?.postable?.slug ?? fp?.postable?.slug ?? null
})

async function ensureFullPostOnce() {
  if (fullPost.value || !props.post?.id) return
  try {
    const res = await api.get(`/public/v1/posts/${props.post.id}`)
    fullPost.value = res.data?.data ?? res.data ?? null
  } catch { fullPost.value = null }
}

async function fetchAnnouncementBySlug(slug: string) {
  const { data } = await api.get(`/public/v1/announcements/${slug}`)
  return (data?.data ?? data) as any
}

watchEffect(async () => {
  preview.value = null
  const p: any = props.post || {}

  if (!looksLikeAnnouncement(p)) await ensureFullPostOnce()
  if (!isAnnouncementAttachment.value) return

  if (p?.postable && (p.postable.slug || p.postable.title)) {
    preview.value = normalizeAnnouncement(p.postable)
    return
  }

  const fp: any = fullPost.value || {}
  if (fp?.postable && (fp.postable.slug || fp.postable.title)) {
    preview.value = normalizeAnnouncement(fp.postable)
    return
  }

  const slug = announcementSlug.value
  if (!slug) return

  loading.value = true
  try {
    const raw = await fetchAnnouncementBySlug(slug)
    preview.value = normalizeAnnouncement(raw)
  } catch {
    preview.value = null
  } finally {
    loading.value = false
  }
})
</script>
