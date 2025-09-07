<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from "vue";
import { Icon } from "@iconify/vue";
import { useMediaStore, type UploadTask } from "@/stores/post/post.media";
import { useFileUpload } from "@/modules/dashboard/pages/campaigns/composables/useFileUpload";

const props = defineProps<{
  campaignId?: number;
  initialDocs?: Array<{ id:number; url:string|null }>;
}>();

const emit = defineEmits<{
  (e: "changed", v: Array<{ id: number; url: string | null }>): void;
  (e: "updated"): void;
}>();

const FQN_CAMPAIGN = "App\\Models\\Campaign\\Campaign";
const mediaStore = useMediaStore();
const { attach } = useFileUpload();

type DocItem = { id:number; url:string|null; name:string; ext:string; persisted:boolean };

const inputRef = ref<HTMLInputElement | null>(null);
const tasks = ref<UploadTask[]>([]);
const docs  = ref<DocItem[]>([]);

const uploading = computed(() => tasks.value.some(t =>
  ["presigning","uploading","finalizing","scanning","processing"].includes(t.status)
));

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

function hydrateInitial() {
  const arr = Array.isArray(props.initialDocs) ? props.initialDocs : [];
  docs.value = arr.map(d => {
    const name = toName(d.id, d.url || null);
    return { id: d.id, url: d.url || null, name, ext: toExt(name), persisted: true };
  });
}
onMounted(hydrateInitial);
watch(() => props.initialDocs, hydrateInitial, { deep:true });

function openPicker() {
  inputRef.value?.click();
}

function onChoose(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (!files || !files.length) return;
  const newTasks: UploadTask[] = [];
  for (let i = 0; i < files.length; i++) {
    const f = files.item(i)!;
    const t = mediaStore.createTask(
      f,
      f.type.startsWith("image/") ? "image"
        : f.type.startsWith("video/") ? "video"
          : f.type.startsWith("audio/") ? "audio" : "document"
    );
    newTasks.push(t);
    mediaStore.enqueueTask(t);
  }
  tasks.value = tasks.value.concat(newTasks);
  if (inputRef.value) inputRef.value.value = "";
}

watch(
  () => tasks.value.map(t => t.status + ":" + t.id).join("|"),
  async () => {
    const ready = tasks.value.filter(t => t.status === "ready" && t.id);
    if (!ready.length) return;

    if (props.campaignId) {
      for (let i = 0; i < ready.length; i++) {
        await attach(ready[i].id!, FQN_CAMPAIGN, props.campaignId, "campaign-document", docs.value.length + i);
        const m = await mediaStore.fetchMedia(ready[i].id!);
        const name = toName(ready[i].id!, (m.public_url || m.url || null) as string | null);
        docs.value.push({
          id: ready[i].id!,
          url: (m.public_url || m.url || null) as string | null,
          name,
          ext: toExt(name),
          persisted: true
        });
      }
      emit("updated");
    } else {
      for (const t of ready) {
        const m = await mediaStore.fetchMedia(t.id!);
        const name = toName(t.id!, (m.public_url || m.url || null) as string | null);
        docs.value.push({
          id: t.id!,
          url: (m.public_url || m.url || null) as string | null,
          name,
          ext: toExt(name),
          persisted: false
        });
      }
      emit("changed", docs.value.map(d => ({ id: d.id, url: d.url })));
    }
  }
);

async function removeDoc(idx: number) {
  const d = docs.value[idx];
  if (!d) return;
  if (props.campaignId && d.persisted) {
    try {
      await mediaStore.detachFromModel(d.id, FQN_CAMPAIGN, props.campaignId, "campaign-document");
    } catch { /* empty */ }
    docs.value.splice(idx, 1);
    emit("updated");
  } else {
    docs.value.splice(idx, 1);
    emit("changed", docs.value.map(x => ({ id: x.id, url: x.url })));
  }
}

onBeforeUnmount(() => {
  for (const t of tasks.value) {
    if (t.status !== "ready") {
      try { mediaStore.removeDraftMedia(t); } catch { /* empty */ }
    }
  }
});
</script>

<template>
  <div class="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    <div class="flex items-center justify-between mb-3">
      <div class="flex items-center gap-2">
        <Icon
          icon="mdi:file-multiple"
          class="w-5 h-5 text-gray-500"
        />
        <h3 class="font-semibold">
          {{ $t("campaign.docs.title") }}
        </h3>
      </div>
      <div class="text-xs">
        <span
          v-if="uploading"
          class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200"
        >
          <Icon
            icon="mdi:progress-clock"
            class="w-4 h-4"
          /> {{ $t("campaign.docs.uploading") }}
        </span>
      </div>
    </div>

    <div class="space-y-4">
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
          multiple
          class="hidden"
          @change="onChoose"
        >
      </div>

      <div
        v-if="docs.length"
        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
      >
        <div
          v-for="(d, idx) in docs"
          :key="d.id"
          class="flex items-center justify-between rounded-lg border border-gray-200 dark:border-gray-700 p-3"
        >
          <div class="flex items-center gap-3 min-w-0">
            <div class="h-10 w-10 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center">
              <Icon
                :icon="iconFor(d.ext)"
                class="w-5 h-5 text-gray-500"
              />
            </div>
            <div class="min-w-0">
              <div class="text-sm font-medium truncate max-w-[220px]">
                {{ d.name }}
              </div>
              <div class="text-xs text-gray-500 dark:text-gray-400">
                #{{ d.id }}
              </div>
            </div>
          </div>
          <button
            type="button"
            class="ml-2 inline-flex items-center justify-center rounded border px-2 py-1 text-xs dark:border-gray-700"
            :title="$t('common.remove') as string"
            @click="removeDoc(idx)"
          >
            <Icon
              icon="mdi:close"
              class="w-4 h-4"
            />
          </button>
        </div>
      </div>

      <div
        v-else
        class="text-xs text-gray-500"
      >
        {{ $t("campaign.docs.upload") }}
      </div>
    </div>
  </div>
</template>
