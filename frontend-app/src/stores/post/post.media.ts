import { defineStore } from 'pinia'
import axios from 'axios'
import { api, ensureCsrfCookie } from '@/lib/http'

export type MediaKind = 'image' | 'video' | 'audio' | 'document'

export type MediaRecord = {
  id: number
  key?: string
  status?: string
  type?: string
  mime?: string
  width?: number | null
  height?: number | null
  duration?: number | null
  processed?: any
  processing?: any
  meta?: any
  url?: string
  public_url?: string
  [k: string]: any
}

export type UploadTask = {
  uid: string
  sig: string
  file: File
  kind: MediaKind
  id?: number
  key?: string
  contentType?: string
  uploadUrl?: string
  progress: number
  status: 'idle'|'presigning'|'uploading'|'finalizing'|'scanning'|'processing'|'ready'|'rejected'|'failed'|'error'
  media?: MediaRecord
  error?: string | null
  abort?: AbortController
  intervalId?: number | null
}

const fileSig = (f: File) => `${f.name}:${f.size}:${f.lastModified}`
const norm = (v: any) => (v ?? '').toString().trim().toUpperCase()
const extOf = (name: string) => { const i = name.lastIndexOf('.'); return i >= 0 ? name.slice(i + 1).toLowerCase() : '' }
const unpack = <T=any>(res:any):T => (res?.data && (res.data.data ?? res.data)) as T

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
const hasPublicUrl = (m?: MediaRecord) => !!(m?.public_url || m?.url)
const looksProcessedForVideo = (m?: any) => {
  const st = pickStatus(m)
  const readyish = ['READY','DONE','PROCESSED','COMPLETE','COMPLETED'].includes(st)
  const variants = Array.isArray(m?.processed?.variants) && m.processed.variants.length > 0
  const mp4 = !!(m?.processed?.mp4_key || m?.processed?.mp4_url || m?.processed?.mp4)
  return readyish || variants || mp4
}
const mapPhaseSmart = (m?: MediaRecord): 'ready'|'rejected'|'failed'|'scanning'|'processing' => {
  const st = pickStatus(m)
  const kind = inferKind(m)
  if (['REJECTED','BLOCKED'].includes(st)) return 'rejected'
  if (['FAILED','ERROR'].includes(st))     return 'failed'
  if (kind === 'video') {
    if (looksProcessedForVideo(m)) return 'ready'
    if (['UPLOADED','SCANNED','QUEUED','PENDING','STARTED'].includes(st)) return 'scanning'
    return 'processing'
  }
  if (hasPublicUrl(m)) return 'ready'
  if (['READY','DONE','PROCESSED','COMPLETE','COMPLETED'].includes(st)) return 'ready'
  if (['UPLOADED','SCANNED','QUEUED','PENDING','STARTED'].includes(st))  return 'scanning'
  return 'processing'
}
const floorProcessingProgress = (t: UploadTask) => {
  if (['scanning','processing','finalizing'].includes(t.status)) t.progress = Math.max(t.progress || 0, 95)
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
      const t: UploadTask = { uid, sig, file, kind, progress: 0, status: 'idle', error: null, intervalId: null }
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
        if (!['ready','rejected','failed','error'].includes(t.status)) this.startPolling(t)
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

    async fetchMedia(id: number): Promise<MediaRecord> {
      const res = await api.get(`media/${id}`, { params: { _: Date.now() } })
      return unpack<MediaRecord>(res)
    },

    startPolling(t: UploadTask, intervalMs = 1200, maxMs = 180000) {
      if (!t.id || t.intervalId) return
      let fail = 0
      let running = false
      const started = Date.now()
      const kind = t.kind
      let delay = Math.max(300, intervalMs)

      const tick = async () => {
        if (running) return
        if (t.intervalId === null || ['ready','rejected','failed','error'].includes(t.status)) return
        running = true
        try {
          const media = await this.fetchMedia(t.id as number)
          t.media = media
          const prog = pickProgress(media)
          if (prog !== null && t.status !== 'ready') t.progress = Math.min(99, prog)
          let phase = mapPhaseSmart(media)
          if (phase !== 'ready' && kind !== 'video' && hasPublicUrl(media)) phase = 'ready'
          if (phase === 'ready') { t.status = 'ready'; t.progress = 100; this.stopPolling(t); return }
          if (phase === 'rejected') { t.status = 'rejected'; this.stopPolling(t); return }
          if (phase === 'failed')   { t.status = 'failed';   this.stopPolling(t); return }
          if (phase === 'scanning') { t.status = 'scanning'; if (prog === null) floorProcessingProgress(t) }
          if (phase === 'processing') { t.status = 'processing'; if (prog === null) floorProcessingProgress(t) }
          fail = 0
          delay = Math.min(15000, Math.max(intervalMs, delay * 1.2))
        } catch {
          fail++
          if (fail >= 3) { t.status = 'error'; t.error = 'poll_error'; this.stopPolling(t); return }
          delay = Math.min(15000, Math.max(intervalMs, delay * 2))
        } finally {
          running = false
          const timedOut = (Date.now() - started > maxMs)
          const terminal = ['ready','rejected','failed','error'].includes(t.status)
          // eslint-disable-next-line no-unsafe-finally
          if (timedOut && !terminal) { t.status = 'error'; this.stopPolling(t); return }
          // eslint-disable-next-line no-unsafe-finally
          if (timedOut || terminal || t.intervalId === null) return
          t.intervalId = window.setTimeout(tick, delay)
        }
      }

      t.intervalId = window.setTimeout(tick, 0)
    },

    stopPolling(t: UploadTask) {
      if (t.intervalId) { clearTimeout(t.intervalId); t.intervalId = null }
    },

    cancelUpload(t: UploadTask) {
      if (t.abort) { try { t.abort.abort() } catch { /* empty */ } }
      t.status = 'error'
    },
  },
})
