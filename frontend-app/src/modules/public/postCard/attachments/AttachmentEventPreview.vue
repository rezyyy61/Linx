<template>
  <div
    v-if="loading"
    class="mt-3"
  >
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 p-3 animate-pulse">
      <div class="h-4 w-40 rounded bg-zinc-200 dark:bg-zinc-700 mb-3" />
      <div class="h-3 w-full rounded bg-zinc-200 dark:bg-zinc-700 mb-2" />
      <div class="h-3 w-5/6 rounded bg-zinc-200 dark:bg-zinc-700" />
    </div>
  </div>

  <EventAttachmentCard
    v-else-if="eventPreview"
    :event="eventPreview"
  />
</template>

<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import EventAttachmentCard from '@/modules/public/postCard/attachments/EventAttachmentCard.vue'
import { api } from '@/lib/http'

const props = defineProps<{ post: any }>()

const loading = ref(false)
const eventPreview = ref<any | null>(null)
const fullPost = ref<any | null>(null)

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

async function fetchEventBySlug(slug: string) {
  try {
    const { data } = await api.get(`/public/v1/events/${slug}`, { params: { with: 'organizer' } })
    return (data?.data ?? data) as any
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  } catch (e) {
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
  } catch (e) {
    console.debug('[AttachmentPreview] organizer fetch failed for slug:', slug, e)
    return evt
  }
}

const postLooksLikeEvent = (p: any) =>
  (p?.postable_type && String(p.postable_type).includes('Event')) ||
  p?.postable_alias === 'event' ||
  !!p?.postable?.slug ||
  (p?.postable && p.postable.kind === 'event')

const isEventAttachment = computed(() => {
  const p: any = props.post || {}
  return postLooksLikeEvent(p) || postLooksLikeEvent(fullPost.value || {})
})

const eventSlug = computed<string | null>(() => {
  const p: any = props.post || {}
  const fp: any = fullPost.value || {}
  return p?.postable?.slug ?? p?.postable_slug ?? fp?.postable?.slug ?? fp?.postable_slug ?? null
})

async function ensureFullPostOnce() {
  if (fullPost.value || !props.post?.id) return
  try {
    const res = await api.get(`/public/v1/posts/${props.post.id}`)
    fullPost.value = res.data?.data ?? res.data ?? null
  } catch (e) {
    console.debug('[AttachmentPreview] full post fetch failed', e)
    fullPost.value = null
  }
}

watchEffect(async () => {
  eventPreview.value = null

  const p: any = props.post || {}
  if (!postLooksLikeEvent(p)) await ensureFullPostOnce()
  if (!isEventAttachment.value) return

  if (p?.postable && (p.postable.slug || p.postable.title)) {
    let evt = normalizeEvent(p.postable)
    if (!evt.organizer?.name && evt.organizer?.slug) {
      evt = await ensureOrganizer(evt)
    }
    eventPreview.value = evt
    return
  }

  const fp: any = fullPost.value || {}
  if (fp?.postable && (fp.postable.slug || fp.postable.title)) {
    let evt = normalizeEvent(fp.postable)
    if (!evt.organizer?.name && evt.organizer?.slug) {
      evt = await ensureOrganizer(evt)
    }
    eventPreview.value = evt
    return
  }

  const slug = eventSlug.value
  if (!slug) return

  loading.value = true
  try {
    let raw = await fetchEventBySlug(slug)
    let evt = normalizeEvent(raw)
    if (!evt.organizer?.name && evt.organizer?.slug) {
      evt = await ensureOrganizer(evt)
    }
    eventPreview.value = evt
  } catch (e) {
    console.debug('[AttachmentPreview] event fetch failed for slug:', slug, e)
    eventPreview.value = null
  } finally {
    loading.value = false
  }
})
</script>
