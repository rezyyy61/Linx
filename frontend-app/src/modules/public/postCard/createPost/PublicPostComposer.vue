<!-- /src/modules/public/postCard/createPost/PublicPostComposer.vue -->
<template>
  <transition
    appear
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-50"
    >
      <div
        class="absolute inset-0 bg-black/45 backdrop-blur-[2px] dark:bg-black/60"
        @click="onClose"
      />
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl overflow-hidden rounded-2xl bg-white/95 shadow-2xl ring-1 ring-black/5 backdrop-blur-sm dark:bg-zinc-900/90 dark:ring-white/10">
          <div class="flex items-center justify-between border-b border-zinc-100 px-5 py-4 dark:border-zinc-800">
            <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
              {{ isEdit ? 'Edit post' : 'Create post' }}
            </h3>
            <div class="flex items-center gap-2">
              <span
                v-if="!isEdit"
                class="text-xs text-zinc-400 dark:text-zinc-500"
              >{{ charsLeft }}</span>
              <button
                class="rounded-xl p-2 hover:bg-zinc-100 dark:hover:bg-zinc-800"
                @click="onClose"
              >
                ✕
              </button>
            </div>
          </div>

          <div class="px-5 py-4">
            <div class="flex items-start gap-3">
              <AvatarUser
                :src="avatar"
                :name="name"
                :color="color"
                size="sm"
                rounded="full"
                ring
              />
              <div class="min-w-0 flex-1">
                <textarea
                  ref="ta"
                  v-model="plainText"
                  rows="3"
                  dir="auto"
                  :placeholder="placeholder"
                  class="w-full resize-none bg-transparent text-[15px] leading-6 text-zinc-900 placeholder:text-zinc-400 focus:outline-none dark:text-zinc-100 dark:placeholder:text-zinc-500"
                  @input="autoResize"
                />
                <div class="mt-4 space-y-3">
                  <MediaGallery
                    :items="galleryItems"
                    @remove="onRemove"
                    @move="onMove"
                    @reorder="onReorder"
                  />
                  <div class="rounded-2xl border border-dashed border-zinc-300 p-3 transition hover:border-zinc-400 dark:border-zinc-700 dark:hover:border-zinc-600">
                    <div class="flex items-center justify-between gap-3">
                      <div class="flex items-center gap-2">
                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-xl px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-800">
                          <input
                            ref="file"
                            type="file"
                            class="hidden"
                            multiple
                            accept="image/*,video/*"
                            @change="onPickedInput"
                          >
                          <span class="text-sm text-zinc-700 dark:text-zinc-200">Add media</span>
                        </label>
                      </div>
                      <span class="text-xs text-zinc-400 dark:text-zinc-500">Images or videos</span>
                    </div>
                    <div class="mt-3">
                      <MediaUploader
                        :remaining="fm?.remaining"
                        @picked="onPicked"
                      />
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-4 flex items-center justify-between">
              <div class="flex items-center gap-2">
                <span
                  v-if="isUploading"
                  class="inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2 py-1 text-xs text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                >Uploading…</span>
              </div>
              <div class="flex items-center gap-2">
                <button
                  class="rounded-xl px-3 py-2 text-sm text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800"
                  @click="onClose"
                >
                  Cancel
                </button>
                <button
                  class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="!canSubmit"
                  :aria-busy="saving"
                  @click="submit"
                >
                  {{ isEdit ? 'Save' : 'Post' }}
                </button>
              </div>
            </div>

            <div
              v-if="errorText"
              class="mt-3 rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-sm text-red-700 dark:border-red-700/40 dark:bg-red-950 dark:text-red-200"
            >
              {{ errorText }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, nextTick, watch, onUnmounted, shallowRef, unref } from 'vue'
import AvatarUser from '@/components/shared/AvatarUser.vue'
import MediaUploader from '@/modules/dashboard/pages/posts/components/createPost/MediaUploader.vue'
import MediaGallery from '@/modules/dashboard/pages/posts/components/createPost/MediaGallery.vue'
import { useMediaStore } from '@/stores/post/post.media'
import { usePostForm } from '@/modules/dashboard/pages/posts/usePostForm'
import { normalizeApiError } from '@/modules/dashboard/pages/posts/lib/errors'
import { useToast } from '@/modules/toast/useToast'

type InitialPost = {
  id: number | string
  content?: string | null
  text?: string | null
  media?: any[]
  visibility?: string
  published_at?: string | null
}

const props = withDefaults(defineProps<{
  open?: boolean
  mode?: 'create'|'edit'
  initial?: InitialPost|null
  avatar?: string
  name?: string
  color?: string
  placeholder?: string
  maxLength?: number
}>(), {
  open: false,
  mode: 'create',
  initial: null,
  avatar: '',
  name: 'You',
  color: undefined,
  placeholder: 'Write something…',
  maxLength: 1000,
})

const emit = defineEmits<{ (e:'close'): void }>()
const mediaStore = useMediaStore()
const toast = useToast()

const fmRef = shallowRef<ReturnType<typeof usePostForm> | null>(null)
function newForm(seed?: any) { fmRef.value = usePostForm(seed); return fmRef.value! }
function destroyUploads() { mediaStore.list.slice().forEach(t => { try { mediaStore.cleanupTask(t) } catch { /* empty */ } }) }
function resetForm() {
  if (fmRef.value && (fmRef.value as any).reset) (fmRef.value as any).reset()
  fmRef.value = null
  destroyUploads()
  plainText.value = ''
  if (file.value) file.value.value = ''
}

const isEdit = computed(() => props.mode === 'edit')
const saving = computed(() => Boolean(fmRef.value?.isSaving?.value))

const plainText = ref('')
const ta = ref<HTMLTextAreaElement|null>(null)
const file = ref<HTMLInputElement|null>(null)
const globalError = ref<string|null>(null)

const galleryItems = computed(() => {
  const g = (fmRef.value as any)?.gallery
  if (Array.isArray(g)) return g
  const v = unref(g)
  return Array.isArray(v) ? v : []
})

const hasAnyMedia = computed(() => galleryItems.value.length > 0)
const allMediaReady = computed(() => {
  if (!hasAnyMedia.value) return false
  const readySet = new Set(['ready','done','processed','complete','completed'])
  return galleryItems.value.every((i: any) => i?.type === 'existing' || readySet.has(String(i?.status ?? '').toLowerCase()))
})
const isUploading = computed(() => hasAnyMedia.value && !allMediaReady.value)
const hasText = computed(() => plainText.value.trim().length > 0)
const canSubmit = computed(() => saving.value ? false : (hasAnyMedia.value ? allMediaReady.value : hasText.value))
const charsLeft = computed(() => props.maxLength - (plainText.value?.length || 0))
const errorText = computed(() => globalError.value || '')

const fm = new Proxy({}, { get(_, k: string) { return (fmRef.value as any)?.[k] } }) as any

function autoResize() {
  if (!ta.value) return
  ta.value.style.height = '0px'
  ta.value.style.height = Math.min(300, ta.value.scrollHeight) + 'px'
}
onMounted(() => nextTick(autoResize))
watch(() => props.open, v => { if (v) nextTick(autoResize) })

function normalizeForForm(p: any) {
  const media = Array.isArray(p?.media)
    ? p.media.map((m: any, i: number) => {
      let mimeRaw = String(m?.mime_type ?? m?.mimeType ?? m?.mime ?? m?.type ?? '').toLowerCase()
      if (mimeRaw === 'image') mimeRaw = 'image/*'
      else if (mimeRaw === 'video') mimeRaw = 'video/*'
      else if (mimeRaw && !mimeRaw.includes('/')) mimeRaw = `${mimeRaw}/*`
      const url =
        m?.url ?? m?.public_url ?? m?.publicUrl ??
        m?.original_url ?? m?.originalUrl ??
        m?.preview_url ?? m?.previewUrl ??
        m?.path ?? m?.src ?? null
      return { id: Number(m?.id ?? i), url, mime_type: mimeRaw || undefined, width: m?.width ?? m?.meta?.width ?? null, height: m?.height ?? m?.meta?.height ?? null, order: typeof m?.order === 'number' ? m.order : (m?.sort ?? i) }
    })
    : []
  return { id: Number(p?.id), content: p?.text ?? p?.content ?? '', visibility: p?.visibility ?? 'public', published_at: p?.published_at ?? p?.publishedAt ?? null, media }
}

function openCreate() {
  resetForm()
  const f = newForm(undefined)
  ;(f as any).visibility.value = 'public'
  ;(f as any).published_at.value = null
  ;(f as any).content.value = null
  plainText.value = ''
}

function openEdit() {
  resetForm()
  const f = newForm(undefined)
  if (!props.initial) return
  const norm = normalizeForForm(props.initial)
  ;(f as any).hydrate(norm)
  plainText.value = norm.content || ''
}

watch(() => props.mode, m => { if (!props.open) return; m === 'edit' ? openEdit() : openCreate() }, { immediate: true })
watch(() => props.initial?.id, () => { if (props.open && isEdit.value) openEdit() }, { immediate: true })
watch(() => props.open, v => { if (v) { isEdit.value ? openEdit() : openCreate() } else { resetForm() } }, { immediate: true })
onUnmounted(() => { resetForm() })

function onPicked(files: File[]) { fm.addFiles(files) }
function onPickedInput(e: Event) {
  const input = e.target as HTMLInputElement
  const files = Array.from(input.files || [])
  if (files.length) fm.addFiles(files)
  input.value = ''
}
function onRemove(key: string) { fm.removeItem(key) }
function onMove(i: number, dir: -1|1) { fm.move(i, dir) }
function onReorder(keys: string[]) { fm.reorder(keys) }

async function submit() {
  globalError.value = null
  fm.content.value = plainText.value.trim() || null
  try {
    if (isEdit.value && props.initial?.id) {
      await fm.publishUpdate(Number(props.initial.id))
      toast.success('Post updated\nYour changes have been saved.')
      resetForm()
    } else {
      await fm.publishCreate()
      toast.success('Posted\nYour post is now live.')
      resetForm()
    }
    emit('close')
  } catch (e: any) {
    const err = normalizeApiError(e)
    globalError.value = err.message || 'Error'
  }
}

function onClose() {
  resetForm()
  emit('close')
}
</script>
