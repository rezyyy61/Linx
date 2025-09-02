import { ref } from 'vue'

export function useAsync<T extends (...args: any[]) => Promise<any>>(fn: T) {
  const loading = ref(false)
  const error = ref<any>(null)
  const run = async (...args: Parameters<T>): Promise<Awaited<ReturnType<T>>> => {
    loading.value = true
    error.value = null
    try { return await fn(...args) } catch (e) { error.value = e; throw e } finally { loading.value = false }
  }
  return { loading, error, run }
}
