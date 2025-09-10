export type ToastType = "success" | "info" | "warning" | "error" | "confirm"

export type ToastItem = {
  id: number
  type: ToastType
  message: string
  title?: string
  duration?: number
  actionLabel?: string
  onAction?: () => void
  confirmLabel?: string
  cancelLabel?: string
  onConfirm?: () => void | Promise<void>
  onCancel?: () => void
  destructive?: boolean
}
