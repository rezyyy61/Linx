import { computed, ref } from 'vue'
import type { Post, MediaItem } from '@/stores/post/Post'
import { usePostStore } from '@/stores/post/Post'
import { useMediaStore, type UploadTask } from '@/stores/post/post.media'
import { validatePost } from '@/modules/dashboard/pages/posts/lib/validation'
import { buildPostPayload } from '@/modules/dashboard/pages/posts/lib/payload'
import { validateFile, kindOfFile } from '@/modules/dashboard/pages/posts/lib/files'
import { emptyCounts, kindFromMime, remainingFrom, filterAllowed, MEDIA_LIMITS, type Counts } from '@/modules/dashboard/pages/posts/lib/mediaPolicy'

type Keyed = { key: string; type: 'existing' | 'task'; id?: number; uid?: string }
type GalleryItem = {
  key: string
  type: 'existing' | 'task'
  id?: number
  uid?: string
  url?: string
  mime?: string
  width?: number | null
  height?: number | null
  progress?: number
  status?: string
}

export function usePostForm(initial?: Post) {
  const postStore = usePostStore()
  const mediaStore = useMediaStore()

  const content = ref<string | null>(initial?.content ?? '')
  const visibility = ref<'public' | 'private' | 'friends'>(initial?.visibility ?? 'public')
  const status = ref<'draft' | 'published'>(initial?.status === 'published' ? 'published' : 'draft')
  const published_at = ref<string | null>(initial?.published_at ?? null)

  const existing = ref<MediaItem[]>(Array.isArray(initial?.media) ? initial!.media.slice() : [])
  const order = ref<Keyed[]>([...existing.value.map(m => ({ key: `e:${m.id}`, type: 'existing' as const, id: m.id }))])

  const tasks = computed<UploadTask[]>(() => mediaStore.list)

  function ensureTaskKeys() {
    for (const t of tasks.value) {
      if (!order.value.some(k => k.type === 'task' && k.uid === t.uid)) {
        order.value.push({ key: `t:${t.uid}`, type: 'task', uid: t.uid })
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
        out.push({ key: k.key, type: 'existing', id: m.id, url: m.url, mime: m.mime_type, width: m.width ?? null, height: m.height ?? null, progress: 100, status: 'ready' })
      } else if (k.type === 'task' && k.uid) {
        const t = mediaStore.byUid(k.uid)
        if (!t) continue
        out.push({ key: k.key, type: 'task', uid: t.uid, id: t.id, url: t.media?.public_url || t.media?.url, mime: t.media?.mime || t.contentType, width: t.media?.width ?? null, height: t.media?.height ?? null, progress: t.progress, status: t.status })
      }
    }
    return out
  })

  const counts = computed<Counts>(() => {
    const c = emptyCounts()
    for (const m of existing.value) {
      const k = kindFromMime(m.mime_type || '')
      if (k) { ;(c as any)[k]++; c.total++ }
    }
    for (const t of tasks.value) {
      const k = kindFromMime(String(t.media?.mime || t.contentType || ''))
      if (k) { ;(c as any)[k]++; c.total++ }
    }
    return c
  })

  const remaining = computed(() => remainingFrom(counts.value))

  function addFiles(files: File[]) {
    const ok: File[] = []
    for (const f of files) {
      const v = validateFile(f, kindOfFile(f))
      if (v.ok) ok.push(f)
    }
    if (!ok.length) return { accepted: [], rejected: files }
    const { picked } = filterAllowed(ok, counts.value)
    if (picked.length) mediaStore.enqueueFiles(picked)
    return { accepted: picked, rejected: ok.filter(f => !picked.includes(f)) }
  }

  function move(index: number, dir: -1 | 1) {
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

  async function removeItem(key: string) {
    const it = order.value.find(x => x.key === key)
    if (!it) return

    order.value = order.value.filter(x => x.key !== key)

    if (it.type === 'existing' && it.id) {
      try {
        if (initial?.id) {
          await mediaStore.detachFromPost(it.id, initial.id, 'post')
        } else {
          await mediaStore.deleteMediaOnServer(it.id)
        }
      } finally {
        existing.value = existing.value.filter(m => m.id !== it.id)
      }
      return
    }

    if (it.type === 'task' && it.uid) {
      const t = mediaStore.byUid(it.uid)
      if (t) mediaStore.cancelUpload(t)
      return
    }
  }


  const mediaPayload = computed<{ id: number; order: number }[]>(() => {
    const out: { id: number; order: number }[] = []
    const seen = new Set<number>()
    let idx = 0
    for (const k of order.value) {
      if (k.type === 'existing' && k.id) {
        if (!seen.has(k.id)) { out.push({ id: k.id, order: idx++ }); seen.add(k.id) }
      } else if (k.type === 'task' && k.uid) {
        const t = mediaStore.byUid(k.uid)
        if (t?.id && !seen.has(t.id)) { out.push({ id: t.id, order: idx++ }); seen.add(t.id) }
      }
    }
    return out
  })

  const payload = computed(() =>
    buildPostPayload(
      { content: content.value || null, visibility: visibility.value, status: status.value },
      mediaPayload.value
    )
  )

  async function saveCreate() {
    const v = validatePost(payload.value)
    if (!v.ok) throw v.errors
    const p = await postStore.create(payload.value)
    hydrate(p)
    return p
  }

  async function saveUpdate(id: number) {
    const v = validatePost(payload.value)
    if (!v.ok) throw v.errors
    const p = await postStore.update(id, payload.value)
    hydrate(p)
    return p
  }

  async function publishCreate() { status.value = 'published'; return saveCreate() }
  async function saveDraft() { status.value = 'draft'; return saveCreate() }

  async function publishUpdate(id: number) {
    const override = buildPostPayload({ content: content.value || null, visibility: visibility.value, status: 'published' }, mediaPayload.value)
    const v = validatePost(override)
    if (!v.ok) throw v.errors
    const p = await postStore.update(id, override)
    hydrate(p)
    return p
  }

  async function saveDraftUpdate(id: number) {
    const override = buildPostPayload({ content: content.value || null, visibility: visibility.value, status: 'draft' }, mediaPayload.value)
    const v = validatePost(override)
    if (!v.ok) throw v.errors
    const p = await postStore.update(id, override)
    hydrate(p)
    return p
  }

  function hydrate(next?: Post) {
    content.value = next?.content ?? ''
    visibility.value = (next?.visibility as any) ?? 'public'
    status.value = (next?.status as any) === 'published' ? 'published' : 'draft'
    published_at.value = next?.published_at ?? null
    existing.value = Array.isArray(next?.media) ? next!.media.slice() : []
    order.value = [...existing.value.map(m => ({ key: `e:${m.id}`, type: 'existing' as const, id: m.id }))]
  }

  return {
    content,
    visibility,
    status,
    published_at,
    gallery,
    order,
    counts,
    remaining,
    addFiles,
    move,
    reorder,
    removeItem,
    saveCreate,
    saveUpdate,
    publishCreate,
    publishUpdate,
    saveDraft,
    saveDraftUpdate,
    hydrate,
    limits: MEDIA_LIMITS,
    isSaving: computed(() => postStore.saving),
  }
}
