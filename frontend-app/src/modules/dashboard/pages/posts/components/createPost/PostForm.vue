<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, type WritableComputedRef } from 'vue'
import { useI18n } from 'vue-i18n'
import { Icon } from '@iconify/vue'
import type { Post } from '@/stores/post/Post'
import { usePostForm } from '@/modules/dashboard/pages/posts/usePostForm'
import { normalizeApiError } from '@/modules/dashboard/pages/posts/lib/errors'
import MediaUploader from '@/modules/dashboard/pages/posts/components/createPost/MediaUploader.vue'
import MediaGallery from '@/modules/dashboard/pages/posts/components/createPost/MediaGallery.vue'
import RichTextEditor from '@/modules/dashboard/pages/posts/components/text/RichTextEditor.vue'
import PostMetaFields from '@/modules/dashboard/pages/posts/components/createPost/PostMetaFields.vue'
import { useMediaStore } from '@/stores/post/post.media'
import { useRouter } from 'vue-router'

const router = useRouter()
const { t, te } = useI18n()
const tr = (k: string) => (te(`post.${k}`) ? t(`post.${k}`) : t(k))

const props = defineProps<{ initial?: Post; mode?: 'create' | 'edit' }>()
const emit = defineEmits<{
  (e: 'saved', post: Post): void
  (e: 'published', post: Post): void
  (e: 'draft_saved', post: Post): void
  (e: 'error', payload: { status: number; message: string; errors?: any }): void
}>()

const mediaStore = useMediaStore()
const fm = usePostForm(props.initial)

const saving = fm.isSaving
const globalError = ref<string | null>(null)
const isEdit = computed(() => props.mode === 'edit')
const isCreate = computed(() => props.mode !== 'edit')

const doneStatuses = new Set(['ready','done','processed','complete','completed'])
const hasPendingUploads = computed(() => mediaStore.list.some(t => !doneStatuses.has(String(t.status ?? '').toLowerCase())))
const canSubmit = computed(() => !saving.value && !hasPendingUploads.value)

const errorText = computed(() => {
  const m = globalError.value
  if (!m) return ''
  return te(m) ? t(m) : m
})

const contentProxy: WritableComputedRef<string> = computed({
  get() { return (fm as any).content?.value ?? '' },
  set(v: string) { ;(fm as any).content.value = v && v.length ? v : null },
})

function onPicked(files: File[]) { fm.addFiles(files) }
function onRemove(key: string) { fm.removeItem(key) }
function onMove(i: number, dir: -1 | 1) { fm.move(i, dir) }
function onReorder(keys: string[]) { fm.reorder(keys) }

function clearLocalUploadTasks() {
  for (const t of mediaStore.list) { try { mediaStore.cleanupTask(t) } catch { /* empty */ } }
}

function hydrateFromInitial() {
  if (!isEdit.value || !props.initial) return
  clearLocalUploadTasks()
  fm.hydrate(props.initial)
}

watch([isEdit, () => props.initial?.id], () => { hydrateFromInitial() }, { immediate: true })
watch(isCreate, (v) => { if (v) clearLocalUploadTasks() }, { immediate: true })
onMounted(() => { if (isCreate.value) clearLocalUploadTasks() })
onUnmounted(() => { clearLocalUploadTasks() })

async function onPublish() {
  if (!isCreate.value) return
  globalError.value = null
  try {
    const p = await fm.publishCreate()
    clearLocalUploadTasks()
    emit('published', p as any)
    router.push('/dashboard/posts')
  } catch (e: any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}

async function onSaveDraft() {
  if (!isCreate.value) return
  globalError.value = null
  try {
    const p = await fm.saveDraft()
    clearLocalUploadTasks()
    emit('draft_saved', p as any)
    router.push('/dashboard/posts')
  } catch (e: any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}

async function onPublishUpdate() {
  if (!isEdit.value || !props.initial?.id) return
  globalError.value = null
  try {
    const p = await fm.publishUpdate(Number(props.initial.id))
    clearLocalUploadTasks()
    emit('published', p as any)
    router.push('/dashboard/posts')
  } catch (e: any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}

async function onSaveDraftUpdate() {
  if (!isEdit.value || !props.initial?.id) return
  globalError.value = null
  try {
    const p = await fm.saveDraftUpdate(Number(props.initial.id))
    clearLocalUploadTasks()
    emit('draft_saved', p as any)
    router.push('/dashboard/posts')
  } catch (e: any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'post.errors.generic'
    emit('error', err)
  }
}

const visibility = computed({
  get: () => (fm as any).visibility.value,
  set: (value) => ((fm as any).visibility.value = value),
})

const publishedAt = computed({
  get: () => (fm as any).published_at.value,
  set: (value) => ((fm as any).published_at.value = value),
})
</script>

<template>
  <form
    class="space-y-6"
    aria-live="polite"
  >
    <div
      v-if="globalError"
      class="flex items-start gap-2 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-950 dark:text-red-200"
    >
      <Icon
        icon="solar:danger-triangle-bold-duotone"
        class="h-5 w-5 shrink-0"
      />
      <span>{{ errorText }}</span>
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
      v-model:visibility="visibility"
      v-model:published-at="publishedAt"
    />

    <div class="space-y-3">
      <MediaGallery
        :items="(fm as any).gallery"
        @remove="onRemove"
        @move="onMove"
        @reorder="onReorder"
      />
      <MediaUploader
        :remaining="fm.remaining"
        @picked="onPicked"
      />
    </div>

    <div class="flex flex-wrap items-center gap-3 pt-2">
      <button
        v-if="isCreate"
        type="button"
        :disabled="!canSubmit"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:opacity-60"
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

      <button
        v-if="isCreate"
        type="button"
        :disabled="!canSubmit"
        class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500/30 disabled:opacity-60"
        :aria-busy="saving"
        @click="onSaveDraft"
      >
        <Icon
          v-if="saving"
          icon="solar:refresh-bold-duotone"
          class="h-5 w-5 animate-spin"
        />
        <Icon
          v-else
          icon="solar:file-text-bold-duotone"
          class="h-5 w-5"
        />
        <span>{{ tr('actions.save_draft') }}</span>
      </button>

      <template v-if="isEdit">
        <button
          type="button"
          :disabled="!canSubmit"
          class="inline-flex items-center px-3 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30 disabled:opacity-60"
          :aria-busy="saving"
          @click="onPublishUpdate"
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

        <button
          type="button"
          :disabled="!canSubmit"
          class="inline-flex items-center px-3 py-2 rounded-lg bg-gray-600 text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500/30 disabled:opacity-60"
          :aria-busy="saving"
          @click="onSaveDraftUpdate"
        >
          <Icon
            v-if="saving"
            icon="solar:refresh-bold-duotone"
            class="h-5 w-5 animate-spin"
          />
          <Icon
            v-else
            icon="solar:file-text-bold-duotone"
            class="h-5 w-5"
          />
          <span>{{ tr('actions.save_draft') }}</span>
        </button>
      </template>
    </div>
  </form>
</template>
