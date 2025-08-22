import { reactive } from 'vue'
import type { NotifyState, Toast, ToastType } from './types'

const state: NotifyState = reactive({ toasts: [] })
const timers = new Map<string, number>()

function uid() {
  if (typeof crypto !== 'undefined' && 'randomUUID' in crypto) {
    // @ts-ignore
    return crypto.randomUUID()
  }
  return Math.random().toString(36).slice(2)
}

function schedule(id: string, ms: number) {
  clearTimer(id)
  const t = state.toasts.find(x => x.id === id)
  if (!t) return
  t._startedAt = Date.now()
  t._remaining = ms
  const h = window.setTimeout(() => remove(id), ms)
  timers.set(id, h)
}

function clearTimer(id: string) {
  const h = timers.get(id)
  if (h) {
    clearTimeout(h)
    timers.delete(id)
  }
}

function push(toast: Partial<Toast> & { type: ToastType }): Toast {
  const t: Toast = {
    id: uid(),
    type: toast.type,
    title: toast.title,
    description: toast.description,
    duration: toast.duration ?? 4000,
    action: toast.action,
    createdAt: Date.now()
  }
  state.toasts.push(t)
  if (t.duration && t.duration > 0) schedule(t.id, t.duration)
  return t
}

function remove(id: string) {
  clearTimer(id)
  const i = state.toasts.findIndex(t => t.id === id)
  if (i !== -1) state.toasts.splice(i, 1)
}

function info(p: Omit<Partial<Toast>, 'type'>) { return push({ ...p, type: 'info' }) }
function success(p: Omit<Partial<Toast>, 'type'>) { return push({ ...p, type: 'success' }) }
function warning(p: Omit<Partial<Toast>, 'type'>) { return push({ ...p, type: 'warning' }) }
function error(p: Omit<Partial<Toast>, 'type'>) { return push({ ...p, type: 'error' }) }

/**
 * Pause countdown when mouse enters a toast
 */
function _clear(id: string) {
  const t = state.toasts.find(x => x.id === id)
  if (!t) return
  if (t._startedAt) {
    const elapsed = Date.now() - t._startedAt
    const remaining = Math.max((t._remaining ?? t.duration ?? 0) - elapsed, 0)
    t._remaining = remaining
  }
  clearTimer(id)
}

/**
 * Resume countdown when mouse leaves a toast
 * UiNotifications.vue calls _arm(t)
 */
function _arm(toast: Toast) {
  if (!toast) return
  const ms = toast._remaining ?? toast.duration ?? 0
  if (ms > 0) schedule(toast.id, ms)
}

export function useNotify() {
  return {
    state,
    push,
    info,
    success,
    warning,
    error,
    remove,
    _arm,
    _clear
  }
}
