<template>
  <teleport to="body">
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-[95]"
      >
        <div
          class="absolute inset-0 bg-black/40 backdrop-blur-sm"
          @click="$emit('close')"
        />
        <div class="absolute inset-0 flex items-center justify-center p-4">
          <div class="w-full max-w-lg rounded-2xl border border-white/10 bg-slate-900/70 p-4 text-zinc-100 shadow-[inset_0_1px_0_rgba(255,255,255,.06)] backdrop-blur-sm">
            <div class="mb-3 flex items-center justify-between">
              <h3 class="text-sm font-semibold">
                Share
              </h3>
              <button
                class="rounded-lg p-2 text-zinc-300 hover:bg-white/10"
                aria-label="Close"
                @click="$emit('close')"
              >
                <svg
                  viewBox="0 0 24 24"
                  class="h-5 w-5"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="1.5"
                ><path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12"
                /></svg>
              </button>
            </div>

            <div class="space-y-4">
              <div>
                <label class="mb-1 block text-xs text-zinc-400">Link</label>
                <div class="flex items-center gap-2">
                  <input
                    :value="url"
                    readonly
                    class="peer w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm outline-none"
                  >
                  <button
                    class="rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-sm hover:bg-white/15"
                    @click="onCopyLink"
                  >
                    <span v-if="copiedLink">Copied</span>
                    <span v-else>Copy</span>
                  </button>
                </div>
              </div>

              <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  :disabled="!webShareSupported"
                  @click="onWebShare"
                >
                  <Icon
                    icon="mdi:web"
                    class="h-6 w-6"
                  />
                  <span>System</span>
                </button>
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  @click="shareProvider('twitter')"
                >
                  <Icon
                    icon="mdi:twitter"
                    class="h-6 w-6"
                  />
                  <span>Twitter</span>
                </button>
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  @click="shareProvider('telegram')"
                >
                  <Icon
                    icon="mdi:telegram"
                    class="h-6 w-6"
                  />
                  <span>Telegram</span>
                </button>
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  @click="shareProvider('whatsapp')"
                >
                  <Icon
                    icon="mdi:whatsapp"
                    class="h-6 w-6"
                  />
                  <span>WhatsApp</span>
                </button>
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  @click="shareProvider('linkedin')"
                >
                  <Icon
                    icon="mdi:linkedin"
                    class="h-6 w-6"
                  />
                  <span>LinkedIn</span>
                </button>
                <button
                  class="flex flex-col items-center gap-2 rounded-xl border border-white/10 bg-white/5 px-3 py-3 text-sm hover:bg-white/10"
                  @click="onCopyEmbed"
                >
                  <Icon
                    icon="mdi:code-braces"
                    class="h-6 w-6"
                  />
                  <span>Embed</span>
                </button>
              </div>

              <div>
                <transition name="fade">
                  <div
                    v-if="copiedEmbed"
                    class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-xs text-emerald-300"
                  >
                    Embed copied
                  </div>
                </transition>
              </div>
            </div>
          </div>
        </div>
      </div>
    </transition>
  </teleport>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue'
import { ref, computed } from 'vue'
import { canWebShare, webShare, copyToClipboard, providerUrl, openWindow } from '../../composables/useShare'

const props = defineProps<{ open: boolean; url: string; title?: string; text?: string }>()
const emit = defineEmits<{ (e:'close'): void; (e:'shared'): void }>()
const copiedLink = ref(false)
const copiedEmbed = ref(false)
const webShareSupported = computed(() => canWebShare())

function onCopyLink() {
  copyToClipboard(props.url).then(() => {
    copiedLink.value = true
    setTimeout(() => (copiedLink.value = false), 1200)
    emit('shared')
  })
}

function onWebShare() {
  if (!webShareSupported.value) return
  webShare({ title: props.title, text: props.text, url: props.url })
    .then(() => emit('shared'))
    .catch(() => {})
}

function shareProvider(p: 'twitter' | 'telegram' | 'whatsapp' | 'linkedin') {
  openWindow(providerUrl(p, props.url, props.text || props.title))
  emit('shared')
}

function onCopyEmbed() {
  const code = `<iframe src="${props.url}" loading="lazy" style="border:0;width:100%;height:400px;"></iframe>`
  copyToClipboard(code).then(() => {
    copiedEmbed.value = true
    setTimeout(() => (copiedEmbed.value = false), 1200)
    emit('shared')
  })
}
</script>

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .15s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
