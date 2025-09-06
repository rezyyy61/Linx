export function useDebounce<T extends (...args: any[]) => any>(fn: T, delay = 300) {
  let t: any
  return (...args: Parameters<T>) => {
    clearTimeout(t)
    t = setTimeout(() => fn(...args), delay)
  }
}
