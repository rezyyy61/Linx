<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from "vue"
import { useI18n } from "vue-i18n"
import i18n, { setLocale } from "@/i18n"
import { applyTheme, getTheme, type Theme } from "@/theme"
import { Icon } from "@iconify/vue"

const { t } = useI18n()
const open = ref(false)
const theme = ref<Theme>("light")
const lang = ref<string>(i18n.global.locale.value as string)
const root = ref<HTMLElement | null>(null)

function toggle() { open.value = !open.value }
function setThemeValue(v: Theme) { theme.value = v; applyTheme(v); }
function setLangValue(v: string) { lang.value = v; setLocale(v); }
function onClickOutside(e: MouseEvent) { if (root.value && !root.value.contains(e.target as Node)) open.value = false }

onMounted(() => { theme.value = getTheme(); document.addEventListener("click", onClickOutside) })
onBeforeUnmount(() => { document.removeEventListener("click", onClickOutside) })
</script>

<template>
  <div ref="root" class="relative">
    <button @click="toggle" class="inline-flex h-10 w-10 items-center justify-center rounded-md border border-gray-300 bg-white hover:bg-gray-50 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:bg-zinc-800">
      <Icon icon="mdi:tune-variant" class="h-5 w-5 text-gray-700 dark:text-zinc-200" />
    </button>

    <div v-show="open" class="absolute right-0 mt-2 w-64 rounded-lg border border-gray-200 bg-white p-3 shadow-card dark:border-zinc-700 dark:bg-zinc-900">
      <div class="text-sm font-semibold text-gray-900 dark:text-zinc-100">{{ t("prefs.title") }}</div>
      <div class="mt-3 space-y-3">
        <div class="grid grid-cols-3 gap-2">
          <button @click="setThemeValue('light')" :class="['px-3 py-2 rounded-md text-sm border inline-flex items-center justify-center gap-2', theme==='light' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">
            <Icon icon="mdi:weather-sunny" class="h-4 w-4" /> Light
          </button>
          <button @click="setThemeValue('dark')" :class="['px-3 py-2 rounded-md text-sm border inline-flex items-center justify-center gap-2', theme==='dark' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">
            <Icon icon="mdi:weather-night" class="h-4 w-4" /> Dark
          </button>
          <button @click="setThemeValue('system')" :class="['px-3 py-2 rounded-md text-sm border inline-flex items-center justify-center gap-2', theme==='system' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">
            <Icon icon="mdi:monitor" class="h-4 w-4" /> System
          </button>
        </div>

        <div class="grid grid-cols-3 gap-2">
          <button @click="setLangValue('en')" :class="['px-3 py-2 rounded-md text-sm border', lang==='en' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">EN</button>
          <button @click="setLangValue('fa')" :class="['px-3 py-2 rounded-md text-sm border', lang==='fa' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">FA</button>
          <button @click="setLangValue('ku')" :class="['px-3 py-2 rounded-md text-sm border', lang==='ku' ? 'border-red-600 text-red-600' : 'border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-zinc-700 dark:text-zinc-200 dark:hover:bg-zinc-800']">KU</button>
        </div>
      </div>
    </div>
  </div>
</template>
