<script setup lang="ts">
import { ref, watchEffect } from "vue"
import { api } from "@/lib/http"
import PublicationAttachmentCard, { type PublicationPreview } from "./PublicationAttachmentCard.vue"

const props = defineProps<{
  preview?: PublicationPreview | null
  slug?: string | null
  post?: any
}>()

const loading = ref(false)
const model = ref<PublicationPreview | null>(props.preview ?? null)

function normalizePublication(raw: any): PublicationPreview {
  const owner = raw.owner ?? raw.owner_profile ?? raw.owner_user ?? null
  return {
    id: Number(raw.id),
    slug: String(raw.slug ?? raw.id),
    title: String(raw.title || ""),
    issue: raw.issue ?? "",
    description: raw.description ?? raw.excerpt ?? "",
    cover_url: raw.cover_url ?? null,
    is_published: !!(raw.is_published ?? raw.published ?? true),
    publish_at: raw.publish_at ?? raw.published_at ?? null,
    created_at: raw.created_at ?? null,
    language: raw.language ?? "en",
    documents_count: raw.documents_count ?? 0,
    owner: owner
      ? {
        name: owner.name ?? owner.title ?? null,
        slug: owner.slug ?? null,
        avatar: owner.avatar ?? owner.logo ?? null,
        verified: !!owner.verified,
      }
      : null,
  }
}

async function fetchBySlug(slug: string) {
  const { data } = await api.get(`/public/v1/publications/${encodeURIComponent(slug)}`)
  return (data?.data ?? data) as any
}

watchEffect(async () => {
  if (props.preview) {
    model.value = props.preview
    return
  }

  const p: any = props.post
  if (p?.postable && (p.postable.slug || p.postable.title)) {
    model.value = normalizePublication(p.postable)
    return
  }

  if (!props.slug) return
  loading.value = true
  try {
    const raw = await fetchBySlug(props.slug)
    model.value = normalizePublication(raw)
  } catch {
    model.value = null
  } finally {
    loading.value = false
  }
})
</script>

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

  <PublicationAttachmentCard
    v-else-if="model"
    :publication="model"
  />
</template>
