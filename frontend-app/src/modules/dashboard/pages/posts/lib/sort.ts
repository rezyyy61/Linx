export function stableReorder<T>(arr: T[], from: number, to: number) {
  const a = arr.slice()
  const [x] = a.splice(from, 1)
  a.splice(to, 0, x)
  return a
}

export function toOrderedMediaPayload(items: Array<{ id: number }>) {
  return items.map((m, i) => ({ id: Number(m.id), order: i }))
}
