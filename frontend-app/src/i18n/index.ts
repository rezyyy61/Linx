// /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/i18n/index.ts
import { createI18n } from "vue-i18n";

type Msgs = Record<string, any>;

function deepMerge(target: any, source: any) {
  for (const k of Object.keys(source)) {
    const sv = source[k];
    if (sv && typeof sv === "object" && !Array.isArray(sv)) {
      target[k] = deepMerge(target[k] || {}, sv);
    } else {
      target[k] = sv;
    }
  }
  return target;
}

const modules = import.meta.glob("./locales/**/*.json", { eager: true });
const messages: Msgs = {};

for (const path in modules) {
  const mod: any = (modules as any)[path];
  const data = mod.default ?? mod;
  const m = path.match(/locales\/([a-zA-Z-_]+)(?:\/|\.json)/);
  if (!m) continue;
  const locale = m[1];
  messages[locale] = deepMerge(messages[locale] || {}, data);
}

const rtl = new Set(["fa", "ku", "ckb", "ar", "he"]);
const saved = localStorage.getItem("lang") || "en";

const i18n = createI18n({
  legacy: false,
  locale: saved,
  fallbackLocale: "en",
  messages,
});

export function setLocale(lang: string) {
  i18n.global.locale.value = lang as any;
  localStorage.setItem("lang", lang);
  const html = document.documentElement;
  html.setAttribute("lang", lang);
  html.setAttribute("dir", rtl.has(lang) ? "rtl" : "ltr");
}

setLocale(saved);

export default i18n;
