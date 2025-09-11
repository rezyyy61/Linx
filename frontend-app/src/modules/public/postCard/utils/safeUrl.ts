export function safeUrl(url: string) {
  try {
    const u = new URL(url)
    return u.toString()
  } catch {
    return 'about:blank'
  }
}
