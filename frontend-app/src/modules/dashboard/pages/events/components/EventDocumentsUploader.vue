<script setup lang="ts">
import { computed, ref, watch, onMounted } from "vue";
import { Icon } from "@iconify/vue";
import { useMediaStore, type UploadTask } from "@/stores/post/post.media";
import { useEventMedia } from "@/modules/dashboard/pages/events/composables/useEventMedia";

const props = defineProps<{
  eventId?: number;
  initialDocs?: Array<{ id:number; url:string|null }>;
  disabled?: boolean;
}>();

const emit = defineEmits<{
  (e: "changed", v: Array<{ id: number; url: string | null }>): void;
  (e: "updated"): void;
}>();

const mediaStore = useMediaStore();
const { docs, addDoc, removeDoc } = useEventMedia(props.eventId, {
  documents: Array.isArray(props.initialDocs) ? props.initialDocs : [],
});

type DocView = { id:number; url:string|null; name:string; ext:string; persisted:boolean };

const inputRef = ref<HTMLInputElement | null>(null);
const tasks = ref<UploadTask[]>([]);
const viewDocs  = ref<DocView[]>([]);
const isDragging = ref(false);
const processedIds = ref<Set<number>>(new Set());

const activeTasksCount = computed(() =>
  tasks.value.filter(t => !["ready","failed","error","rejected"].includes(t.status)).length
);
const remainingSlots = computed(() => Math.max(0, 2 - (viewDocs.value.length + activeTasksCount.value)));
const uploading = computed(() => activeTasksCount.value > 0);
const hasReady = computed(() => !uploading.value && viewDocs.value.length > 0);
const canInteract = computed(() => !props.disabled && remainingSlots.value > 0);

function toName(id:number, url:string|null) {
  try {
    if (url) {
      const u = new URL(url);
      const base = u.pathname.split("/").pop() || "";
      if (base) return decodeURIComponent(base);
    }
  } catch { /* empty */ }
  return `Document #${id}`;
}
function toExt(name:string) {
  const p = name.lastIndexOf(".");
  return p >= 0 ? name.slice(p+1).toLowerCase() : "";
}
function iconFor(ext: string) {
  const e = ext.toLowerCase();
  if (["pdf"].includes(e)) return "mdi:file-pdf-box";
  if (["doc","docx"].includes(e)) return "mdi:file-word-box";
  if (["xls","xlsx","csv"].includes(e)) return "mdi:file-excel-box";
  if (["ppt","pptx"].includes(e)) return "mdi:file-powerpoint-box";
  if (["zip","rar","7z"].includes(e)) return "mdi:folder-zip";
  if (["png","jpg","jpeg","webp","gif","svg"].includes(e)) return "mdi:file-image";
  return "mdi:file-document";
}

function hydrateFromModel() {
  const base = docs.value.map(d => {
    const name = toName(d.id, d.url);
    return { id: d.id, url: d.url, name, ext: toExt(name), persisted: !!props.eventId } as DocView;
  });
  viewDocs.value = base;
  emit("changed", docs.value.map(d => ({ id: d.id, url: d.url })));
}
onMounted(hydrateFromModel);

watch(() => props.initialDocs, () => {
  docs.value = Array.isArray(props.initialDocs) ? [...props.initialDocs] : [];
  hydrateFromModel();
}, { deep: true });

function openPicker() {
  if (!canInteract.value) return;
  inputRef.value?.click();
}

function isAllowedDoc(f: File) {
  const name = (f.name || "").toLowerCase();
  const ext = name.split(".").pop() || "";
  const allowedExts = new Set([
    "pdf","doc","docx","rtf","txt","xls","xlsx","csv","ppt","pptx","odt","ods","odp","zip"
  ]);
  if (allowedExts.has(ext)) return true;

  const t = (f.type || "").toLowerCase();
  if (t.startsWith("image/")) return false;
  if (t.startsWith("video/")) return false;
  if (t.startsWith("audio/")) return false;

  const allowedMimes = [
    "application/pdf",
    "application/msword",
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    "application/vnd.ms-excel",
    "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
    "application/vnd.ms-powerpoint",
    "application/vnd.openxmlformats-officedocument.presentationml.presentation",
    "text/plain",
    "application/rtf",
    "application/vnd.oasis.opendocument.text",
    "application/vnd.oasis.opendocument.spreadsheet",
    "application/vnd.oasis.opendocument.presentation",
    "application/zip",
  ];
  return allowedMimes.includes(t);
}

function addFiles(fileList: FileList) {
  if (remainingSlots.value <= 0) return;

  const all = Array.from(fileList).filter(isAllowedDoc);
  if (!all.length) return;

  const take = Math.min(remainingSlots.value, all.length);
  const selected = all.slice(0, take);

  const newTasks: UploadTask[] = [];
  for (const f of selected) {
    const t = mediaStore.createTask(f, "document");
    newTasks.push(t);
    mediaStore.enqueueTask(t);
  }
  tasks.value = tasks.value.concat(newTasks);
}


function onChoose(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (!files || !files.length) return;
  addFiles(files);
  if (inputRef.value) inputRef.value.value = "";
}

function onDrop(e: DragEvent) {
  e.preventDefault();
  isDragging.value = false;
  if (!e.dataTransfer?.files?.length || !canInteract.value) return;
  addFiles(e.dataTransfer.files);
}
function onDragOver(e: DragEvent) {
  e.preventDefault();
  if (!canInteract.value) return;
  isDragging.value = true;
}
function onDragLeave() {
  isDragging.value = false;
}

watch(
  () => tasks.value.map(t => `${t.status}:${t.id ?? ""}`).join("|"),
  async () => {
    const ready = tasks.value.filter(t => t.status === "ready" && t.id && !processedIds.value.has(t.id!));
    if (!ready.length) return;

    if (props.eventId) {
      for (let i = 0; i < ready.length; i++) {
        if (processedIds.value.has(ready[i].id!)) continue;
        await addDoc(ready[i].id!, viewDocs.value.length + i);
        const m = await mediaStore.fetchMedia(ready[i].id!);
        const name = toName(ready[i].id!, (m.public_url || m.url || null) as string | null);
        if (!viewDocs.value.some(d => d.id === ready[i].id)) {
          viewDocs.value.push({ id: ready[i].id!, url: (m.public_url || m.url || null) as string | null, name, ext: toExt(name), persisted: true });
        }
        processedIds.value.add(ready[i].id!);
      }
      emit("updated");
      emit("changed", docs.value.map(d => ({ id: d.id, url: d.url })));
    } else {
      for (const t of ready) {
        if (processedIds.value.has(t.id!)) continue;
        const m = await mediaStore.fetchMedia(t.id!);
        const name = toName(t.id!, (m.public_url || m.url || null) as string | null);
        if (!docs.value.some(d => d.id === t.id)) docs.value.push({ id: t.id!, url: (m.public_url || m.url || null) as string | null });
        if (!viewDocs.value.some(d => d.id === t.id)) viewDocs.value.push({ id: t.id!, url: (m.public_url || m.url || null) as string | null, name, ext: toExt(name), persisted: false });
        processedIds.value.add(t.id!);
      }
      emit("changed", docs.value.map(d => ({ id: d.id, url: d.url })));
    }

    tasks.value = tasks.value.filter(t => !(t.status === "ready" && t.id && processedIds.value.has(t.id)));
  }
);

async function removeAt(idx: number) {
  const d = viewDocs.value[idx];
  if (!d) return;
  await removeDoc(d.id);
  viewDocs.value.splice(idx, 1);
  if (props.eventId) {
    emit("updated");
    emit("changed", docs.value.map(x => ({ id: x.id, url: x.url })));
  } else {
    emit("changed", docs.value.map(x => ({ id: x.id, url: x.url })));
  }
  processedIds.value.delete(d.id);
}
</script>

<template>
  <div class="rounded-2xl border border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 p-5 shadow-sm">
    <div class="flex items-center justify-between mb-4">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:file-multiple"
          class="w-5 h-5 text-gray-500"
        />
        <h3 class="font-semibold">
          {{ $t("event.docs.title") }}
        </h3>
      </div>
      <div class="text-xs">
        <span
          v-if="uploading"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        >
          <Icon
            icon="mdi:progress-clock"
            class="w-4 h-4"
          /> {{ $t("event.docs.uploading") }}
        </span>
        <span
          v-else-if="hasReady"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300"
        >
          <Icon
            icon="mdi:check-circle"
            class="w-4 h-4"
          /> Ready
        </span>
      </div>
    </div>

    <div class="space-y-5">
      <div
        class="relative rounded-2xl border border-dashed p-6 transition-all select-none flex items-center justify-center text-center w-full"
        :class="[
          canInteract ? 'cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800' : 'opacity-60 cursor-not-allowed',
          isDragging ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-900/20' : 'border-gray-300 dark:border-gray-700'
        ]"
        @click="openPicker"
        @drop="onDrop"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
      >
        <div class="flex flex-col items-center gap-3">
          <div class="h-14 w-14 rounded-2xl bg-gray-200 dark:bg-gray-800 flex items-center justify-center">
            <Icon
              icon="mdi:cloud-upload"
              class="w-7 h-7 text-gray-500"
            />
          </div>
          <div class="text-sm font-medium">
            {{ remainingSlots > 0 ? $t("event.cover.dropOrClick") : ($t("common.limitReached") || "Limit reached") }}
          </div>
          <div
            v-if="remainingSlots > 0"
            class="text-xs text-gray-500"
          >
            {{ $t("event.cover.chooseFile") }} • {{ remainingSlots }}/2
          </div>
          <div
            v-else
            class="text-xs text-gray-500"
          >
            2/2
          </div>
        </div>
        <input
          ref="inputRef"
          type="file"
          multiple
          class="hidden"
          :disabled="!canInteract"
          @change="onChoose"
        >
      </div>

      <div
        v-if="tasks.filter(t => t.status !== 'ready').length"
        class="space-y-2 w-full"
      >
        <div
          v-for="t in tasks.filter(t => t.status !== 'ready')"
          :key="t.uid"
          class="rounded-xl border border-gray-200 dark:border-gray-800 p-3 w-full"
        >
          <div class="flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
            <span>Status: {{ t.status }}</span>
            <span v-if="t.status !== 'ready'">{{ t.progress || 0 }}%</span>
          </div>
          <div
            v-if="t.status !== 'ready'"
            class="mt-2 h-2 w-full rounded-full bg-gray-200 dark:bg-gray-800 overflow-hidden"
          >
            <div
              class="h-2 bg-blue-600 transition-all"
              :style="{ width: (t.progress || 0) + '%' }"
            />
          </div>
        </div>
      </div>

      <div
        v-if="viewDocs.length"
        class="space-y-3 w-full"
      >
        <div
          v-for="(d, idx) in viewDocs"
          :key="d.id"
          class="rounded-xl border border-gray-200 dark:border-gray-800 p-4 flex items-center gap-3 hover:shadow-sm transition w-full"
        >
          <div class="h-12 w-12 rounded-xl bg-gray-100 dark:bg-gray-800 flex items-center justify-center flex-shrink-0">
            <Icon
              :icon="iconFor(d.ext)"
              class="w-6 h-6 text-gray-600 dark:text-gray-300"
            />
          </div>
          <div class="min-w-0 flex-1">
            <div class="text-sm font-medium truncate">
              {{ d.name }}
            </div>
            <div class="text-xs text-gray-500 dark:text-gray-400">
              #{{ d.id }}
            </div>
          </div>
          <button
            type="button"
            class="ml-2 inline-flex items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 px-2.5 py-2 text-xs"
            :title="$t('common.remove') as string"
            @click="removeAt(idx)"
          >
            <Icon
              icon="mdi:trash-can-outline"
              class="w-4 h-4"
            />
          </button>
        </div>
      </div>

      <div
        v-else
        class="text-xs text-gray-500 w-full"
      >
        {{ $t("event.docs.upload") }}
      </div>
    </div>
  </div>
</template>
