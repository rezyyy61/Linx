import { onBeforeUnmount, watch, unref, ref } from 'vue'
import { onBeforeRouteLeave } from 'vue-router'

function toPlain(input: any): any {
  const v = unref(input)
  if (v === null || typeof v !== 'object') return v
  if (Array.isArray(v)) return v.map(toPlain)
  const out: any = {}
  for (const k of Object.keys(v)) out[k] = toPlain(v[k])
  return out
}

function stableStringify(input: any) {
  const v = toPlain(input)
  try { return JSON.stringify(v) } catch { return String(v) }
}

export function useUnsavedChangesGuard(dirty: { value: boolean }) {
  const onBeforeUnload = (e: BeforeUnloadEvent) => {
    if (!dirty.value) return
    e.preventDefault()
    e.returnValue = ''
  }
  window.addEventListener('beforeunload', onBeforeUnload)
  onBeforeRouteLeave((_to, _from, next) => {
    if (!dirty.value) return next()
    if (window.confirm('You have unsaved changes. Leave?')) next()
    else next(false)
  })
  onBeforeUnmount(() => window.removeEventListener('beforeunload', onBeforeUnload))
}

export function useDirtyFlag<T>(getter: () => T) {
  const init = stableStringify(getter())
  const dirty = ref(false)
  const stop = watch(() => getter(), v => {
    dirty.value = stableStringify(v) !== init
  }, { deep: true })
  onBeforeUnmount(() => stop())
  return dirty
}
