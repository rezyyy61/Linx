// src/theme.ts
export type Theme = "light" | "dark" | "system";
const KEY = "theme";

let mql: MediaQueryList | null = null;
let mqlListener: ((this: MediaQueryList, e: MediaQueryListEvent) => any) | null = null;

function systemDark() {
  return (
    typeof window !== "undefined" &&
    typeof window.matchMedia === "function" &&
    window.matchMedia("(prefers-color-scheme: dark)").matches
  );
}

function setMeta(dark: boolean) {
  const meta = document.querySelector('meta[name="color-scheme"]') as HTMLMetaElement | null;
  if (meta) meta.content = dark ? "dark light" : "light dark";
}

export function getTheme(): Theme {
  if (typeof window === "undefined") return "light";
  const t = (localStorage.getItem(KEY) as Theme) || "light";
  return t;
}

export function applyTheme(theme: Theme) {
  if (typeof document === "undefined") return;

  // قطع listener قبلی
  if (mql && mqlListener) {
    try { mql.removeEventListener("change", mqlListener); }
    catch { mql.removeListener(mqlListener); }
    mqlListener = null;
  }

  const html = document.documentElement;
  const dark = theme === "dark" || (theme === "system" && systemDark());
  html.classList.toggle("dark", dark);
  setMeta(dark);
  try { localStorage.setItem(KEY, theme); } catch {
    // intentionally ignore: response body may not be JSON; keep default msg
  }

  if (theme === "system" && typeof window !== "undefined" && window.matchMedia) {
    mql = window.matchMedia("(prefers-color-scheme: dark)");
    mqlListener = () => {
      const saved = (localStorage.getItem(KEY) as Theme) || "light";
      const isDark = saved === "dark" || (saved === "system" && mql!.matches);
      html.classList.toggle("dark", isDark);
      setMeta(isDark);
    };
    try { mql.addEventListener("change", mqlListener); }
    catch { mql.addListener(mqlListener); }
  }
}

export function initTheme() {
  if (typeof window === "undefined") return;
  applyTheme(getTheme());
}

