<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useMediaStore, type UploadTask } from "@/stores/post/post.media";
import { usePublicationMedia } from "@/modules/dashboard/pages/publications/composables/usePublicationMedia";

const props = defineProps<{
  publicationId?: number;
  currentCoverUrl?: string | null;
  currentCoverId?: number | null;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "selected", v: { id: number; url: string | null } | null): void;
  (e: "updated"): void;
  (e: "cleared"): void;
}>();

const mediaStore = useMediaStore();
const { coverId, coverUrl, setCover, clearCover } = usePublicationMedia(props.publicationId, {
  cover_id: props.currentCoverId ?? null,
  cover_url: props.currentCoverUrl ?? null,
});

const inputRef = ref<HTMLInputElement | null>(null);
const task = ref<UploadTask | null>(null);
const isDragging = ref(false);

const busy = computed(() => !!task.value && ["presigning","uploading","finalizing","scanning","processing"].includes(task.value.status));
const ready = computed(() => task.value?.status === "ready");
const canInteract = computed(() => !props.disabled && !busy.value);

function openPicker() {
  if (!canInteract.value) return;
  inputRef.value?.click();
}

function pickFile(file?: File | null) {
  if (!file || props.disabled) return;
  if (task.value) resetLocal();
  const t = mediaStore.createTask(file, "image");
  task.value = t;
  mediaStore.enqueueTask(t);
}

function onChoose(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0] || null;
  pickFile(file);
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
  const file = e.dataTransfer?.files?.[0] || null;
  pickFile(file);
}

function onDragOver(e: DragEvent) {
  e.preventDefault();
  if (!canInteract.value) return;
  isDragging.value = true;
}

function onDragLeave() {
  isDragging.value = false;
}

async function onAfterReady() {
  if (!task.value?.id) return;
  await setCover(task.value.id);
  const payload = { id: coverId.value!, url: coverUrl.value };
  if (props.publicationId) emit("updated");
  emit("selected", payload);
}

async function remove() {
  await clearCover();
  if (props.publicationId) {
    emit("updated");
  } else {
    emit("cleared");
  }
  emit("selected", null);
  resetLocal();
}

function resetLocal() {
  task.value = null;
  if (inputRef.value) inputRef.value.value = "";
}

watch(() => task.value?.status, (st) => {
  if (st === "ready") onAfterReady();
});

watch(() => props.currentCoverId, (v) => {
  if (!props.publicationId) return;
  if (!v) return;
  if (coverId.value !== v) coverId.value = v ?? null;
}, { immediate: true });

watch(() => props.currentCoverUrl, (v) => {
  if (!props.publicationId) return;
  if (coverUrl.value !== (v ?? null)) coverUrl.value = v ?? null;
}, { immediate: true });
</script>

<template>
  <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:image"
          class="w-5 h-5 text-gray-500"
        />
        <h3 class="font-semibold">
          Cover
        </h3>
      </div>
      <div class="text-xs">
        <span
          v-if="busy"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        >
          <Icon
            icon="mdi:progress-clock"
            class="w-4 h-4"
          /> Uploading
        </span>
        <span
          v-else-if="ready || coverUrl"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
        >
          <Icon
            icon="mdi:check-circle"
            class="w-4 h-4"
          /> Ready
        </span>
      </div>
    </div>

    <div class="flex flex-col gap-4">
      <div class="relative rounded-2xl overflow-hidden border border-gray-200 dark:border-gray-800 bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-800 dark:to-gray-900">
        <div
          class="group cursor-pointer"
          :class="canInteract ? '' : 'pointer-events-none opacity-60'"
          @click="openPicker"
          @drop="onDrop"
          @dragover="onDragOver"
          @dragleave="onDragLeave"
        >
          <div class="aspect-[16/9] md:aspect-[21/9] w-full">
            <div
              v-if="!coverUrl"
              class="absolute inset-0"
            >
              <div class="h-full w-full flex flex-col items-center justify-center gap-3">
                <div class="h-16 w-16 rounded-2xl bg-gray-200 dark:bg-gray-800 animate-pulse flex items-center justify-center">
                  <Icon
                    icon="mdi:image-plus"
                    class="w-8 h-8 text-gray-400"
                  />
                </div>
                <div class="text-sm text-gray-600 dark:text-gray-400">
                  Drop or click
                </div>
                <div class="text-xs text-gray-500">
                  Choose an image
                </div>
              </div>
            </div>

            <img
              v-else
              :src="coverUrl"
              alt=""
              class="h-full w-full object-contain"
            >

            <div
              class="absolute inset-0 transition-all"
              :class="[ isDragging ? 'ring-4 ring-blue-500/60 ring-offset-2 ring-offset-transparent' : 'ring-0' ]"
            />

            <div class="absolute inset-x-0 bottom-0 p-3 md:p-4 bg-gradient-to-t from-black/50 via-black/20 to-transparent flex items-center justify-between">
              <div class="flex items-center gap-2 text-white/90 text-xs md:text-sm">
                <Icon
                  v-if="busy"
                  icon="mdi:progress-clock"
                  class="w-4 h-4"
                />
                <Icon
                  v-else-if="ready || coverUrl"
                  icon="mdi:check-circle"
                  class="w-4 h-4"
                />
                <span v-if="busy">Uploading</span>
                <span v-else-if="ready || coverUrl">Ready</span>
              </div>

              <button
                type="button"
                class="inline-flex items-center gap-2 rounded-full bg-white/90 text-gray-900 px-3 py-1.5 text-xs md:text-sm backdrop-blur hover:bg-white disabled:opacity-50"
                :disabled="(!coverUrl && !task) || disabled"
                @click.stop="remove"
              >
                <Icon
                  icon="mdi:trash-can-outline"
                  class="w-4 h-4"
                />
                <span>Remove</span>
              </button>
            </div>
          </div>
        </div>

        <input
          ref="inputRef"
          type="file"
          accept="image/*"
          class="hidden"
          :disabled="!canInteract"
          @change="onChoose"
        >
      </div>

      <div
        v-if="task && task.status !== 'ready'"
        class="mt-1"
      >
        <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden">
          <div
            class="h-2 bg-blue-600 transition-all"
            :style="{ width: (task.progress || 0) + '%' }"
          />
        </div>
        <div class="mt-1 text-xs text-gray-600 dark:text-gray-400 flex items-center justify-between">
          <span>Status: {{ task.status }}</span>
          <span>{{ task.progress || 0 }}%</span>
        </div>
      </div>
    </div>
  </div>
</template>
