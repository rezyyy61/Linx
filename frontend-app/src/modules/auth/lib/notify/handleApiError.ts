import { normalizeApiError } from './api-error'
import { useNotify } from './useNotify'
import type { ApiErrorNormalized } from './types'

type Options = {
  setFieldErrors?: (errs: Record<string, string[]>) => void
  t?: (k: string) => string
  notifyType?: 'toast' | 'none'
  log?: boolean
}

const TECH_RE =
  /(exception|error|stack|typeerror|referenceerror|rangeerror|axioserror|econn|etimedout|eai_again|sql|syntaxerror|cannot read property|undefined|null|<[^>]+>)/i

function isTechnical(msg?: string) {
  if (!msg) return false
  if (msg.length > 160) return true
  return TECH_RE.test(msg)
}

export function handleApiError(err: unknown, opts: Options = {}) {
  const parsed: ApiErrorNormalized = normalizeApiError(err)

  if (opts.log !== false && typeof window !== 'undefined' && (import.meta as any)?.env?.DEV) {
     
    console.error('[API ERROR]', parsed, err)
  }

  if (parsed.fieldErrors && opts.setFieldErrors) {
    opts.setFieldErrors(parsed.fieldErrors)
  }

  if (opts.notifyType === 'none') return parsed

  const t = opts.t ?? ((s: string) => s)
  const status = parsed.status ?? 0
  const hasFields = !!parsed.fieldErrors && Object.keys(parsed.fieldErrors).length > 0
  const network = status === 0 || /network/i.test(`${parsed.message || ''}${(err as any)?.message || ''}`)

  let title: string
  let description: string | undefined

  if (hasFields) {
    title = t('common.validationError') || 'Validation error'
    description = t('common.validationErrorDesc') || 'Please review the highlighted fields.'
  } else if (network) {
    title = t('common.networkError') || 'Network error'
    description = t('common.networkErrorDesc') || 'Please check your connection and try again.'
  } else if (status >= 500) {
    title = t('common.serverError') || 'Server error'
    description = t('common.serverErrorDesc') || 'Something went wrong on our side. Please try again later.'
  } else {
    title = t('common.error') || 'Error'
    description = isTechnical(parsed.message) ? undefined : parsed.message
  }

  const { error: pushError } = useNotify()
  pushError({ title, description, duration: 6000 })

  return parsed
}
