export function canWebShare() {
  return typeof navigator !== 'undefined' && !!(navigator as any).share
}

export async function webShare(payload: { title?: string; text?: string; url: string }) {
  if (!canWebShare()) throw new Error('not-supported')
  await (navigator as any).share(payload)
}

export async function copyToClipboard(text: string) {
  if (navigator.clipboard && window.isSecureContext) {
    await navigator.clipboard.writeText(text)
    return
  }
  const ta = document.createElement('textarea')
  ta.value = text
  ta.style.position = 'fixed'
  ta.style.opacity = '0'
  document.body.appendChild(ta)
  ta.select()
  document.execCommand('copy')
  document.body.removeChild(ta)
}

export function providerUrl(provider: 'twitter' | 'telegram' | 'whatsapp' | 'linkedin', url: string, text?: string) {
  const u = encodeURIComponent(url)
  const t = encodeURIComponent(text || '')
  if (provider === 'twitter') return `https://twitter.com/intent/tweet?url=${u}&text=${t}`
  if (provider === 'telegram') return `https://t.me/share/url?url=${u}&text=${t}`
  if (provider === 'whatsapp') return `https://wa.me/?text=${t ? t + '%20' : ''}${u}`
  return `https://www.linkedin.com/sharing/share-offsite/?url=${u}`
}

export function openWindow(url: string) {
  const w = 700
  const h = 600
  const y = window.top ? (window.top.outerHeight - h) / 2 : 100
  const x = window.top ? (window.top.outerWidth - w) / 2 : 100
  window.open(url, '_blank', `toolbar=0,location=0,status=0,menubar=0,scrollbars=1,resizable=1,width=${w},height=${h},left=${x},top=${y}`)
}
