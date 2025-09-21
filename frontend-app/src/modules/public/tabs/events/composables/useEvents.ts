import { ref, computed } from "vue"
import type { EventPublic } from "../types"
import { isUpcoming, isOngoing, isPast } from "../utils/datetime"
import { fetchEvents, joinEvent as apiJoin, unjoinEvent as apiUnjoin, getJoinStatus } from "../api/events"

const eventsRaw = ref<EventPublic[]>([])
const bookmarks = ref<Set<number>>(new Set())
const joinedMap = ref<Map<number, boolean>>(new Map())

export function useEvents() {
  const query = ref("")
  const status = ref<"all" | "upcoming" | "ongoing" | "past">("all")
  const tag = ref<string | null>(null)

  const filtered = computed(() => {
    return eventsRaw.value.filter(ev => {
      const q = query.value ? ev.title.toLowerCase().includes(query.value.toLowerCase()) : true
      const t = tag.value ? ev.tags.includes(tag.value) : true
      let s = true
      if (status.value === "upcoming") s = isUpcoming(ev.starts_at)
      if (status.value === "ongoing") s = isOngoing(ev.starts_at, ev.ends_at)
      if (status.value === "past") s = isPast(ev.ends_at)
      return q && t && s
    })
  })

  const events = computed(() => {
    const rank = (ev: EventPublic) => {
      if (isUpcoming(ev.starts_at)) return 0
      if (isOngoing(ev.starts_at, ev.ends_at)) return 1
      return 2
    }
    return [...filtered.value].sort((a, b) => {
      const ra = rank(a), rb = rank(b)
      if (ra !== rb) return ra - rb
      const sa = new Date(a.starts_at).getTime()
      const sb = new Date(b.starts_at).getTime()
      return sa - sb
    })
  })

  async function loadEvents(params?: Record<string, any>) {
    const res = await fetchEvents(params)
    eventsRaw.value = res.data
  }

  function findEvent(id: number) {
    return eventsRaw.value.find(e => e.id === id) || null
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
    tag,
    setQuery: (q: string) => (query.value = q),
    setStatus: (s: "all" | "upcoming" | "ongoing" | "past") => (status.value = s),
    setTag: (t: string | null) => (tag.value = t),
    ensureJoinStatus,
    isJoined,
    join,
    unjoin,
    toggleBookmark,
    isBookmarked,
  }
}
