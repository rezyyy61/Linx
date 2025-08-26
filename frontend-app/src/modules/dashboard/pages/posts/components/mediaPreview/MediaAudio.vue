<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watch, computed } from 'vue'
import WaveSurfer from 'wavesurfer.js'

const props = withDefaults(defineProps<{
  src: string
  height?: number
  accent?: string
  wave?: string
  bg?: string
}>(), { height: 76, accent: 'rgba(16,185,129,1)', wave: 'rgba(148,163,184,0.55)', bg: 'rgba(17,24,39,1)' })

const emit = defineEmits<{ ready: []; play: []; pause: []; ended: []; error: [err: unknown] }>()
const root = ref<HTMLElement|null>(null)
const wrap = ref<HTMLDivElement|null>(null)
let ws: WaveSurfer | null = null

const loading = ref(true)
const ready = ref(false)
const playing = ref(false)
const cur = ref(0)
const dur = ref(0)
const muted = ref(false)
const vol = ref(0.9)
const pct = computed(() => dur.value ? Math.min(100, Math.max(0, (cur.value / dur.value) * 100)) : 0)
const fmt = (s: number) => { const m = Math.floor(s / 60); const sec = Math.floor(s % 60); return `${m}:${sec < 10 ? '0' : ''}${sec}` }
const canInteract = computed(() => ready.value && !loading.value)

const init = async () => {
  if (!wrap.value) return
  ws = WaveSurfer.create({
    container: wrap.value,
    height: props.height - 24,
    waveColor: props.wave,
    progressColor: props.accent,
    cursorColor: props.accent,
    barWidth: 2,
    barGap: 1.5,
    normalize: true,
    interact: true,
    fillParent: true,
  })
  ws.on('ready', () => { ready.value = true; loading.value = false; dur.value = ws!.getDuration(); emit('ready') })
  ws.on('decode', () => { dur.value = ws!.getDuration() })
  ws.on('play', () => { playing.value = true; emit('play') })
  ws.on('pause', () => { playing.value = false; emit('pause') })
  ws.on('finish', () => { playing.value = false; cur.value = dur.value; emit('ended') })
  ws.on('timeupdate', (t: number) => { cur.value = t })
  ws.on('audioprocess', () => { cur.value = ws!.getCurrentTime() })
  ws.on('error', (err) => { loading.value = false; ready.value = false; emit('error', err) })
  try {
    loading.value = true
    await ws.load(props.src)
    ws.setVolume(vol.value)
    ws.setMuted(muted.value)
  } catch (e) {
    loading.value = false
    emit('error', e)
  }
}
const destroy = () => { try { ws?.destroy() } catch { /* empty */ } ws = null }

const toggle = () => { if (!ws || !canInteract.value) return; playing.value ? ws.pause() : ws.play() }
const seek = (e: Event) => { if (!ws || !dur.value) return; const v = Number((e.target as HTMLInputElement).value); ws.seekTo(v / 100) }
const setVolume = (v: number) => { vol.value = Math.max(0, Math.min(1, v)); if (ws) ws.setVolume(vol.value) }
const toggleMute = () => { muted.value = !muted.value; if (ws) ws.setMuted(muted.value) }
const onKey = (ev: KeyboardEvent) => { if (!root.value) return; const inside = root.value.contains(document.activeElement); if (!inside) return; if (ev.code === 'Space') { ev.preventDefault(); toggle() } }

onMounted(() => { init(); window.addEventListener('keydown', onKey) })
onBeforeUnmount(() => { window.removeEventListener('keydown', onKey); destroy() })

watch(() => props.src, async () => {
  destroy()
  ready.value = false
  playing.value = false
  cur.value = 0
  dur.value = 0
  loading.value = true
  await Promise.resolve()
  init()
})
</script>

<template>
  <div
    ref="root"
    class="rounded-2xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-900"
  >
    <div class="flex items-stretch gap-0">
      <div class="p-3 flex items-center">
        <button
          type="button"
          class="inline-grid place-items-center w-11 h-11 rounded-full text-white focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50"
          :style="{ backgroundColor: canInteract ? 'rgb(16 185 129)' : 'rgb(209 213 219)' }"
          :disabled="!canInteract"
          aria-label="Play/Pause"
          @click="toggle"
        >
          <span
            class="iconify w-6 h-6"
            :data-icon="playing ? 'mdi:pause' : 'mdi:play'"
          />
        </button>
      </div>

      <div class="flex-1 py-3 pr-3">
        <div
          class="rounded-xl overflow-hidden border border-gray-100 dark:border-gray-800"
          :style="{ backgroundColor: props.bg }"
        >
          <div
            ref="wrap"
            class="w-full"
          />
          <div class="h-0.5 w-full bg-gray-200/30 dark:bg-white/10">
            <div
              class="h-0.5"
              :style="{ width: pct + '%', backgroundColor: 'rgb(16 185 129)' }"
            />
          </div>
        </div>

        <div class="mt-2 flex items-center gap-3">
          <input
            type="range"
            min="0"
            max="100"
            step="0.1"
            class="w-full accent-emerald-600"
            :value="pct"
            :disabled="!canInteract"
            @input="seek"
          >
          <div class="text-[11px] text-gray-500 dark:text-gray-400 w-24 text-right tabular-nums">
            {{ fmt(cur) }} / {{ fmt(dur) }}
          </div>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="inline-grid place-items-center w-8 h-8 rounded-lg bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200"
              :aria-pressed="muted"
              @click="toggleMute"
            >
              <span
                class="iconify w-4.5 h-4.5"
                :data-icon="muted ? 'mdi:volume-mute' : (vol<=0.33 ? 'mdi:volume-low' : vol<=0.66 ? 'mdi:volume-medium' : 'mdi:volume-high')"
              />
            </button>
            <input
              type="range"
              min="0"
              max="1"
              step="0.02"
              class="w-24 accent-emerald-600"
              :value="vol"
              :disabled="!canInteract"
              @input="setVolume(Number(($event.target as HTMLInputElement).value))"
            >
          </div>
        </div>
      </div>
    </div>

    <div
      v-if="loading"
      class="px-3 pb-3"
    >
      <div class="h-2 rounded bg-gray-100 animate-pulse dark:bg-gray-800" />
    </div>
    <div
      v-else-if="!ready"
      class="px-3 pb-3"
    >
      <div class="text-xs text-red-600 dark:text-red-400">
        Audio failed to load
      </div>
    </div>
  </div>
</template>
