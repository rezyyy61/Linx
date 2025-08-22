import type { ApiErrorNormalized } from './types'

function pickMessage(obj: any): string | undefined {
  if (!obj) return
  if (typeof obj === 'string') return obj
  return (
    obj.message ??
    obj.error ??
    obj.title ??
    (Array.isArray(obj) ? obj.join(', ') : undefined)
  )
}

function pickFieldErrors(obj: any): Record<string, string[]> | undefined {
  if (!obj) return
  const cand =
    obj.errors ??
    obj.fieldErrors ??
    obj.data?.errors ??
    obj.error?.errors

  if (cand && typeof cand === 'object') return cand as Record<string, string[]>
  return undefined
}

export function normalizeApiError(err: unknown): ApiErrorNormalized {
  // Axios-like: err.response.data
  const axiosResp = (err as any)?.response
  if (axiosResp) {
    const data = axiosResp.data ?? {}
    const status = axiosResp.status
    const fieldErrors = pickFieldErrors(data)
    const message =
      pickMessage(data) ||
      pickMessage((err as any)) ||
      'Unexpected error'
    return { status, message, fieldErrors, raw: err }
  }

  // Fetch Response thrown
  const asResp = err as any
  if (asResp && typeof asResp === 'object' && 'status' in asResp && 'text' in asResp) {
    const status = (asResp.status as number) || undefined
    const message = pickMessage(asResp) || 'Request failed'
    return { status, message, raw: err }
  }

  if (typeof err === 'string') return { message: err }
  if (err && typeof err === 'object') {
    const message = pickMessage(err) || 'Unknown error'
    const fieldErrors = pickFieldErrors(err)
    const status = (err as any).status ?? (err as any).code
    return { status, message, fieldErrors, raw: err }
  }

  return { message: 'Unknown error', raw: err }
}
