export type ToastType = 'success' | 'error' | 'info' | 'warning'

export interface ToastAction {
  label: string
  onClick: () => void
}

export interface Toast {
  id: string
  type: ToastType
  title?: string
  description?: string
  duration?: number
  action?: ToastAction
  createdAt: number
  _remaining?: number
  _startedAt?: number
}

export interface NotifyState {
  toasts: Toast[]
}

export interface ApiErrorNormalized {
  status?: number
  message: string
  fieldErrors?: Record<string, string[]>
  raw?: unknown
}
