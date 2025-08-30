<script setup lang="ts">
import { ref, computed, watch, type WritableComputedRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'
import type { Post, PostPayload } from '@/stores/post/post'
import { usePostStore } from '@/stores/post/post'
import { usePostForm } from '@/modules/dashboard/pages/posts/usePostForm'
import { normalizeApiError } from '@/modules/dashboard/pages/posts/lib/errors'
import MediaUploader from '@/modules/dashboard/pages/posts/components/createPost/MediaUploader.vue'
import MediaGallery from '@/modules/dashboard/pages/posts/components/createPost/MediaGallery.vue'
import RichTextEditor from '@/modules/dashboard/pages/posts/components/text/RichTextEditor.vue'
import PostMetaFields from '@/modules/dashboard/pages/posts/components/createPost/PostMetaFields.vue'
import { useMediaStore } from '@/stores/post/post.media'

const { t, te } = useI18n()
const tr = (k: string) => (te(`post.${k}`) ? t(`post.${k}`) : t(k))

const props = defineProps<{ initial?: Post; mode?: 'create'|'edit' }>()
const emit = defineEmits<{
  (e:'saved', post: Post): void
  (e:'published', post: Post): void
  (e:'error', payload: { status:number; message:string; errors?:any }): void
}>()

const store = usePostStore()
const mediaStore = useMediaStore()
const fm = usePostForm(props.initial)

const saving = fm.isSaving
const globalError = ref<string | null>(null)
const canSubmit = computed(() => !saving.value)
const isEdit = computed(() => props.mode === 'edit')
const isCreate = computed(() => props.mode !== 'edit')

const contentProxy: WritableComputedRef<string> = computed({
  get(): string {
    return (fm as any).content?.value ?? ''
  },
  set(v: string): void {
    ;(fm as any).content.value = v && v.length ? v : null
  },
})

function onPicked(files: File[]) { fm.addFiles(files) }
function onRemove(key:string) { fm.removeItem(key) }
function onMove(i:number, dir:-1|1) { fm.move(i, dir) }

type GalleryItem = {
  key: string
  type: 'existing'|'task'
  id?: number
  uid?: string
  url?: string
  mime?: string
  width?: number|null
  height?: number|null
  progress?: number
  status?: string
}

function mapPostMediaToGallery(p: Post): GalleryItem[] {
  return (p.media || []).map((m) => ({
    key: `existing:${m.id}`,
    type: 'existing',
    id: m.id,
    url: m.url,
    mime: (m as any).mime_type || '',
    width: m.width ?? null,
    height: m.height ?? null,
    progress: 100,
    status: 'ready',
  }))
}

function clearLocalUploadTasks() {
  for (const t of mediaStore.list) {
    try { mediaStore.cleanupTask(t) } catch { /* empty */ }
  }
}

function hydrateFromInitial() {
  if (!isEdit.value || !props.initial) return
  clearLocalUploadTasks()
  const p = props.initial
  ;(fm as any).content.value = p.content ?? null
  ;(fm as any).visibility.value = p.visibility
  ;(fm as any).status.value = p.status
  ;(fm as any).published_at.value = p.published_at
  ;(fm as any).gallery.value = mapPostMediaToGallery(p)
}

watch([isEdit, () => props.initial?.id], () => {
  hydrateFromInitial()
}, { immediate: true })

function buildPayloadFromForm(): PostPayload {
  const gal = (fm as any).gallery?.value
  const media = Array.isArray(gal)
    ? gal
      .map((it:any, i:number) => (it?.id ? { id: Number(it.id), order: i } : null))
      .filter(Boolean) as { id:number; order?:number }[]
    : null

  return {
    content: (fm as any).content?.value ?? null,
    visibility: (fm as any).visibility?.value ?? (fm as any).visibility,
    status: (fm as any).status?.value ?? (fm as any).status,
    media: media && media.length ? media : null,
  }
}

async function onSave() {
  if (!isEdit.value || !props.initial?.id) return
  globalError.value = null
  try {
    const payload = buildPayloadFromForm()
    const p = await store.update(Number(props.initial.id), payload)
    emit('saved', p)
  } catch (e:any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}

async function onPublish() {
  if (!isCreate.value) return
  globalError.value = null
  try {
    const p = await (fm as any).publishCreate()
    emit('published', p as any)
  } catch (e:any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}
</script>

<template>
  <form
    class="space-y-6"
    aria-live="polite"
    @submit.prevent="onSave"
  >
    <div
      v-if="globalError"
      class="flex items-start gap-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-950 dark:text-red-200"
    >
      <Icon
        icon="solar:danger-triangle-bold-duotone"
        class="h-5 w-5 shrink-0"
      />
      <span>{{ tr('errors.generic') }}</span>
    </div>

    <div class="space-y-2">
      <label class="block text-sm font-medium text-gray-700 dark:text-gray-200">{{ tr('form.content') }}</label>
      <div class="rounded-xl border border-gray-300 bg-white dark:border-gray-700 dark:bg-gray-900">
        <RichTextEditor
          v-model="contentProxy"
          :placeholder="tr('form.content_placeholder')"
          class="rich-text-editor"
        />
      </div>
    </div>

    <PostMetaFields
      v-model:visibility="(fm as any).visibility"
      v-model:status="(fm as any).status"
      v-model:published-at="(fm as any).published_at"
    />

    <div class="space-y-3">
      <MediaUploader @picked="onPicked" />
      <MediaGallery
        :items="(fm as any).gallery"
        @remove="onRemove"
        @move="onMove"
      />
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
      <button
        v-if="isEdit"
        type="submit"
        :disabled="!canSubmit"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        :aria-busy="saving"
      >
        <Icon
          v-if="saving"
          icon="solar:refresh-bold-duotone"
          class="h-5 w-5 animate-spin"
        />
        <Icon
          v-else
          icon="solar:floppy-disk-bold-duotone"
          class="h-5 w-5"
        />
        <span>{{ tr('actions.save') }}</span>
      </button>

      <button
        v-else
        type="button"
        :disabled="!canSubmit"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        :aria-busy="saving"
        @click="onPublish"
      >
        <Icon
          v-if="saving"
          icon="solar:refresh-bold-duotone"
          class="h-5 w-5 animate-spin"
        />
        <Icon
          v-else
          icon="solar:megaphone-bold-duotone"
          class="h-5 w-5"
        />
        <span>{{ tr('actions.publish') }}</span>
      </button>
    </div>
  </form>
</template>
