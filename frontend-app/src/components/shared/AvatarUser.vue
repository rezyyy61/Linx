<template>
  <div
    :class="[wrapperClass, zoomable && showImage ? 'cursor-zoom-in' : '']"
    :style="wrapperStyle"
    :aria-label="altText"
    :role="zoomable && showImage ? 'button' : undefined"
    :tabindex="zoomable && showImage ? 0 : undefined"
    @click="onClick"
    @keydown.enter.space.prevent="onClick"
  >
    <img
      v-if="showImage"
      :src="src!"
      :alt="altText"
      :class="imgClass"
      @error="onError"
    >
    <div
      v-else
      :class="fallbackClass"
    >
      <Icon
        v-if="useIcon"
        :icon="iconName"
        :width="iconPx"
        :height="iconPx"
      />
      <span v-else>{{ initials }}</span>
    </div>
    <slot />
  </div>

  <transition name="fade">
    <div
      v-if="lightbox && src"
      class="fixed inset-0 z-[1000] flex items-center justify-center p-4"
      :class="overlayClass"
      @click.self="close"
    >
      <div class="relative max-w-[90vw] max-h-[90vh]">
        <img
          :src="src"
          :alt="altText"
          class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl"
        >
        <button
          class="absolute -top-3 -right-3 rounded-full bg-white/90 dark:bg-black/70 backdrop-blur px-2 py-1 text-xs border dark:border-slate-700 shadow"
          @click.stop="close"
        >
          ✕
        </button>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from "vue"
import { Icon } from "@iconify/vue"

type SizeKey = "xs" | "sm" | "md" | "lg" | "xl" | "2xl"
type RoundedKey = "full" | "xl" | "lg" | "md" | "none"
type RingKey = "none" | "indigo" | "zinc" | "emerald" | "sky" | "rose"
type OverlayTone = "dark" | "light"

const props = defineProps<{
  src?: string | null
  name?: string | null
  color?: string | null
  size?: SizeKey
  rounded?: RoundedKey
  ring?: boolean
  ringColor?: RingKey
  fallback?: "initials" | "icon"
  icon?: string
  alt?: string
  zoomable?: boolean
  overlayTone?: OverlayTone
}>()

const broken = ref(false)
const size = computed<SizeKey>(() => props.size ?? "md")
const rounded = computed<RoundedKey>(() => props.rounded ?? "full")
const ringColor = computed<RingKey>(() => props.ringColor ?? "indigo")
const fallbackMode = computed(() => props.fallback ?? "initials")
const iconName = computed(() => props.icon ?? "mdi:account")
const zoomable = computed(() => !!props.zoomable)
const overlayTone = computed<OverlayTone>(() => props.overlayTone ?? "dark")

const sizeMap: Record<SizeKey, string> = { xs:"w-6 h-6", sm:"w-8 h-8", md:"w-10 h-10", lg:"w-12 h-12", xl:"w-16 h-16", "2xl":"w-20 h-20" }
const textMap: Record<SizeKey, string> = { xs:"text-[10px]", sm:"text-xs", md:"text-sm", lg:"text-base", xl:"text-xl", "2xl":"text-2xl" }
const iconMap: Record<SizeKey, number> = { xs:12, sm:14, md:16, lg:20, xl:24, "2xl":28 }
const roundedMap: Record<RoundedKey, string> = { full:"rounded-full", xl:"rounded-2xl", lg:"rounded-xl", md:"rounded-lg", none:"rounded-none" }
const ringMap: Record<RingKey, string> = {
  none:"", indigo:"ring-indigo-500 dark:ring-indigo-400", zinc:"ring-zinc-400 dark:ring-zinc-500",
  emerald:"ring-emerald-500 dark:ring-emerald-400", sky:"ring-sky-500 dark:ring-sky-400", rose:"ring-rose-500 dark:ring-rose-400"
}

const showImage = computed(() => !!props.src && !broken.value)
const altText = computed(() => props.alt ?? props.name ?? "AvatarUser")
const iconPx = computed(() => iconMap[size.value])

function normHex(c?: string | null) {
  if (!c) return null
  const v = c.trim()
  if (/^#([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v)) return v
  if (/^([0-9a-f]{3}|[0-9a-f]{6})$/i.test(v)) return "#" + v
  return null
}
function hexToRgb(hex: string) {
  const h = hex.replace("#","")
  const d = h.length === 3 ? h.split("").map(x=>x+x).join("") : h
  const n = parseInt(d,16)
  return { r:(n>>16)&255, g:(n>>8)&255, b:n&255 }
}
function contrastOn(hex: string) {
  const {r,g,b} = hexToRgb(hex)
  const yiq = (r*299 + g*587 + b*114)/1000
  return yiq >= 128 ? "#111827" : "#ffffff"
}

const bgHex = computed(() => normHex(props.color))
const textColor = computed(() => bgHex.value ? contrastOn(bgHex.value) : "#ffffff")

const initials = computed(() => {
  const n = (props.name ?? "").trim()
  if (!n) return "?"
  const parts = n.split(/[\s-]+/).filter(Boolean)
  const first = parts[0]?.[0] ?? ""
  const second = parts[1]?.[0] ?? ""
  return (first + second).toUpperCase()
})

const useIcon = computed(() => fallbackMode.value === "icon")

const wrapperClass = computed(() => [
  "inline-flex items-center justify-center overflow-hidden select-none",
  sizeMap[size.value],
  roundedMap[rounded.value],
  props.ring ? ["ring-2", ringMap[ringColor.value]] : "",
])

const wrapperStyle = computed(() => {
  if (!showImage.value) return { backgroundColor: bgHex.value ?? "" }
  return {}
})

const imgClass = computed(() => ["object-cover","w-full","h-full"])

const fallbackClass = computed(() => [
  "w-full h-full flex items-center justify-center",
  textMap[size.value],
  "font-semibold",
  roundedMap[rounded.value],
  bgHex.value ? "" : "bg-zinc-200 dark:bg-zinc-700",
  { color: textColor.value }
])

function onError() { broken.value = true }

/* Lightbox */
const lightbox = ref(false)
const overlayClass = computed(() =>
  overlayTone.value === "light"
    ? "bg-white/80 dark:bg-white/70"
    : "bg-black/70"
)

function onClick() {
  if (!zoomable.value || !showImage.value) return
  lightbox.value = true
}
function close() {
  lightbox.value = false
}
function onKey(e: KeyboardEvent) {
  if (e.key === 'Escape') close()
}

onMounted(() => window.addEventListener('keydown', onKey))
onBeforeUnmount(() => window.removeEventListener('keydown', onKey))
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity .15s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
