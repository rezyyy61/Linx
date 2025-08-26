export type HtmlToTextOptions = {
  preserveLineBreaks?: boolean
  maxNewlines?: number
}
export type ExcerptOptions = {
  preserveWords?: boolean
  suffix?: string
  preserveLineBreaks?: boolean
  maxNewlines?: number
}
const BLOCK_TAGS = new Set(['P','DIV','LI','UL','OL','H1','H2','H3','H4','H5','H6','BLOCKQUOTE'])
const RTL_RE = /[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF]/
const ALLOWED_TAGS = new Set(['P','BR','STRONG','EM','U','S','A','BLOCKQUOTE','UL','OL','LI','H1','H2','H3','H4','H5','H6','SPAN'])
const SAFE_CLASS_RE = /^(ql-(align|indent|direction)-\w+)$/
function hasDOM(): boolean { return typeof window !== 'undefined' && typeof document !== 'undefined' }
function decodeEntities(text: string): string {
  if (!hasDOM()) return text.replace(/&nbsp;/g,' ')
  const d = document.createElement('textarea')
  d.innerHTML = text
  return d.value
}
function collapseWhitespace(s: string): string {
  return s.replace(/\u00A0/g,' ').replace(/[ \t\f\v]+/g,' ').replace(/ *\n+ */g,'\n').trim()
}
function limitNewlines(s: string, max: number): string {
  if (max == null) return s
  let lines = s.split('\n')
  let kept = 0
  for (let i=0;i<lines.length;i++){
    if (lines[i]==='') kept++
    else kept=0
    if (kept>max) lines[i]=' '
  }
  return collapseWhitespace(lines.join('\n'))
}
function walkText(node: Node, out: string[]): void {
  if (node.nodeType === 3) { out.push((node as Text).nodeValue || ''); return }
  if (node.nodeType !== 1) return
  const el = node as HTMLElement
  const name = el.tagName
  if (name === 'BR') { out.push('\n'); return }
  const isBlock = BLOCK_TAGS.has(name)
  if (isBlock) out.push('\n')
  for (let i=0;i<el.childNodes.length;i++) walkText(el.childNodes[i], out)
  if (isBlock) out.push('\n')
}
export function htmlToText(html: string, opts: HtmlToTextOptions = {}): string {
  const { preserveLineBreaks = false, maxNewlines } = opts
  if (!html) return ''
  if (!hasDOM()) {
    let t = html.replace(/<br\s*\/?>/gi, '\n').replace(/<\/(p|div|li|h\d|blockquote)>/gi, '\n').replace(/<[^>]+>/g, '')
    t = decodeEntities(t)
    t = preserveLineBreaks ? collapseWhitespace(t) : collapseWhitespace(t.replace(/\n+/g,' '))
    return limitNewlines(t, maxNewlines ?? (preserveLineBreaks ? 2 : 0))
  }
  const c = document.createElement('div')
  c.innerHTML = html
  const out: string[] = []
  walkText(c, out)
  let text = out.join('')
  text = collapseWhitespace(text)
  text = preserveLineBreaks ? text : text.replace(/\n+/g,' ')
  return limitNewlines(text, maxNewlines ?? (preserveLineBreaks ? 2 : 0))
}
function safeUrl(href: string | null | undefined): string | null {
  if (!href) return null
  try {
    const u = new URL(href, 'http://x')
    const p = u.protocol.toLowerCase()
    if (p === 'http:' || p === 'https:' || p === 'mailto:' || p === 'tel:') return href
    return null
  } catch { return null }
}
export function sanitizeForDisplay(html: string): string {
  if (!html) return ''
  if (!hasDOM()) return html
  const parser = new DOMParser()
  const doc = parser.parseFromString(`<div>${html}</div>`, 'text/html')
  const root = doc.body.firstElementChild as HTMLElement
  function clean(node: Node): Node | null {
    if (node.nodeType === 3) return doc.createTextNode((node as Text).nodeValue || '')
    if (node.nodeType !== 1) return null
    const el = node as HTMLElement
    const name = el.tagName
    if (!ALLOWED_TAGS.has(name)) {
      const frag = doc.createDocumentFragment()
      for (let i=0;i<el.childNodes.length;i++) {
        const c = clean(el.childNodes[i])
        if (c) frag.appendChild(c)
      }
      return frag
    }
    const out = doc.createElement(name.toLowerCase())
    if (name === 'A') {
      const href = safeUrl(el.getAttribute('href'))
      if (href) {
        out.setAttribute('href', href)
        out.setAttribute('rel', 'nofollow noopener noreferrer')
        out.setAttribute('target', '_blank')
      }
    }
    if (el.hasAttribute('dir')) out.setAttribute('dir', el.getAttribute('dir') || '')
    const cls = (el.getAttribute('class') || '').split(/\s+/).filter(c => SAFE_CLASS_RE.test(c))
    if (cls.length) out.setAttribute('class', cls.join(' '))
    for (let i=0;i<el.childNodes.length;i++) {
      const c = clean(el.childNodes[i])
      if (c) out.appendChild(c)
    }
    return out
  }
  const cleaned = clean(root)
  const wrap = doc.createElement('div')
  if (cleaned) wrap.appendChild(cleaned)
  return wrap.innerHTML
}
export function excerptFromHtml(html: string, maxChars: number, opts: ExcerptOptions = {}): string {
  const { preserveWords = true, suffix = '…', preserveLineBreaks = false, maxNewlines } = opts
  const text = htmlToText(html, { preserveLineBreaks, maxNewlines })
  if (text.length <= maxChars) return text
  if (!preserveWords) return text.slice(0, maxChars).trimEnd() + suffix
  const slice = text.slice(0, maxChars + 10)
  const idx = slice.lastIndexOf(/[\s.,!?،؛]/.test(slice.charAt(maxChars)) ? slice.charAt(maxChars) : ' ')
  const cut = idx > 40 ? slice.slice(0, idx) : slice.slice(0, maxChars)
  return cut.trimEnd() + suffix
}
export function isEmptyHtml(html: string): boolean {
  const t = htmlToText(html, { preserveLineBreaks: false })
  return t.length === 0
}
export function directionFor(input: string): 'rtl' | 'ltr' {
  const t = /<[^>]+>/.test(input) ? htmlToText(input) : String(input || '')
  return RTL_RE.test(t) ? 'rtl' : 'ltr'
}
export function charCount(html: string): number {
  return htmlToText(html, { preserveLineBreaks: false }).length
}
