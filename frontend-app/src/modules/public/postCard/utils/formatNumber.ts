export function formatNumber(n: number | undefined | null) {
  const v = typeof n === 'number' ? n : 0
  if (v < 1000) return `${v}`
  if (v < 10000) return `${(v / 1000).toFixed(1)}k`.replace('.0', '')
  if (v < 1000000) return `${Math.round(v / 1000)}k`
  if (v < 10000000) return `${(v / 1000000).toFixed(1)}M`.replace('.0', '')
  return `${Math.round(v / 1000000)}M`
}
