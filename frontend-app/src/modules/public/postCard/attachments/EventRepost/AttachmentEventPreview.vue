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

  <EventAttachmentCard
    v-else-if="model"
    :event="model"
  />
</template>

<script setup lang="ts">
import { ref, watchEffect } from 'vue'
import { api } from '@/lib/http'
import EventAttachmentCard from '@/modules/public/postCard/attachments/EventRepost/EventAttachmentCard.vue'

const props = defineProps<{
  preview?: any | null
  slug?: string | null
  post?: any
}>()

const loading = ref(false)
const model = ref<any | null>(props.preview ?? null)

function pick<T = any>(...vals: any[]): T | null {
  for (const v of vals) if (v !== undefined && v !== null && v !== '') return v as T
  return null
}

function normalizeEvent(raw: any) {
  const orgObj = pick<any>(raw.organizer, raw.organizer_profile, raw.organizer_user)
  const organizer = {
    slug: pick<string>(raw.organizer_slug, orgObj?.slug) || null,
    name: pick<string>(raw.organizer_name, orgObj?.name, orgObj?.title, raw.organizerTitle) || null,
    avatar: pick<string>(raw.organizer_avatar, orgObj?.avatar, orgObj?.logo) || null,
    id: pick<number>(raw.organizer_id, orgObj?.id) || null,
  }
  return {
    id: Number(raw.id),
    slug: pick<string>(raw.slug, String(raw.id))!,
    title: raw.title || '',
    description: raw.description || '',
    cover_url: pick<string>(raw.cover_url, raw.coverUrl),
    starts_at: pick<string>(raw.starts_at, raw.startsAt),
    ends_at: pick<string>(raw.ends_at, raw.endsAt),
    timezone: pick<string>(raw.timezone, 'UTC'),
    location: raw.location ?? null,
    organizer: organizer.slug || organizer.name || organizer.avatar ? organizer : null,
    going_count: pick<number>(raw.going_count, raw.goingCount),
    capacity: raw.capacity ?? null,
  }
}

async function fetchBySlug(slug: string) {
  try {
    const { data } = await api.get(`/public/v1/events/${slug}`, { params: { with: 'organizer' } })
    return (data?.data ?? data) as any
  } catch {
    const { data } = await api.get(`/public/v1/events/${slug}`)
    return (data?.data ?? data) as any
  }
}

async function fetchOrganizerBySlug(slug: string) {
  const { data } = await api.get(`/public/v1/profiles/${slug}`)
  const payload = data?.data ?? data
  return {
    name: pick<string>(payload?.name, payload?.title, payload?.display_name),
    slug: pick<string>(payload?.slug, slug),
    avatar: pick<string>(payload?.avatar, payload?.logo),
    id: payload?.id ?? null,
  }
}

async function ensureOrganizer(evt: any) {
  if (evt.organizer?.name && evt.organizer?.slug) return evt
  const slug = evt.organizer?.slug || evt.organizer_slug || null
  if (!slug) return evt
  try {
    const org = await fetchOrganizerBySlug(slug)
    evt.organizer = {
      slug: org.slug || evt.organizer?.slug || null,
      name: org.name || evt.organizer?.name || null,
      avatar: org.avatar || evt.organizer?.avatar || null,
      id: org.id || evt.organizer?.id || null,
    }
    return evt
  } catch {
    return evt
  }
}

watchEffect(async () => {
  if (props.preview) {
    model.value = props.preview
    return
  }

  const p: any = props.post
  if (p?.postable && (p.postable.slug || p.postable.title)) {
    let evt = normalizeEvent(p.postable)
    if (!evt.organizer?.name && evt.organizer?.slug) evt = await ensureOrganizer(evt)
    model.value = evt
    return
  }

  const slug = props.slug ?? p?.postable_slug ?? p?.postable?.slug ?? null
  if (!slug) return

  loading.value = true
  try {
    let raw = await fetchBySlug(slug)
    let evt = normalizeEvent(raw)
    if (!evt.organizer?.name && evt.organizer?.slug) evt = await ensureOrganizer(evt)
    model.value = evt
  } catch {
    model.value = null
  } finally {
    loading.value = false
  }
})
</script>
