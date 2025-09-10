import { useToastStore } from "./store"
import type { ToastType } from "./types"

export function useToast() {
  const store = useToastStore()

  function show(p: { message: string; title?: string; type?: ToastType; duration?: number; actionLabel?: string; onAction?: () => void }) {
    return store.push(p)
  }
  function of(type: ToastType) {
    return (message: string, opt?: { title?: string; duration?: number; actionLabel?: string; onAction?: () => void }) =>
      store.push({ type, message, ...opt })
  }
  function confirm(message: string, opt: {
    title?: string
    confirmLabel?: string
    cancelLabel?: string
    destructive?: boolean
    duration?: number
    onConfirm: () => void | Promise<void>
    onCancel?: () => void
  }) {
    return store.pushConfirm({ message, ...opt })
  }

  return {
    show,
    success: of("success"),
    info: of("info"),
    warning: of("warning"),
    error: of("error"),
    confirm,
    remove: store.remove,
    clear: store.clear
  }
}
