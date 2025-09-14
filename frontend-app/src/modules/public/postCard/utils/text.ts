// utils/text.ts
export function normalizeNewlines(
  input: string,
  maxConsecutive = 2,
  maxTotalBreaks = 40
): string {
  if (!input) return ''
  let s = input.replace(/\r\n/g, '\n')
  const re = new RegExp(`\\n{${maxConsecutive + 1},}`, 'g')
  s = s.replace(re, '\n'.repeat(maxConsecutive))
  const lines = s.split('\n')
  let breaks = 0
  for (let i = 0; i < lines.length - 1; i++) {
    if (lines[i] === '') breaks++
    if (breaks > maxTotalBreaks) {
      s = lines.slice(0, i + 1).join('\n')
      break
    }
  }
  return s.trimEnd()
}
