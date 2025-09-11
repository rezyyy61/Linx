export function useTimeAgo() {
  function timeAgo(iso: string) {
    const now = new Date().getTime()
    const then = new Date(iso).getTime()
    const diff = Math.max(0, now - then)

    const sec = Math.floor(diff / 1000)
    if (sec < 60) return `${sec}s`

    const min = Math.floor(sec / 60)
    if (min < 60) return `${min}m`

    const hr = Math.floor(min / 60)
    if (hr < 24) return `${hr}h`

    const d = Math.floor(hr / 24)
    if (d < 7) return `${d}d`

    const w = Math.floor(d / 7)
    if (w < 4) return `${w}w`

    const mo = Math.floor(d / 30)
    if (mo < 12) return `${mo}mo`

    const y = Math.floor(d / 365)
    return `${y}y`
  }

  return { timeAgo }
}
