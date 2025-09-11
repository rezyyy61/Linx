<template>
  <div class="overflow-hidden rounded-xl border border-white/10 bg-white/[0.03] p-3">
    <div class="flex items-center gap-3">
      <div class="h-12 w-12 overflow-hidden rounded-lg border border-white/10 bg-white/5">
        <img
          v-if="media.coverUrl"
          :src="media.coverUrl"
          alt=""
          class="h-full w-full object-cover"
        >
        <div
          v-else
          class="flex h-full w-full items-center justify-center"
        >
          <Icon
            icon="mdi:music"
            class="h-7 w-7 text-zinc-300"
          />
        </div>
      </div>
      <div class="min-w-0 flex-1">
        <div class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
          {{ title }}
        </div>
        <div class="text-xs text-zinc-500 dark:text-zinc-400">
          {{ durationText }}
        </div>
        <div class="mt-2 flex items-center gap-2">
          <button
            class="rounded-full p-1.5 text-zinc-900 hover:bg-zinc-200 dark:text-zinc-100 dark:hover:bg-white/10"
            @click="toggle"
          >
            <Icon
              :icon="playing ? 'mdi:pause' : 'mdi:play'"
              class="h-5 w-5"
            />
          </button>
          <input
            v-model.number="percent"
            type="range"
            min="0"
            max="100"
            class="h-1 w-full accent-indigo-600"
            @input="onSeek"
          >
          <div class="w-12 text-right text-[11px] tabular-nums text-zinc-500 dark:text-zinc-400">
            {{ currentText }}
          </div>
        </div>
      </div>
    </div>
    <audio
      ref="audio"
      :src="media.url"
      preload="metadata"
      @timeupdate="onTime"
      @loadedmetadata="onMeta"
      @ended="onEnded"
    />
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { computed, onBeforeUnmount, ref } from 'vue'
import type { AudioMedia } from '../../types/media.types'
import { hhmmss } from '../../utils/format'

const props = defineProps<{ media: AudioMedia }>()
const audio = ref<HTMLAudioElement|null>(null)
const duration = ref(0)
const current = ref(0)
const percent = ref(0)
const playing = ref(false)

const title = computed(() => props.media.title || 'Audio')
const durationText = computed(() => hhmmss(props.media.durationSec || duration.value))
const currentText = computed(() => hhmmss(current.value))

function onMeta() { if (audio.value) duration.value = audio.value.duration || 0 }
function onTime() { if (!audio.value || !duration.value) return; current.value = audio.value.currentTime; percent.value = Math.min(100, Math.max(0, (current.value / duration.value) * 100)) }
function onSeek() { if (!audio.value || !duration.value) return; audio.value.currentTime = (percent.value / 100) * duration.value }
function toggle() { if (!audio.value) return; if (audio.value.paused) { audio.value.play(); playing.value = true } else { audio.value.pause(); playing.value = false } }
function onEnded() { playing.value = false }
onBeforeUnmount(() => { if (audio.value) { audio.value.pause() } })
</script>
