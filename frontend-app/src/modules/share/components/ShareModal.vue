<template>
  <transition
    appear
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-50"
    >
      <div
        class="absolute inset-0 bg-black/40 dark:bg-black/60"
        @click="emit('close')"
      />
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 shadow-xl ring-1 ring-black/5 dark:ring-white/10">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Share
            </h3>
            <button
              class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              <Icon
                icon="mdi:close"
                class="h-5 w-5 text-gray-500 dark:text-gray-400"
              />
            </button>
          </div>

          <div class="px-5 py-5">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
              <div class="space-y-4">
                <div class="space-y-2">
                  <div class="flex items-center gap-2">
                    <input
                      v-model="shortUrl"
                      type="text"
                      readonly
                      class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2"
                      @focus="onFocusSelect"
                    >
                    <button
                      class="shrink-0 inline-flex items-center gap-1 rounded-xl px-3 py-2 border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 text-sm"
                      @click="copyLink"
                    >
                      <Icon
                        icon="mdi:content-copy"
                        class="h-5 w-5"
                      />
                      <span>Copy</span>
                    </button>
                  </div>
                  <p
                    v-if="copied"
                    class="text-xs text-green-600 dark:text-green-400"
                  >
                    Copied
                  </p>
                </div>

                <div class="grid grid-cols-4 gap-3">
                  <button
                    class="flex flex-col items-center justify-center rounded-2xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="shareExternal('whatsapp')"
                  >
                    <Icon
                      icon="mdi:whatsapp"
                      class="h-6 w-6 text-green-600"
                    />
                    <span class="mt-1 text-xs text-gray-700 dark:text-gray-200">WhatsApp</span>
                  </button>
                  <button
                    class="flex flex-col items-center justify-center rounded-2xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="shareExternal('telegram')"
                  >
                    <Icon
                      icon="mdi:telegram"
                      class="h-6 w-6 text-sky-500"
                    />
                    <span class="mt-1 text-xs text-gray-700 dark:text-gray-200">Telegram</span>
                  </button>
                  <button
                    class="flex flex-col items-center justify-center rounded-2xl border border-gray-200 dark:border-gray-700 p-3 hover:bg-gray-50 dark:hover:bg-gray-800"
                    @click="shareExternal('facebook')"
                  >
                    <Icon
                      icon="mdi:facebook"
                      class="h-6 w-6 text-blue-600"
                    />
                    <span class="mt-1 text-xs text-gray-700 dark:text-gray-200">Facebook</span>
                  </button>
                  <button
                    class="flex flex-col items-center justify-center rounded-2xl border border-indigo-200 dark:border-indigo-700 p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/30"
                    @click="isRepostOpen = true"
                  >
                    <Icon
                      icon="mdi:repeat-variant"
                      class="h-6 w-6 text-indigo-600 dark:text-indigo-400"
                    />
                    <span class="mt-1 text-xs text-gray-700 dark:text-gray-200">Repost</span>
                  </button>
                </div>
              </div>

              <div class="flex items-center justify-center">
                <div class="p-4 rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                  <img
                    v-if="qrDataUrl"
                    :src="qrDataUrl"
                    alt="QR"
                    class="h-48 w-48"
                  >
                  <div
                    v-else
                    class="h-48 w-48 flex items-center justify-center text-sm text-gray-500 dark:text-gray-400"
                  >
                    Generating…
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
            <button
              class="px-3 py-2 text-sm rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              Close
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>

  <RepostModal
    :open="isRepostOpen"
    :post-id="shareableId"
    :post="post?.original ?? post"
    @close="isRepostOpen = false"
    @done="onRepostDone"
  />
</template>

<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import { Icon } from '@iconify/vue';
import type { ShareChannel } from '../types';
import { useShare } from '../composables/useShare';
import RepostModal from './RepostModal.vue';

interface Props {
  open: boolean;
  shareableType?: string;
  shareableAlias?: string;
  shareableId: number;
  title?: string;
  text?: string;
  post?: any;
}

const props = defineProps<Props>();
const emit = defineEmits<{ (e: 'close'): void; (e: 'shared'): void }>();

const copied = ref(false);
const qrDataUrl = ref<string>('');
const shortUrl = ref<string>('');
const isRepostOpen = ref(false);

const share = useShare({
  shareableType: props.shareableType,
  shareableAlias: props.shareableAlias,
  shareableId: props.shareableId,
  defaultTitle: props.title,
  defaultText: props.text,
});

function onFocusSelect(e: FocusEvent) {
  const el = e.target as HTMLInputElement | null
  el?.select()
}

async function ensureShare() {
  if (!share.lastShare.value) {
    const res = await share.shareTo('internal');
    shortUrl.value = res.short_url;
  } else {
    shortUrl.value = share.lastShare.value.short_url;
  }
}

async function copyLink() {
  await ensureShare();
  try {
    await navigator.clipboard.writeText(shortUrl.value);
    copied.value = true;
    emit('shared');
    setTimeout(() => (copied.value = false), 1200);
  } catch {
    copied.value = false;
  }
}

async function shareExternal(channel: ShareChannel) {
  await ensureShare();
  const url = share.buildExternalUrl(channel, props.title, props.text);
  window.open(url, '_blank', 'noopener,noreferrer');
  emit('shared');
}

async function buildQr() {
  await ensureShare();
  const lib = await import('qrcode');
  qrDataUrl.value = await lib.toDataURL(shortUrl.value, { margin: 1, width: 480 });
}

function onRepostDone() {
  emit('shared');
  isRepostOpen.value = false;
}

watch(() => props.open, async v => {
  if (v) {
    copied.value = false;
    qrDataUrl.value = '';
    await ensureShare();
    await buildQr();
  }
});

onMounted(async () => {
  if (props.open) {
    await ensureShare();
    await buildQr();
  }
});
</script>
