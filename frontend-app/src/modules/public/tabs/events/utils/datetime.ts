import { DateTime } from "luxon"

function parseUtcFlexible(input?: string | null) {
  if (!input) return null
  // Try ISO first
  let dt = DateTime.fromISO(input, { zone: "utc" })
  if (dt.isValid) return dt
  // Fallback for "YYYY-MM-DD HH:mm:ss"
  dt = DateTime.fromSQL(input, { zone: "utc" })
  if (dt.isValid) return dt
  // Last try: parse as JS Date
  const d = new Date(input)
  if (!Number.isNaN(d.getTime())) return DateTime.fromJSDate(d, { zone: "utc" })
  return null
}

export function formatDateTime(dt: string, tz: string, format = "dd LLL yyyy, HH:mm") {
  const p = parseUtcFlexible(dt)
  if (!p) return "Invalid DateTime"
  return p.setZone(tz).toFormat(format)
}

export function formatRange(start: string, end?: string | null, tz?: string) {
  const s = parseUtcFlexible(start)
  const e = parseUtcFlexible(end ?? undefined)
  const zone = tz || "UTC"
  if (!s && !e) return ""
  if (s && !e) return s.setZone(zone).toFormat("dd LLL yyyy, HH:mm")
  if (!s && e) return e.setZone(zone).toFormat("dd LLL yyyy, HH:mm")
  if (s && e && s.hasSame(e, "day")) {
    return `${s.setZone(zone).toFormat("dd LLL yyyy, HH:mm")} — ${e.setZone(zone).toFormat("HH:mm")}`
  }
  return `${s!.setZone(zone).toFormat("dd LLL yyyy, HH:mm")} → ${e!.setZone(zone).toFormat("dd LLL yyyy, HH:mm")}`
}

export function isUpcoming(start: string) {
  const s = parseUtcFlexible(start)
  if (!s) return false
  return s > DateTime.utc()
}

export function isOngoing(start: string, end?: string | null) {
  const s = parseUtcFlexible(start)
  const e = parseUtcFlexible(end ?? undefined)
  if (!s || !e) return false
  const now = DateTime.utc()
  return s <= now && now <= e
}

export function isPast(end?: string | null) {
  const e = parseUtcFlexible(end ?? undefined)
  if (!e) return false
  return e < DateTime.utc()
}
