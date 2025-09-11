export const URL_RE = /(https?:\/\/[^\s]+|www\.[^\s]+)/gu

export function normalizeUrl(raw: string) {
  const trimmed = raw.replace(/[),.;!?]+$/, '')
  const trail = raw.slice(trimmed.length)
  const href = trimmed.startsWith('http') ? trimmed : `https://${trimmed}`
  return { href, display: trimmed, trail }
}
