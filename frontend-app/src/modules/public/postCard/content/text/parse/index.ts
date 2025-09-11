import { URL_RE, normalizeUrl } from './linkify'
import { MENTION_RE, mentionHref } from './mentions'
import { HASHTAG_RE, hashtagHref } from './hashtags'

function escapeHtml(s: string) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;')
}

const ALL_RE = new RegExp(`${URL_RE.source}|${MENTION_RE.source}|${HASHTAG_RE.source}`, 'gu')

const URL_ONLY_RE = new RegExp(`^(?:${URL_RE.source})$`, 'iu')
const MENTION_ONLY_RE = /^@([A-Za-z0-9_]{2,30})$/
const HASHTAG_ONLY_RE = /^#([\p{L}\d_]{2,50})$/u

export function parseRichText(input: string) {
  const text = input || ''
  let res = ''
  let last = 0

  for (const m of text.matchAll(ALL_RE)) {
    const i = m.index ?? 0
    if (i > last) res += escapeHtml(text.slice(last, i))

    const token = m[0]

    if (URL_ONLY_RE.test(token)) {
      const { href, display, trail } = normalizeUrl(token)
      res += `<a data-entity="link" dir="ltr" href="${escapeHtml(href)}" target="_blank" rel="noopener nofollow ugc">${escapeHtml(display)}</a>${escapeHtml(trail)}`
    } else if (MENTION_ONLY_RE.test(token)) {
      const u = token.slice(1)
      res += `<a data-entity="mention" href="${mentionHref(u)}">@${escapeHtml(u)}</a>`
    } else if (HASHTAG_ONLY_RE.test(token)) {
      const h = token.slice(1)
      res += `<a data-entity="hashtag" href="${hashtagHref(h)}">#${escapeHtml(h)}</a>`
    } else {
      res += escapeHtml(token)
    }


    last = i + token.length
  }

  if (last < text.length) res += escapeHtml(text.slice(last))
  return res.replace(/\n/g, '<br/>')
}
