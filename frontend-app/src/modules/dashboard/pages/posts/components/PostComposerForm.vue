<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import { storeToRefs } from 'pinia'
import { usePostStore, type PostPayload, type Post } from '@/stores/post/post'
import { useMediaStore, type UploadTask } from '@/stores/post/post.media'
import MediaUploader from '@/modules/dashboard/pages/posts/components/MediaUploader.vue'
import RichTextEditor from '@/modules/dashboard/pages/posts/components/text/RichTextEditor.vue'
import { useRouter } from 'vue-router'

const props = defineProps<{ post?: Post | null }>()
const emit = defineEmits<{ submit: [p: Post] }>()


const postStore  = usePostStore()
const mediaStore = useMediaStore()
const router     = useRouter()
const { tasks: tasksMapRef } = storeToRefs(mediaStore)

const MAX_MEDIA = 10

const content    = ref<string>('')
const visibility = ref<PostPayload['visibility']>('public')
const status     = ref<PostPayload['status']>('published')

const files = ref<File[]>([])
const existingMedia = ref([] as { id:number; url?:string; mime_type?:string; order?:number }[])

const submitting  = ref(false)
const errorMsg    = ref('')
const successMsg  = ref('')
const sessionUids = ref<string[]>([])

const tasks = computed<UploadTask[]>(() =>
  sessionUids.value.map(u => tasksMapRef.value[u]).filter((t): t is UploadTask => !!t)
)
const readyTasks = computed(() => tasks.value.filter(t => t.status === 'ready'))
const processingTasks = computed(() => tasks.value.filter(t =>
  ['presigning','uploading','finalizing','scanning','processing'].includes(t.status)
))
const existingCount = computed(() => existingMedia.value.length)
const selectedIds = computed(() =>
  readyTasks.value.map(t => Number(t.id)).filter(n => Number.isFinite(n))
)
const totalSelectedCount = computed(() => existingCount.value + selectedIds.value.length)
const addCapacity = computed(() => Math.max(0, MAX_MEDIA - totalSelectedCount.value))

function stripHtml(html: string) {
  return html.replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g, ' ').replace(/\s+/g, ' ').trim()
}
const contentTextLen = computed(() => stripHtml(content.value).length)
const isValid   = computed(() => contentTextLen.value > 0 || totalSelectedCount.value > 0)
const canSave   = computed(() => isValid.value && !submitting.value && processingTasks.value.length === 0)
const isEditing = computed(() => !!props.post?.id)

function onCreated(uids: string[]) {
  sessionUids.value = [...sessionUids.value, ...uids]
}

function buildOrderedMedia(existing: { id:number; order?:number }[], added: number[], limit: number) {
  const base = (existing || []).map((m, i) => ({ id: Number(m.id), order: typeof m.order === 'number' ? m.order! : i }))
  base.sort((a, b) => a.order - b.order)
  const out: { id:number; order:number }[] = []
  const seen = new Set<number>()
  for (const m of base) if (!seen.has(m.id)) { seen.add(m.id); out.push(m) }
  let maxOrder = out.length ? Math.max(...out.map(x => x.order)) : -1
  for (const id of added) if (!seen.has(id)) { seen.add(id); out.push({ id, order: ++maxOrder }) }
  return out.slice(0, limit)
}

async function save(kind: 'publish'|'draft' = 'publish') {
  if (kind === 'publish' && !canSave.value) return
  if (submitting.value) return

  submitting.value = true
  errorMsg.value   = ''
  successMsg.value = ''

  try {
    if (kind === 'draft') status.value = 'draft'

    const payload: PostPayload = {
      content: contentTextLen.value > 0 ? content.value : null,
      visibility: visibility.value,
      status: status.value,
      media: buildOrderedMedia(existingMedia.value, selectedIds.value, MAX_MEDIA),
    }

    let saved: Post
    if (isEditing.value) {
      // Guess: متد آپدیت در استور
      saved = await (postStore as any).update(props.post!.id, payload)
      emit('submit', saved)
      successMsg.value = 'Post updated'
    } else {
      saved = await postStore.create(payload)
      await router.push('/dashboard/posts')
      successMsg.value = 'Post published'
    }
    return saved
  } catch (e: any) {
    errorMsg.value = e?.message || 'Failed to save'
    throw e
  } finally {
    submitting.value = false
  }
}

function iconFor(t: UploadTask): string {
  const name = t.file?.type || ''
  if (name.startsWith('image/')) return 'mdi:image'
  if (name.startsWith('video/')) return 'mdi:filmstrip'
  if (name.startsWith('audio/')) return 'mdi:music'
  return 'mdi:file'
}
function statusChip(t: UploadTask): { text: string; cls: string } {
  switch (t.status) {
    case 'ready':      return { text: 'ready',      cls: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-900/20 dark:text-emerald-300 dark:border-emerald-800' }
    case 'uploading':  return { text: 'uploading',  cls: 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-900/20 dark:text-blue-300 dark:border-blue-800' }
    case 'finalizing':
    case 'scanning':
    case 'processing': return { text: t.status,     cls: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-900/20 dark:text-amber-300 dark:border-amber-800' }
    case 'rejected':
    case 'failed':
    case 'error':      return { text: t.status,     cls: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-900/20 dark:text-red-300 dark:border-red-800' }
    case 'presigning':
    default:           return { text: t.status,     cls: 'bg-gray-50 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700' }
  }
}

/* پرکردن فرم از props.post */
function hydrateFromPost(p?: Post | null) {
  if (!p) {
    content.value    = ''
    visibility.value = 'public'
    status.value     = 'published'
    existingMedia.value = []
    sessionUids.value   = []
    return
  }
  content.value    = p.content || ''
  visibility.value = (p.visibility as any) || 'public'
  status.value     = (p.status as any)     || 'published'
  const list = Array.isArray(p.media) ? p.media : []
  existingMedia.value = list
    .filter((m:any) => m && typeof m.id !== 'undefined')
    .map((m:any, i:number) => ({
      id: Number(m.id),
      url: m.url,
      mime_type: m.mime_type,
      order: typeof (m as any).order === 'number' ? (m as any).order : i,
    }))
  sessionUids.value = []
  files.value = []
}

onMounted(() => hydrateFromPost(props.post))
watch(() => props.post, (p) => hydrateFromPost(p))
</script>

<template>
  <div class="rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 dark:border-gray-700 overflow-hidden">
    <div class="p-4 sm:p-6 space-y-6">
      <div
        v-if="successMsg"
        class="rounded-xl border border-emerald-200 dark:border-emerald-800 bg-emerald-50 dark:bg-emerald-900/20 px-3 py-2 text-sm text-emerald-700 dark:text-emerald-300"
      >
        {{ successMsg }}
      </div>

      <div class="space-y-2">
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Text</label>
        <RichTextEditor v-model="content" />
        <div class="flex items-center justify-between">
          <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ contentTextLen }} chars
          </div>
          <div class="text-xs text-gray-500 dark:text-gray-400">
            {{ totalSelectedCount }}/{{ MAX_MEDIA }}
          </div>
        </div>
      </div>

      <MediaUploader
        v-model:files="files"
        v-model:existing="existingMedia"
        :capacity="addCapacity"
        :max="MAX_MEDIA"
        @created="onCreated"
      />

      <div
        v-if="tasks.length"
        class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden"
      >
        <div class="bg-gray-50 dark:bg-gray-800/60 px-3 py-2 text-xs text-gray-600 dark:text-gray-300 flex items-center justify-between">
          <div>Uploads: {{ readyTasks.length }} ready / {{ tasks.length }}</div>
          <div
            v-if="processingTasks.length"
            class="text-amber-600 dark:text-amber-300"
          >
            Processing {{ processingTasks.length }}…
          </div>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
          <div
            v-for="t in tasks"
            :key="t.uid"
            class="grid grid-cols-12 gap-3 items-center px-3 py-2"
          >
            <div class="col-span-6 sm:col-span-5 md:col-span-4 flex items-center gap-2 min-w-0">
              <span
                class="iconify w-5 h-5 text-gray-500"
                :data-icon="iconFor(t)"
                aria-hidden="true"
              />
              <div class="truncate text-sm text-gray-800 dark:text-gray-200">
                {{ t.file?.name || t.key }}
              </div>
            </div>
            <div class="col-span-3 sm:col-span-3 md:col-span-3">
              <div class="w-full h-2 rounded-full bg-gray-100 dark:bg-gray-800 overflow-hidden">
                <div
                  class="h-2 rounded-full bg-emerald-500 transition-[width]"
                  :style="{ width: (t.status === 'ready' ? 100 : t.progress || 0) + '%' }"
                />
              </div>
            </div>
            <div class="col-span-3 sm:col-span-2 md:col-span-2">
              <span
                class="inline-flex items-center px-2 py-0.5 rounded-md border text-[11px] capitalize"
                :class="statusChip(t).cls"
              >
                {{ statusChip(t).text }}
              </span>
            </div>
            <div class="col-span-12 sm:col-span-2 md:col-span-3 text-right">
              <span class="text-xs text-gray-500 dark:text-gray-400">{{ t.status === 'ready' ? 100 : (t.progress || 0) }}%</span>
            </div>
          </div>
        </div>
      </div>

      <div
        v-if="errorMsg"
        class="text-sm text-red-600 dark:text-red-400"
      >
        {{ errorMsg }}
      </div>
    </div>

    <div class="border-t border-gray-200 dark:border-gray-700 px-4 sm:px-6 py-3 bg-gray-50 dark:bg-gray-800/60 flex items-center gap-3">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="!canSave"
        @click="save(isEditing ? 'publish' : 'publish')"
      >
        <span
          class="iconify w-5 h-5"
          data-icon="mdi:content-save"
        />
        <span>{{ isEditing ? 'Update' : 'Publish' }}</span>
      </button>

      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl px-4 py-2 bg-gray-100 text-gray-800 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 disabled:opacity-50 disabled:cursor-not-allowed"
        :disabled="submitting"
        @click="save('draft')"
      >
        <span
          class="iconify w-5 h-5"
          data-icon="mdi:content-save-edit"
        />
        <span>{{ isEditing ? 'Save Draft' : 'Save Draft' }}</span>
      </button>

      <div class="ml-auto text-xs text-gray-500 dark:text-gray-400">
        {{ processingTasks.length ? 'Please wait for uploads to finish…' : (isEditing ? 'Ready to update' : 'Ready to publish') }}
      </div>
    </div>
  </div>
</template>
