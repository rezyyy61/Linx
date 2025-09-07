<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useMediaStore, type UploadTask } from "@/stores/post/post.media";
import { useFileUpload } from "@/modules/dashboard/pages/announcements/composables/useFileUpload";

const props = defineProps<{
  campaignId?: number;
  currentCoverUrl?: string | null;
}>();

const emit = defineEmits<{
  (e: "selected", v: { id: number; url: string | null }): void;
  (e: "updated"): void;
  (e: "cleared"): void;
}>();

const FQN_CAMPAIGN = "App\\Models\\Campaign\\Campaign";

const mediaStore = useMediaStore();
const { attachSingle } = useFileUpload();

const inputRef = ref<HTMLInputElement | null>(null);
const task = ref<UploadTask | null>(null);
const previewUrl = ref<string | null>(props.currentCoverUrl || null);
const busy = computed(() => !!task.value && ["presigning","uploading","finalizing","scanning","processing"].includes(task.value.status));
const ready = computed(() => task.value?.status === "ready");

function openPicker() {
  inputRef.value?.click();
}

function onChoose(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0];
  if (!file) return;
  if (task.value) remove(false);
  const t = mediaStore.createTask(file, "image");
  task.value = t;
  mediaStore.enqueueTask(t);
}

async function onAfterReady() {
  if (!task.value?.id) return;
  const media = await mediaStore.fetchMedia(task.value.id);
  const url = (media.public_url || media.url || null) as string | null;
  previewUrl.value = url;

  if (props.campaignId) {
    await attachSingle(task.value.id, FQN_CAMPAIGN, props.campaignId, "campaign-cover", 0);
    emit("updated");
  } else {
    emit("selected", { id: task.value.id, url });
  }
}

async function remove(deleteServer = true) {
  try {
    if (task.value && deleteServer && !props.campaignId) {
      await mediaStore.removeDraftMedia(task.value);
    }
  } finally {
    task.value = null;
    previewUrl.value = null;
    emit("cleared");
    if (inputRef.value) inputRef.value.value = "";
  }
}

watch(
  () => task.value?.status,
  (st) => {
    if (st === "ready") onAfterReady();
  }
);

onBeforeUnmount(() => {
  if (task.value && task.value.status !== "ready") {
    try { mediaStore.removeDraftMedia(task.value); } catch { /* empty */ }
  }
});
</script>

<template>
  <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:image"
          class="w-5 h-5 text-gray-500"
        />
        <h3 class="font-semibold">
          {{ $t("campaign.cover.title") }}
        </h3>
      </div>
      <div class="text-xs">
        <span
          v-if="busy"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        >
          <Icon
            icon="mdi:progress-clock"
            class="w-4 h-4"
          /> {{ $t("campaign.cover.uploading") }}
        </span>
        <span
          v-else-if="ready"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
        >
          <Icon
            icon="mdi:check-circle"
            class="w-4 h-4"
          /> Ready
        </span>
      </div>
    </div>

    <div class="flex gap-4">
      <div class="h-32 w-32 flex-shrink-0 rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 flex items-center justify-center">
        <img
          v-if="previewUrl"
          :src="previewUrl"
          alt=""
          class="h-full w-full object-cover"
        >
        <Icon
          v-else
          icon="mdi:image-plus"
          class="w-8 h-8 text-gray-400"
        />
      </div>

      <div class="flex-1">
        <div
          class="relative rounded-lg border border-dashed border-gray-300 dark:border-gray-700 p-4 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition"
          @click="openPicker"
        >
          <div class="flex items-center gap-3">
            <Icon
              icon="mdi:cloud-upload"
              class="w-6 h-6 text-gray-500"
            />
            <div class="text-sm">
              <div class="font-medium">
                {{ $t("campaign.cover.dropOrClick") }}
              </div>
              <div class="text-gray-500">
                {{ $t("campaign.cover.chooseFile") }}
              </div>
            </div>
          </div>
          <input
            ref="inputRef"
            type="file"
            accept="image/*"
            class="hidden"
            @change="onChoose"
          >
        </div>

        <div
          v-if="task"
          class="mt-3"
        >
          <div class="h-2 w-full rounded bg-gray-200 dark:bg-gray-800 overflow-hidden">
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

        <div class="mt-3 flex items-center gap-2">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded border px-3 py-1.5 text-sm dark:border-gray-700 disabled:opacity-50"
            :disabled="!task && !previewUrl"
            @click="remove()"
          >
            <Icon
              icon="mdi:trash-can-outline"
              class="w-4 h-4"
            />
            <span>{{ $t("common.remove") }}</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
