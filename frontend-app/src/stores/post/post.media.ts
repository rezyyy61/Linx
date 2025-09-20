// /src/stores/post/post.media.ts
import { defineStore } from 'pinia'
import axios from 'axios'
import { api, ensureCsrfCookie } from '@/lib/http'
import { subscribePrivate, unsubscribe } from '@/lib/echo'

export type MediaKind = 'image' | 'video' | 'audio' | 'document'
export type MediaRecord = { id:number; key?:string; status?:string; type?:string; mime?:string; width?:number|null; height?:number|null; duration?:number|null; processed?:any; processing?:any; meta?:any; url?:string; public_url?:string; [k:string]:any }
export type UploadTask = { uid:string; sig:string; file:File; kind:MediaKind; id?:number; key?:string; contentType?:string; uploadUrl?:string; progress:number; status:'idle'|'presigning'|'uploading'|'finalizing'|'scanning'|'processing'|'ready'|'rejected'|'failed'|'error'; media?:MediaRecord; error?:string|null; abort?:AbortController; intervalId?:number|null; rtName?:string; rtBound?:boolean }

const fileSig = (f: File) => `${f.name}:${f.size}:${f.lastModified}`
const norm = (v: any) => (v ?? '').toString().trim().toUpperCase()
const extOf = (name: string) => { const i = name.lastIndexOf('.'); return i >= 0 ? name.slice(i + 1).toLowerCase() : '' }
const unpack = <T=any>(res:any):T => (res?.data && (res.data.data ?? res.data)) as T
const TYPE_POST_FQN = 'App\\Models\\Post\\Post'

const inferKind = (m?: MediaRecord): MediaKind => {
  const mime = (m?.mime ?? '').toLowerCase(), typ = (m?.type ?? '').toLowerCase()
  if (mime.startsWith('video/') || typ === 'video') return 'video'
  if (mime.startsWith('image/') || typ === 'image') return 'image'
  if (mime.startsWith('audio/') || typ === 'audio') return 'audio'
  return 'document'
}
const pickStatus = (m?: any): string => {
  if (!m) return ''
  const c = [m.status, m.state, m.phase, m.processing_status, m?.meta?.status, m?.processed?.status, m?.processing?.status]
  for (const s of c) { const n = norm(s); if (n) return n }
  return ''
}
const pickProgress = (m?: any): number | null => {
  const c = [m?.progress, m?.percent, m?.percentage, m?.meta?.progress, m?.meta?.percent, m?.processed?.progress, m?.processing?.progress, m?.processing?.percent]
  for (const v of c) { const n = Number(v); if (Number.isFinite(n)) return Math.max(0, Math.min(100, n)) }
  return null
}
const looksProcessedForVideo = (m?: any) => {
  const st = pickStatus(m)
  const readyish = ['READY','DONE','PROCESSED','COMPLETE','COMPLETED'].includes(st)
  const variantsRoot = Array.isArray(m?.processed?.variants) && m.processed.variants.length > 0
  const variantsVideo = Array.isArray(m?.processed?.video?.variants) && m.processed.video.variants.length > 0
  const mp4Root = !!(m?.processed?.mp4_key || m?.processed?.mp4_url || m?.processed?.mp4)
  const mp4Video = !!(m?.processed?.video?.mp4_key || m?.processed?.video?.mp4_url || m?.processed?.video?.mp4)
  return readyish || variantsRoot || variantsVideo || mp4Root || mp4Video
}
const mapPhaseSmart = (m?: MediaRecord): 'ready'|'rejected'|'failed'|'scanning'|'processing' => {
  const st = pickStatus(m)
  const kind = inferKind(m)
  if (['REJECTED','BLOCKED'].includes(st)) return 'rejected'
  if (['FAILED','ERROR'].includes(st))     return 'failed'
  if (['READY','DONE','PROCESSED','COMPLETE','COMPLETED'].includes(st)) return 'ready'
  if (kind === 'video') {
    if (looksProcessedForVideo(m)) return 'ready'
    if (['UPLOADED','SCANNED','QUEUED','PENDING','STARTED','SCANNING','PROCESSING'].includes(st)) return 'scanning'
    return 'processing'
  }
  if (['UPLOADED','SCANNED','QUEUED','PENDING','STARTED','SCANNING','PROCESSING'].includes(st)) return 'scanning'
  return 'processing'
}
const floorProcessingProgress = (t: UploadTask) => {
  if (['scanning','processing','finalizing'].includes(t.status)) t.progress = Math.max(t.progress || 0, 95)
}
const hasPublicUrl = (m?: MediaRecord|null) => !!(m?.public_url || m?.url)

const isUsableForUI = (m?: MediaRecord|null): boolean => {
  if (!m) return false
  const kind = inferKind(m)
  if (kind === 'video') return hasPublicUrl(m) && looksProcessedForVideo(m)
  return hasPublicUrl(m)
}

export const useMediaStore = defineStore('media', {
  state: () => ({
    tasks: {} as Record<string, UploadTask>,
    bySig: {} as Record<string, string>,
    queue: [] as UploadTask[],
    active: 0,
    concurrency: 3,
    pumping: false,
  }),

  getters: {
    list: (s): UploadTask[] => Object.values(s.tasks),
    byUid: (s) => (uid: string) => s.tasks[uid],
  },

  actions: {
    createTask(file: File, kind: MediaKind): UploadTask {
      const sig = fileSig(file)
      const existingUid = this.bySig[sig]
      if (existingUid && this.tasks[existingUid]) return this.tasks[existingUid]
      const uid = `${Date.now()}_${Math.random().toString(36).slice(2)}`
      const t: UploadTask = { uid, sig, file, kind, progress: 0, status: 'idle', error: null, intervalId: null, rtName: undefined, rtBound: false }
      this.tasks[uid] = t
      this.bySig[sig] = uid
      return t
    },

    enqueueTask(t: UploadTask) {
      if (!this.queue.includes(t)) this.queue.push(t)
      this._pump()
    },

    enqueueFiles(files: File[]) {
      const out: UploadTask[] = []
      for (const f of files) {
        const kind: MediaKind =
          f.type.startsWith('video/') ? 'video' :
            f.type.startsWith('image/') ? 'image' :
              f.type.startsWith('audio/') ? 'audio' : 'document'
        const t = this.createTask(f, kind)
        out.push(t)
        this.enqueueTask(t)
      }
      return out
    },

    async _runTask(t: UploadTask) {
      try {
        await this.presign(t)
        await this.uploadToS3(t)
        await this.finalize(t)
        if (!['ready','rejected','failed','error'].includes(t.status)) await this.subscribeRealtime(t)
      } catch (e: any) {
        t.status = 'error'
        t.error = e?.message || 'upload_error'
      }
    },

    async _pump() {
      if (this.pumping) return
      this.pumping = true
      try {
        while (this.active < this.concurrency && this.queue.length > 0) {
          const t = this.queue.shift()!
          this.active++
          ;(async () => {
            await this._runTask(t)
            this.active = Math.max(0, this.active - 1)
            this._pump()
          })()
          await Promise.resolve()
        }
      } finally {
        this.pumping = false
      }
    },

    async presign(t: UploadTask) {
      t.status = 'presigning'
      await ensureCsrfCookie()
      const body = { extension: extOf(t.file.name), type: t.kind }
      const res = await api.post('media/presigned', body)
      const data = unpack<{ id:number; upload_url:string; key:string; contentType:string }>(res)
      t.id = data.id
      t.uploadUrl = data.upload_url
      t.key = data.key
      t.contentType = data.contentType
      return data
    },

    async uploadToS3(t: UploadTask, onProgress?: (p:number)=>void) {
      if (!t.uploadUrl || !t.contentType) throw new Error('upload_url')
      t.status = 'uploading'
      t.progress = 0
      const ctrl = new AbortController()
      t.abort = ctrl
      let lastAt = 0
      let lastVal = -1
      await axios.put(t.uploadUrl, t.file, {
        headers: { 'Content-Type': t.contentType },
        withCredentials: false,
        signal: ctrl.signal,
        onUploadProgress: (e) => {
          if (typeof e.total === 'number' && e.total > 0) {
            const now = Date.now()
            const p = Math.round((e.loaded / e.total) * 100)
            if (p !== lastVal && (now - lastAt > 100 || p === 100)) {
              lastVal = p
              lastAt = now
              t.progress = p
              onProgress?.(p)
            }
          }
        },
      })
      t.progress = 100
    },

    async finalize(t: UploadTask, extra: Record<string, any> = {}) {
      if (!t.id) throw new Error('no_id')
      t.status = 'finalizing'
      await ensureCsrfCookie()
      const res = await api.post(`media/${t.id}/finalize`, extra)
      const media = unpack<MediaRecord>(res)
      t.media = media
      const phase = mapPhaseSmart(media)
      if (phase === 'ready') { t.status = 'ready'; t.progress = 100 }
      else if (phase === 'rejected') t.status = 'rejected'
      else if (phase === 'failed')   t.status = 'failed'
      else if (phase === 'scanning') { t.status = 'scanning'; floorProcessingProgress(t) }
      else                          { t.status = 'processing'; floorProcessingProgress(t) }
      const prog = pickProgress(media)
      if (prog !== null && t.status !== 'ready') t.progress = Math.min(99, prog)
      return media
    },

    async subscribeRealtime(t: UploadTask) {
      if (!t.id || t.rtBound) return
      const name = `media.${t.id}`
      const ch = await subscribePrivate(name)

      const handler = (p: any) => {
        t.media = { ...(t.media || {}), ...p }
        const prog = typeof p?.progress === 'number' ? p.progress : pickProgress(t.media)
        if (prog !== null && t.status !== 'ready') t.progress = Math.min(100, Number(prog))
        const st = norm(p?.status || pickStatus(t.media))
        if (['REJECTED','BLOCKED'].includes(st)) { t.status = 'rejected'; this.unsubscribeRealtime(t); return }
        if (['FAILED','ERROR'].includes(st))     { t.status = 'failed';   this.unsubscribeRealtime(t); return }
        if (t.kind === 'video') {
          if (looksProcessedForVideo(t.media)) { t.status = 'ready'; t.progress = 100; this.unsubscribeRealtime(t); return }
          if (['SCANNED','UPLOADED','QUEUED','PENDING','STARTED','SCANNING','PROCESSING'].includes(st)) { t.status = 'processing'; floorProcessingProgress(t); return }
        }
        if (['READY','DONE','PROCESSED','COMPLETE','COMPLETED'].includes(st)) { t.status = 'ready'; t.progress = 100; this.unsubscribeRealtime(t); return }
        if (['UPLOADED','SCANNED','QUEUED','PENDING','STARTED','SCANNING','PROCESSING'].includes(st)) { t.status = ['SCANNING','SCANNED'].includes(st) ? 'scanning' : 'processing'; floorProcessingProgress(t); return }
        if (st === 'DELETED') { this.unsubscribeRealtime(t); this.cleanupTask(t); return }
      }

      ch.unbind('MediaUpdated')
      ch.bind('MediaUpdated', handler)
      t.rtName = name
      t.rtBound = true
    },

    async unsubscribeRealtime(t: UploadTask) {
      if (t.rtName) { try { await unsubscribe(t.rtName) } catch { /* empty */ } }
      t.rtName = undefined
      t.rtBound = false
    },

    async fetchMedia(id: number): Promise<MediaRecord> {
      const res = await api.get(`media/${id}`, { params: { _: Date.now() } })
      return unpack<MediaRecord>(res)
    },

    async waitUntilReady(id: number, opts?: { tries?: number; intervalMs?: number }): Promise<MediaRecord | null> {
      const tries = opts?.tries ?? 10
      const intervalMs = opts?.intervalMs ?? 1000
      let rec: MediaRecord | null = null
      try { rec = await this.fetchMedia(id) } catch { rec = null }
      if (isUsableForUI(rec)) return rec

      let ch: any = null
      let resolved = false
      try {
        ch = await subscribePrivate(`media.${id}`)
        ch.bind('MediaUpdated', (p: any) => {
          if (resolved) return
          rec = { ...(rec || {}), ...(p || {}) }
          if (isUsableForUI(rec)) { resolved = true }
        })
      } catch { /* empty */ }

      for (let i = 0; i < tries && !resolved; i++) {
        try {
          const r = await this.fetchMedia(id)
          rec = { ...(rec || {}), ...(r || {}) }
          if (isUsableForUI(rec)) { resolved = true; break }
        } catch { /* empty */ }
        if (!resolved) await new Promise(r => setTimeout(r, intervalMs))
      }

      if (ch) {
        try { ch.unbind('MediaUpdated') } catch { /* empty */ }
        try { await unsubscribe(`media.${id}`) } catch { /* empty */ }
      }
      return rec
    },

    async waitUntilReadyMany(ids: number[], opts?: { tries?: number; intervalMs?: number }): Promise<Record<number, MediaRecord>> {
      const out: Record<number, MediaRecord> = {}
      const jobs = ids.map(async (id) => {
        const r = await this.waitUntilReady(id, opts)
        if (r) out[id] = r
      })
      await Promise.all(jobs)
      return out
    },

    startPolling() {},
    stopPolling() {},

    cancelUpload(t: UploadTask) {
      if (!t) return
      if (t.abort) { try { t.abort.abort() } catch { /* empty */ } }
      if (t.id) { this.deleteMediaOnServer(t.id).finally(() => this.cleanupTask(t)) }
      else { this.cleanupTask(t) }
    },

    async deleteMediaOnServer(id: number) {
      try {
        await ensureCsrfCookie()
        await api.delete(`media/${id}`)
      } catch (e:any) {
        const code = e?.response?.status
        if (code === 404 || code === 409) return
        throw e
      }
    },

    cleanupTask(t: UploadTask) {
      try { this.unsubscribeRealtime(t) } catch { /* empty */ }
      if (t.abort) { try { t.abort.abort() } catch { /* empty */ } }
      if (t.sig && this.bySig[t.sig]) delete this.bySig[t.sig]
      if (t.uid && this.tasks[t.uid]) delete this.tasks[t.uid]
      this.queue = this.queue.filter(x => x.uid !== t.uid)
    },

    async removeDraftMedia(t: UploadTask) {
      if (!t) return
      try { if (t.id) await this.deleteMediaOnServer(t.id) }
      finally { this.cleanupTask(t) }
    },

    async detachFromModel(mediaId: number, modelType: string, modelId: number, collection = 'post') {
      await ensureCsrfCookie()
      await api.post(`media/${mediaId}/detach`, { model_type: modelType, model_id: modelId, collection })
    },

    async detachFromPost(mediaId: number, postId: number, collection = 'post') {
      return this.detachFromModel(mediaId, TYPE_POST_FQN, postId, collection)
    },
  },
})
