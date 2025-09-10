import { defineStore } from "pinia"
import type { ToastItem, ToastType } from "./types"

let seq = 1
const timers = new Map<number, number>()

export const useToastStore = defineStore("toast", {
  state: () => ({ items: [] as ToastItem[] }),
  actions: {
    push(payload: Omit<ToastItem, "id" | "type"> & { type?: ToastType }) {
      const id = ++seq
      const item: ToastItem = {
        id,
        type: payload.type || "info",
        message: payload.message,
        title: payload.title,
        duration: payload.duration ?? 5600,
        actionLabel: payload.actionLabel,
        onAction: payload.onAction
      }
      this.items.unshift(item)
      if (item.duration && item.duration > 0) {
        const t = window.setTimeout(() => this.remove(id), item.duration)
        timers.set(id, t)
      }
      return id
    },
    pushConfirm(p: {
      message: string
      title?: string
      confirmLabel?: string
      cancelLabel?: string
      onConfirm: () => void | Promise<void>
      onCancel?: () => void
      destructive?: boolean
      duration?: number
    }) {
      const id = ++seq
      const item: ToastItem = {
        id,
        type: "confirm",
        message: p.message,
        title: p.title,
        confirmLabel: p.confirmLabel,
        cancelLabel: p.cancelLabel,
        onConfirm: p.onConfirm,
        onCancel: p.onCancel,
        destructive: !!p.destructive,
        duration: p.duration ?? 0
      }
      this.items.unshift(item)
      if (item.duration && item.duration > 0) {
        const t = window.setTimeout(() => this.remove(id), item.duration)
        timers.set(id, t)
      }
      return id
    },
    remove(id: number) {
      this.items = this.items.filter(i => i.id !== id)
      const t = timers.get(id)
      if (t) { clearTimeout(t); timers.delete(id) }
    },
    clear() {
      for (const t of timers.values()) clearTimeout(t)
      timers.clear()
      this.items = []
    }
  }
})
