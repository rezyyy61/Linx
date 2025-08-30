export function normalizeApiError(e: any) {
  const status = e?.response?.status ?? 0
  const data = e?.response?.data ?? {}
  const message = data?.message ?? e?.message ?? 'error'
  const errors = data?.errors ?? null
  return { status, message, errors }
}

export function pickFieldError(errors: Record<string, string[] | string> | null | undefined, field: string) {
  if (!errors) return null
  const v = (errors as any)[field]
  if (!v) return null
  return Array.isArray(v) ? v[0] : String(v)
}
