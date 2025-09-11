export const MENTION_RE = /@([A-Za-z0-9_]{2,30})/g

export function mentionHref(username: string) {
  return `/u/${encodeURIComponent(username)}`
}
