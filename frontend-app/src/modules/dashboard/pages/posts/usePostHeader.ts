import { ref, computed, watch, onBeforeUnmount } from 'vue'

export type PostFilters = {
  q: string
  visibility: 'all'|'public'|'private'|'friends'
  status: 'all'|'draft'|'published'|'archived'
  dateFrom: string | null
  dateTo: string | null
  sort: 'newest'|'oldest'
}

const DEFAULTS: PostFilters = {
  q: '',
  visibility: 'all',
  status: 'all',
  dateFrom: null,
  dateTo: null,
  sort: 'newest',
}

function clampDateRange(from: string | null, to: string | null) {
  if (!from && !to) return { from: null, to: null }
  if (from && to && from > to) return { from: to, to: from }
  return { from: from ?? null, to: to ?? null }
}

export function usePostHeader(initial?: Partial<PostFilters>) {
  const init: PostFilters = { ...DEFAULTS, ...(initial ?? {}) }

  const q = ref<string>(init.q)
  const visibility = ref<PostFilters['visibility']>(init.visibility)
  const status = ref<PostFilters['status']>(init.status)
  const dateFrom = ref<string | null>(init.dateFrom)
  const dateTo = ref<string | null>(init.dateTo)
  const sort = ref<PostFilters['sort']>(init.sort)

  const normalizedRange = computed(() => clampDateRange(dateFrom.value, dateTo.value))

  const state = computed<PostFilters>(() => ({
    q: q.value,
    visibility: visibility.value,
    status: status.value,
    dateFrom: normalizedRange.value.from,
    dateTo: normalizedRange.value.to,
    sort: sort.value,
  }))

  const hasActiveFilters = computed(() => {
    const trimmed = (q.value || '').trim()
    return (
      trimmed.length > 0 ||
      visibility.value !== DEFAULTS.visibility ||
      status.value !== DEFAULTS.status ||
      !!normalizedRange.value.from ||
      !!normalizedRange.value.to ||
      sort.value !== DEFAULTS.sort
    )
  })

  function reset(target: 'defaults' | 'initial' = 'defaults') {
    const base = target === 'initial' ? init : DEFAULTS
    q.value = base.q
    visibility.value = base.visibility
    status.value = base.status
    dateFrom.value = base.dateFrom
    dateTo.value = base.dateTo
    sort.value = base.sort
  }

  function set(partial: Partial<PostFilters>) {
    if ('q' in partial && typeof partial.q === 'string') q.value = partial.q
    if ('visibility' in partial && partial.visibility) visibility.value = partial.visibility
    if ('status' in partial && partial.status) status.value = partial.status
    if ('dateFrom' in partial) dateFrom.value = partial.dateFrom ?? null
    if ('dateTo' in partial) dateTo.value = partial.dateTo ?? null
    if ('sort' in partial && partial.sort) sort.value = partial.sort
  }

  function setDateRange(from: string | null, to: string | null) {
    const r = clampDateRange(from, to)
    dateFrom.value = r.from
    dateTo.value = r.to
  }

  function toApiParams() {
    const p: Record<string, any> = {}
    const qv = (q.value || '').trim()
    const { from, to } = normalizedRange.value
    if (qv) p.q = qv
    if (visibility.value !== 'all') p.visibility = visibility.value
    if (status.value !== 'all') p.status = status.value
    if (from) p.date_from = from
    if (to) p.date_to = to
    p.sort = sort.value
    return p
  }

  // سازگاری با نسخهٔ قبلی: فقط سرچ debounce شود
  const qDebouncedTimer = ref<ReturnType<typeof setTimeout> | null>(null)
  function cancelQueryDebounce() {
    if (qDebouncedTimer.value) {
      clearTimeout(qDebouncedTimer.value)
      qDebouncedTimer.value = null
    }
  }
  function onQueryChange(cb: (params: Record<string, any>) => void, delay = 350) {
    watch(q, () => {
      cancelQueryDebounce()
      qDebouncedTimer.value = setTimeout(() => {
        cb(toApiParams())
        qDebouncedTimer.value = null
      }, delay)
    })
  }
  onBeforeUnmount(cancelQueryDebounce)

  // واچر عمومی روی همهٔ فیلترها (تغییرات غیر از q بلافاصله فایر می‌شوند)
  function watchFilters(cb: (params: Record<string, any>) => void, opts?: { debounceMs?: number; immediate?: boolean }) {
    const debounceMs = opts?.debounceMs ?? 350
    const immediate = opts?.immediate ?? false
    let timer: ReturnType<typeof setTimeout> | null = null

    const run = (debounce = false) => {
      if (debounce) {
        if (timer) clearTimeout(timer)
        timer = setTimeout(() => {
          cb(toApiParams())
          timer = null
        }, debounceMs)
      } else {
        if (timer) { clearTimeout(timer); timer = null }
        cb(toApiParams())
      }
    }

    const stop1 = watch(q, () => run(true), { immediate })
    const stop2 = watch([visibility, status, dateFrom, dateTo, sort], () => run(false), { immediate })

    const stop = () => {
      if (timer) { clearTimeout(timer); timer = null }
      stop1(); stop2()
    }
    return stop
  }

  function apply(cb: (params: Record<string, any>) => void) {
    cb(toApiParams())
  }

  return {
    // refs
    q, visibility, status, dateFrom, dateTo, sort,
    // computed
    state, hasActiveFilters,
    // actions
    reset, set, setDateRange, toApiParams, apply,
    // watchers
    onQueryChange, cancelQueryDebounce, watchFilters,
  }
}
