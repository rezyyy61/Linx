import { ref } from "vue"

const requested = ref<Set<number | string>>(new Set())
const loading = ref<Set<number | string>>(new Set())

function cloneSet<T>(s: Set<T>) { return new Set(Array.from(s)) }

export function useFollowUiState() {
  function isRequested(id: number | string) { return requested.value.has(id) }
  function isLoading(id: number | string) { return loading.value.has(id) }
  function start(id: number | string) { const s = cloneSet(loading.value); s.add(id); loading.value = s }
  function succeed(id: number | string) {
    const l = cloneSet(loading.value); l.delete(id); loading.value = l
    const r = cloneSet(requested.value); r.add(id); requested.value = r
  }
  function fail(id: number | string) { const l = cloneSet(loading.value); l.delete(id); loading.value = l }
  function clearRequested(id: number | string) { const r = cloneSet(requested.value); r.delete(id); requested.value = r }
  return { isRequested, isLoading, start, succeed, fail, clearRequested }
}
