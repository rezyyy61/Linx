export function relativeTimeI18n(iso: string, t: (k:string, p?:any)=>string, now = new Date()): string {
  const ts = new Date(iso).getTime()
  const s = Math.max(0, Math.floor((now.getTime() - ts) / 1000))
  if (s < 60) return t("notification.ago.now")
  if (s < 3600) return t("notification.ago.m", { n: Math.floor(s / 60) })
  if (s < 86400) return t("notification.ago.h", { n: Math.floor(s / 3600) })
  if (s < 604800) return t("notification.ago.d", { n: Math.floor(s / 86400) })
  return new Date(iso).toLocaleDateString()
}
