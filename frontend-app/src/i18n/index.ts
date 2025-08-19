import { createI18n } from "vue-i18n"
import en from "./locales/en.json"
import fa from "./locales/fa.json"
import ku from "./locales/ku.json"

const rtl = new Set(["fa","ku","ckb","ar","he"])
const saved = localStorage.getItem("lang") || "en"

const i18n = createI18n({
  legacy: false,
  locale: saved,
  fallbackLocale: "en",
  messages: { en, fa, ku },
})

export function setLocale(lang: string) {
  i18n.global.locale.value = lang as any
  localStorage.setItem("lang", lang)
  const html = document.documentElement
  html.setAttribute("lang", lang)
  html.setAttribute("dir", rtl.has(lang) ? "rtl" : "ltr")
}

// مهم: همین الان مقدار ذخیره‌شده را اعمال کن
setLocale(saved)

export default i18n
