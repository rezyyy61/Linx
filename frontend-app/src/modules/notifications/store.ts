import { defineStore } from "pinia"
import type { NotificationVM, NotificationRecord, PaginatedResponse } from "./types"
import { fetchNotifications, markAsRead, markAllAsRead, removeNotification } from "./api"
import { mapRecordToVM } from "./mappers/kindToVM"
import { subscribeUserNotifications, type UnsubscribeFn } from "./realtime"

type Status = "idle" | "loading" | "error"

interface State {
  items: NotificationVM[]
  unreadCount: number
  page: number
  perPage: number
  hasMore: boolean
  status: Status
  error: string | null
  _unsub: UnsubscribeFn | null
  _userId: number | null
}

const CACHE_KEY_ITEMS = "notifications.items"
const CACHE_KEY_UNREAD = "notifications.unread"

function persist(items: NotificationVM[], unread: number) {
  try {
    sessionStorage.setItem(CACHE_KEY_ITEMS, JSON.stringify(items.slice(0, 20)))
    sessionStorage.setItem(CACHE_KEY_UNREAD, String(unread))
  } catch { /* empty */ }
}

function restore(): { items: NotificationVM[]; unread: number } {
  try {
    const i = JSON.parse(sessionStorage.getItem(CACHE_KEY_ITEMS) || "[]")
    const u = Number(sessionStorage.getItem(CACHE_KEY_UNREAD) || "0")
    return { items: Array.isArray(i) ? i : [], unread: isNaN(u) ? 0 : u }
  } catch {
    return { items: [], unread: 0 }
  }
}

export const useNotificationsStore = defineStore("notifications", {
  state: (): State => ({
    items: [],
    unreadCount: 0,
    page: 1,
    perPage: 15,
    hasMore: true,
    status: "idle",
    error: null,
    _unsub: null,
    _userId: null,
  }),

  getters: {
    unreadItems: (s) => s.items.filter(i => !i.read),
  },

  actions: {
    hydrateFromCache() {
      const { items, unread } = restore()
      this.items = items
      this.unreadCount = unread
    },

    async fetchFirstPage() {
      this.status = "loading"
      this.error = null
      try {
        const res: PaginatedResponse<NotificationRecord> = await fetchNotifications(1, this.perPage)
        const list = res.data.map(mapRecordToVM)
        this.items = list
        this.page = 1
        this.hasMore = this.page < (res.last_page || 1)
        this.unreadCount = list.filter(i => !i.read).length
        persist(this.items, this.unreadCount)
        this.status = "idle"
      } catch (e: any) {
        this.status = "error"
        this.error = String(e?.message || "Failed")
      }
    },

    async fetchNextPage() {
      if (!this.hasMore || this.status === "loading") return
      this.status = "loading"
      this.error = null
      try {
        const next = this.page + 1
        const res: PaginatedResponse<NotificationRecord> = await fetchNotifications(next, this.perPage)
        const list = res.data.map(mapRecordToVM)
        this.items = [...this.items, ...list]
        this.page = next
        this.hasMore = this.page < (res.last_page || this.page)
        this.unreadCount = this.items.filter(i => !i.read).length
        persist(this.items, this.unreadCount)
        this.status = "idle"
      } catch (e: any) {
        this.status = "error"
        this.error = String(e?.message || "Failed")
      }
    },

    async markOneAsRead(id: number) {
      const idx = this.items.findIndex(i => i.id === id)
      if (idx === -1) return
      const prev = this.items[idx]
      if (!prev.read) {
        this.items[idx] = { ...prev, read: true }
        this.unreadCount = Math.max(0, this.unreadCount - 1)
        persist(this.items, this.unreadCount)
      }
      try {
        await markAsRead(id)
      } catch { /* empty */ }
    },

    async markAll() {
      const before = this.unreadCount
      this.items = this.items.map(i => ({ ...i, read: true }))
      this.unreadCount = 0
      persist(this.items, this.unreadCount)
      try {
        const updated = await markAllAsRead()
        if (updated < before) { /* empty */ }
      } catch { /* empty */ }
    },

    async remove(id: number) {
      const idx = this.items.findIndex(i => i.id === id)
      if (idx === -1) return
      const wasUnread = !this.items[idx].read
      const prev = this.items
      this.items = this.items.filter(i => i.id !== id)
      if (wasUnread) this.unreadCount = Math.max(0, this.unreadCount - 1)
      persist(this.items, this.unreadCount)
      try {
        await removeNotification(id)
      } catch {
        this.items = prev
        this.unreadCount = prev.filter(i => !i.read).length
        persist(this.items, this.unreadCount)
      }
    },

    async onAuth(userId: number) {
      this._userId = userId
      if (this._unsub) {
        this._unsub()
        this._unsub = null
      }
      await this.fetchFirstPage()
      this._unsub = await subscribeUserNotifications(userId, (rec) => {
        const vm = mapRecordToVM(rec)
        const exists = this.items.some(i => i.id === vm.id)
        if (!exists) {
          this.items = [vm, ...this.items]
          if (!vm.read) this.unreadCount += 1
          persist(this.items, this.unreadCount)
        }
      })
    },

    onLogout() {
      if (this._unsub) this._unsub()
      this._unsub = null
      this._userId = null
      this.items = []
      this.unreadCount = 0
      this.page = 1
      this.hasMore = true
      this.status = "idle"
      this.error = null
      try {
        sessionStorage.removeItem(CACHE_KEY_ITEMS)
        sessionStorage.removeItem(CACHE_KEY_UNREAD)
      } catch { /* empty */ }
    },
  },
})
