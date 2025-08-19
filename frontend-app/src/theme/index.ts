export type Theme = "light" | "dark" | "system"
const KEY = "theme"

function systemDark() {
  return typeof window !== "undefined"
      && window.matchMedia?.("(prefers-color-scheme: dark)")?.matches
}

export function applyTheme(theme: Theme) {
  const html = document.documentElement
  const dark = theme === "dark" || (theme === "system" && systemDark())
  html.classList.toggle("dark", dark)
  localStorage.setItem(KEY, theme)
  const meta = document.querySelector('meta[name="color-scheme"]') as HTMLMetaElement | null
  if (meta) meta.content = dark ? "dark light" : "light dark"
}

export function getTheme(): Theme {
  return (localStorage.getItem(KEY) as Theme) || "light"
}

export function initTheme() {
  const saved = getTheme()
  applyTheme(saved)
  if (saved === "system" && window.matchMedia) {
    const mql = window.matchMedia("(prefers-color-scheme: dark)")
    const listener = () => applyTheme("system")
    try { mql.addEventListener("change", listener) } catch { mql.addListener(listener) }
  }
}
