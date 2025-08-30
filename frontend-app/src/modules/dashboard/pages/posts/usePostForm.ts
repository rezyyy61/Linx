import { computed, ref } from 'vue'
import type { Post, MediaItem } from '@/stores/post/post'
import { usePostStore } from '@/stores/post/post'
import { useMediaStore, type UploadTask } from '@/stores/post/post.media'
import { validatePost } from './lib/validation'
import { buildPostPayload } from './lib/payload'
import { useDirtyFlag, useUnsavedChangesGuard } from './lib/unsaved'
import { validateFile, kindOfFile } from './lib/files'

type Keyed = { key:string; type:'existing'|'task'; id?:number; uid?:string }
type GalleryItem = {
  key:string
  type:'existing'|'task'
  id?:number
  uid?:string
  url?:string
  mime?:string
  width?:number|null
  height?:number|null
  progress?:number
  status?:string
}

export function usePostForm(initial?: Post) {
  const postStore = usePostStore()
  const mediaStore = useMediaStore()

  const content = ref<string | null>(initial?.content ?? '')
  const visibility = ref<'public'|'private'|'friends'>(initial?.visibility ?? 'public')
  const status = ref<'draft'|'published'|'archived'>(initial?.status ?? 'draft')
  const published_at = ref<string|null>(initial?.published_at ?? null)

  const existing = ref<MediaItem[]>(Array.isArray(initial?.media) ? initial!.media.slice() : [])
  const order = ref<Keyed[]>([
    ...existing.value.map(m => ({ key:`e:${m.id}`, type:'existing' as const, id:m.id }))
  ])

  const dirty = useDirtyFlag(() => ({
    content: content.value,
    visibility: visibility.value,
    status: status.value,
    published_at: published_at.value,
    order: order.value.map(x => x.key)
  }))

  useUnsavedChangesGuard(dirty)

  const tasks = computed<UploadTask[]>(() => mediaStore.list)
  const taskByUid = computed<Record<string, UploadTask>>(() => {
    const o: Record<string, UploadTask> = {}
    for (const t of tasks.value) o[t.uid] = t
    return o
  })

  function ensureTaskKeys() {
    for (const t of tasks.value) {
      if (!order.value.some(k => k.type === 'task' && k.uid === t.uid)) {
        order.value.push({ key:`t:${t.uid}`, type:'task', uid:t.uid })
      }
    }
  }

  const gallery = computed<GalleryItem[]>(() => {
    ensureTaskKeys()
    const out: GalleryItem[] = []
    for (const k of order.value) {
      if (k.type === 'existing' && k.id) {
        const m = existing.value.find(x => x.id === k.id)
        if (!m) continue
        out.push({
          key: k.key, type:'existing', id: m.id, url: m.url, mime: m.mime_type,
          width: m.width ?? null, height: m.height ?? null, progress: 100, status: 'ready'
        })
      } else if (k.type === 'task' && k.uid) {
        const t = taskByUid.value[k.uid]
        if (!t) continue
        out.push({
          key: k.key, type:'task', uid: t.uid, id: t.id, url: t.media?.public_url || t.media?.url,
          mime: t.media?.mime || t.contentType, width: t.media?.width ?? null, height: t.media?.height ?? null,
          progress: t.progress, status: t.status
        })
      }
    }
    return out
  })

  function addFiles(files: File[]) {
    const ok: File[] = []
    for (const f of files) {
      const v = validateFile(f, kindOfFile(f))
      if (v.ok) ok.push(f)
    }
    if (ok.length) mediaStore.enqueueFiles(ok)
  }

  function move(index:number, dir:-1|1) {
    const a = order.value.slice()
    const to = index + dir
    if (to < 0 || to >= a.length) return
    const [x] = a.splice(index, 1)
    a.splice(to, 0, x)
    order.value = a
  }

  function reorder(keys: string[]) {
    const map = new Map(order.value.map(x => [x.key, x]))
    const next: Keyed[] = []
    for (const k of keys) {
      const v = map.get(k)
      if (v) next.push(v)
    }
    order.value = next
  }

  function removeItem(key:string) {
    const it = order.value.find(x => x.key === key)
    if (!it) return
    if (it.type === 'existing' && it.id) {
      existing.value = existing.value.filter(m => m.id !== it.id)
      order.value = order.value.filter(x => x.key !== key)
    } else if (it.type === 'task' && it.uid) {
      const t = mediaStore.byUid(it.uid)
      if (t) mediaStore.cancelUpload(t)
      order.value = order.value.filter(x => x.key !== key)
    }
  }

  const mediaRefs = computed<{id:number}[]>(() => {
    const out: {id:number}[] = []
    const seen = new Set<number>()
    for (const k of order.value) {
      if (k.type === 'existing' && k.id) {
        if (!seen.has(k.id)) { out.push({id:k.id}); seen.add(k.id) }
      } else if (k.type === 'task' && k.uid) {
        const t = taskByUid.value[k.uid]
        if (t?.id && !seen.has(t.id)) { out.push({id:t.id}); seen.add(t.id) }
      }
    }
    return out
  })

  const payload = computed(() => buildPostPayload({
    content: content.value || null,
    visibility: visibility.value,
    status: status.value,
    published_at: published_at.value ?? null,
  }, mediaRefs.value))

  async function saveCreate() {
    const v = validatePost(payload.value)
    if (!v.ok) throw v.errors
    const p = await postStore.create(payload.value)
    dirty.value = false
    return p
  }

  async function saveUpdate(id:number) {
    const v = validatePost(payload.value)
    if (!v.ok) throw v.errors
    const p = await postStore.update(id, payload.value)
    dirty.value = false
    return p
  }

  async function publishCreate() {
    status.value = 'published'
    if (!published_at.value) published_at.value = new Date().toISOString()
    return saveCreate()
  }

  async function publishUpdate(id:number) {
    status.value = 'published'
    if (!published_at.value) published_at.value = new Date().toISOString()
    return saveUpdate(id)
  }

  return {
    content, visibility, status, published_at,
    gallery, order, addFiles, move, reorder, removeItem,
    saveCreate, saveUpdate, publishCreate, publishUpdate,
    isSaving: computed(()=>postStore.saving)
  }
}
