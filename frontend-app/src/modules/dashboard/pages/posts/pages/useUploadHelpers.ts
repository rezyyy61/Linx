export type ExistingItem = { id: number; url?: string; mime_type?: string }

export const fileKey = (f: File) => `${f.name}:${f.size}:${f.lastModified}`

export function acceptMatches(file: File, acceptStr: string): boolean {
  const rules = acceptStr.split(',').map(s => s.trim().toLowerCase()).filter(Boolean)
  if (!rules.length) return true
  const type = file.type.toLowerCase()
  const ext = file.name.includes('.') ? `.${file.name.split('.').pop()!.toLowerCase()}` : ''
  return rules.some(rule => {
    if (rule.endsWith('/*')) return type.startsWith(rule.slice(0, -1))
    if (rule.startsWith('.')) return rule === ext
    return rule === type
  })
}

export function useFileCaches() {
  const urlCache = new WeakMap<File, string>()
  const posterCache = new WeakMap<File, string>()
  const fileUrl = (file: File) => {
    let u = urlCache.get(file)
    if (!u) { u = URL.createObjectURL(file); urlCache.set(file, u) }
    return u
  }
  const revokeFile = (file: File) => {
    const u = urlCache.get(file)
    if (u) { URL.revokeObjectURL(u); urlCache.delete(file) }
    posterCache.delete(file)
  }
  const posterOf = (file: File) => posterCache.get(file) || ''
  return { urlCache, posterCache, fileUrl, revokeFile, posterOf }
}

export function useVideoPoster(fileUrl: (f: File) => string, posterCache: WeakMap<File, string>) {
  let busy = false
  const queue: File[] = []
  const generateVideoPoster = async (file: File) => {
    if (posterCache.get(file)) return posterCache.get(file)!
    queue.push(file)
    if (busy) return null
    busy = true
    while (queue.length) {
      const f = queue.shift()!
      await renderPoster(f).catch(() => null)
    }
    busy = false
    return null
  }
  const renderPoster = (file: File) => new Promise<string | null>((resolve) => {
    const url = fileUrl(file)
    const video = document.createElement('video')
    video.crossOrigin = 'anonymous'
    video.muted = true
    video.preload = 'metadata'
    ;(video as any).playsInline = true
    video.src = url
    let timeoutId: number | null = null
    const cleanup = () => {
      if (timeoutId) window.clearTimeout(timeoutId as any)
      video.removeEventListener('loadedmetadata', onLoadedMeta)
      video.removeEventListener('seeked', onSeeked)
      video.removeEventListener('error', onError)
      video.src = ''
      try { video.load() } catch { /* empty */ }
    }
    const targetTime = () => {
      const d = Number.isFinite(video.duration) ? video.duration : 0
      return d > 0 ? Math.min(1, d / 3) : 0.1
    }
    const seekTo = (t: number) => {
      try { video.currentTime = t } catch { setTimeout(() => { try { video.currentTime = t } catch { /* empty */ } }, 50) }
    }
    const drawFrame = () => {
      const w = video.videoWidth, h = video.videoHeight
      if (!w || !h) { cleanup(); resolve(null); return }
      const maxW = 640, targetW = Math.min(maxW, w), ratio = targetW / w
      const canvas = document.createElement('canvas')
      canvas.width = targetW
      canvas.height = Math.round(h * ratio)
      const ctx = canvas.getContext('2d')
      if (!ctx) { cleanup(); resolve(null); return }
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height)
      const dataUrl = canvas.toDataURL('image/jpeg', 0.8)
      posterCache.set(file, dataUrl)
      cleanup()
      resolve(dataUrl)
    }
    const onLoadedMeta = () => { seekTo(targetTime()); timeoutId = window.setTimeout(() => drawFrame(), 1000) as any }
    const onSeeked = () => { drawFrame() }
    const onError = () => { cleanup(); resolve(null) }
    video.addEventListener('loadedmetadata', onLoadedMeta)
    video.addEventListener('seeked', onSeeked)
    video.addEventListener('error', onError)
  })
  return { generateVideoPoster }
}
