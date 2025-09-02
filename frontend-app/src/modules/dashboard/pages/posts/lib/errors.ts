export type ApiFieldErrors = Record<string, string[] | string> | null | undefined

export function normalizeApiError(e: any) {
  const status = e?.response?.status ?? 0
  const data = e?.response?.data ?? {}
  const message = data?.message ?? e?.message ?? 'error'
  const errors = (data?.errors ?? null) as ApiFieldErrors
  return { status, message, errors }
}

export function pickFieldError(errors: ApiFieldErrors, field: string) {
  if (!errors) return null
  const v = (errors as any)[field]
  if (!v) return null
  if (Array.isArray(v)) return v[0] ?? null
  return typeof v === 'string' ? v : String(v)
}
