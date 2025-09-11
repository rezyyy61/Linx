export function humanFileSize(bytes?: number) {
  if (!bytes || bytes <= 0) return ''
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  const sizes = ['B','KB','MB','GB','TB']
  const v = bytes / Math.pow(1024, i)
  return `${v.toFixed(v >= 10 ? 0 : 1)} ${sizes[i]}`
}

export function hhmmss(sec?: number) {
  if (!sec || sec <= 0) return '0:00'
  const s = Math.floor(sec)
  const h = Math.floor(s / 3600)
  const m = Math.floor((s % 3600) / 60)
  const r = s % 60
  return h > 0 ? `${h}:${String(m).padStart(2,'0')}:${String(r).padStart(2,'0')}` : `${m}:${String(r).padStart(2,'0')}`
}
