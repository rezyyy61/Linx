import { ref } from "vue"
import type { EventPublic } from "../types"
import { fetchEvents, joinEvent as apiJoin, unjoinEvent as apiUnjoin, getJoinStatus } from "../api/events"

export function useEvents() {
  const events = ref<EventPublic[]>([])
  const query = ref("")
  const status = ref<"all" | "upcoming" | "ongoing" | "past">("all")
  const dateRange = ref<"all" | "today" | "this_week" | "this_month">("all")
  const bookmarks = ref<Set<number>>(new Set())
  const joinedMap = ref<Map<number, boolean>>(new Map())

  async function loadEvents() {
    const res = await fetchEvents({
      q: query.value,
      status: status.value,
      date_range: dateRange.value,
    })
    events.value = res.data
  }

  function findEvent(id: number) {
    return events.value.find(e => e.id === id) || null
  }

  async function ensureJoinStatus(id: number) {
    try {
      const s = await getJoinStatus(id)
      joinedMap.value.set(id, s.joined)
      const ev = findEvent(id)
      if (ev) ev.going_count = s.count
    } catch {
      joinedMap.value.set(id, false)
    }
  }

  function isJoined(id: number) {
    return joinedMap.value.get(id) === true
  }

  async function join(id: number) {
    await apiJoin(id)
    await ensureJoinStatus(id)
  }

  async function unjoin(id: number) {
    await apiUnjoin(id)
    await ensureJoinStatus(id)
  }

  function toggleBookmark(id: number) {
    if (bookmarks.value.has(id)) bookmarks.value.delete(id)
    else bookmarks.value.add(id)
  }

  function isBookmarked(id: number) {
    return bookmarks.value.has(id)
  }

  return {
    events,
    loadEvents,
    query,
    status,
    dateRange,
    setQuery: (q: string) => (query.value = q),
    setStatus: (s: "all" | "upcoming" | "ongoing" | "past") => (status.value = s),
    setDateRange: (d: "all" | "today" | "this_week" | "this_month") => (dateRange.value = d),
    ensureJoinStatus,
    isJoined,
    join,
    unjoin,
    toggleBookmark,
    isBookmarked,
  }
}
