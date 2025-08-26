<!-- MediaUploader.vue -->
<script setup lang="ts">
import { ref, computed } from "vue";
import axios, { AxiosError } from "axios";
import { api, ensureCsrfCookie } from "@/lib/http";

type Step = "idle" | "presigned" | "uploading" | "finalized" | "error";

type PresignResponse = {
  id: number;            // ID رکورد مدیا که قراره فاینال بشه
  upload_url: string;    // URL امضاشده برای PUT
  key: string;           // کلید آبجکت در استوریج
};

type Media = {
  id: number;
  title?: string | null;
  key?: string | null;
  type?: string | null;
  url?: string | null;
  [k: string]: unknown;
};

const props = defineProps<{
  accept?: string;    // مثال: "image/*,video/*"
  maxSizeMB?: number; // مثال: 1024
}>();

const emit = defineEmits<{
  uploaded: [media: Media]
  error: [err: unknown]
}>()

const file = ref<File | null>(null);
const progress = ref<number>(0);
const step = ref<Step>("idle");
const log = ref<string>("");

const canUpload = computed(() => !!file.value && step.value !== "uploading");

function reset() {
  progress.value = 0;
  step.value = "idle";
  log.value = "";
}

function pickFile(e: Event) {
  const input = e.target as HTMLInputElement;
  const f = input.files?.[0] ?? null;
  file.value = f;
  reset();
  if (!f) return;

  if (props.maxSizeMB && f.size > props.maxSizeMB * 1024 * 1024) {
    step.value = "error";
    log.value = `✖ File too large (>${props.maxSizeMB}MB)`;
    file.value = null;
  }
}

function guessTypeByMime(mime: string): "image" | "video" | "audio" | "document" {
  if (mime.startsWith("image/")) return "image";
  if (mime.startsWith("video/")) return "video";
  if (mime.startsWith("audio/")) return "audio";
  return "document";
}

async function createPresigned(extension: string, type: string): Promise<PresignResponse> {
  await ensureCsrfCookie();
  const { data } = await api.post<PresignResponse>("/media/presigned", {
    extension,
    type,
  });
  return data;
}

// عمداً void: پاسخ finalize برای لاگ استفاده نمی‌شود
async function finalizeUpload(id: number, title?: string): Promise<void> {
  await ensureCsrfCookie();
  await api.post(`/media/${id}/finalize`, { title });
}

async function getMedia(id: number): Promise<Media> {
  const { data } = await api.get<Media>(`/media/${id}`);
  return data;
}

function prettyError(err: unknown): string {
  const ax = err as AxiosError<any>;
  if (ax?.response) {
    const status = ax.response.status;
    const serverMsg =
      (ax.response.data && (ax.response.data.message || ax.response.data.error)) ||
      JSON.stringify(ax.response.data);
    return `HTTP ${status} — ${serverMsg}`;
  }
  if ((err as any)?.message) return (err as any).message;
  try { return String(err); } catch { return "Unknown error"; }
}

async function upload() {
  try {
    if (!file.value) return;
    const f = file.value;

    const ext = (f.name.split(".").pop() || "").toLowerCase() || "bin";
    const type = guessTypeByMime(f.type || "");

    log.value = `→ presign for .${ext} (${type})`;

    // 1) Presign (POST /api/media/presigned)
    const presign = await createPresigned(ext, type);
    step.value = "presigned";
    log.value += `\n✔ got URL`;

    // 2) PUT مستقیم به URL امضاشده با axios و progress
    step.value = "uploading";
    progress.value = 0;

    await axios.put(presign.upload_url, f, {
      headers: { "Content-Type": f.type || "application/octet-stream" },
      withCredentials: false,  // presigned URL خارج از دامین API
      baseURL: undefined,      // نذار /api بهش اضافه بشه
      onUploadProgress: (e) => {
        if (e.total) {
          progress.value = Math.round((e.loaded / e.total) * 100);
        } else if (f.size) {
          progress.value = Math.min(100, Math.round((e.loaded / f.size) * 100));
        }
      },
    });

    log.value += `\n✔ uploaded to object storage`;

    // 3) Finalize (POST /api/media/{id}/finalize) — هیچ ID از پاسخ نخون!
    await finalizeUpload(presign.id, f.name);

    // 4) تلاش برای گرفتن آبجکت کامل (GET /api/media/{id})
    let mediaIdForLog: number = presign.id;
    try {
      const media = await getMedia(presign.id);
      mediaIdForLog = typeof media?.id === "number" ? media.id : presign.id;
      emit("uploaded", media);
    } catch {
      // حتی اگر GET شکست خورد، باز هم یک ID معتبر برای لاگ داریم
      emit("uploaded", { id: presign.id } as Media);
    }

    log.value += `\n✔ finalized (media id=${mediaIdForLog})`;
    step.value = "finalized";
  } catch (err) {
    step.value = "error";
    const msg = prettyError(err);
    log.value += `\n✖ ${msg}`;
    console.error(err);
    emit("error", err);
  }
}
</script>

<template>
  <div class="uploader border border-gray-300 rounded-lg p-4 space-y-3">
    <h2 class="text-lg font-semibold">
      Media Upload (S3 presigned)
    </h2>

    <input
      type="file"
      :accept="accept"
      class="block"
      @change="pickFile"
    >

    <div class="flex gap-2">
      <button
        :disabled="!canUpload"
        class="px-3 py-1.5 rounded bg-black text-white disabled:opacity-50"
        @click="upload"
      >
        Upload
      </button>
      <button
        type="button"
        class="px-3 py-1.5 rounded border"
        @click="reset"
      >
        Reset
      </button>
    </div>

    <div
      v-if="step !== 'idle'"
      class="status space-y-2"
    >
      <p><strong>Step:</strong> {{ step }}</p>

      <div v-if="step === 'uploading'">
        <progress
          :value="progress"
          max="100"
          class="w-full"
        />
        <span class="ml-2">{{ progress }}%</span>
      </div>

      <pre class="log bg-gray-600 p-2 rounded max-h-48 overflow-auto">{{ log }}</pre>
    </div>
  </div>
</template>

<style scoped>
progress { width: 100%; height: 10px; }
</style>
