export const HASHTAG_RE = /#([\p{L}\d_]{2,50})/gu

export function hashtagHref(tag: string) {
  return `/tag/${encodeURIComponent(tag)}`
}
