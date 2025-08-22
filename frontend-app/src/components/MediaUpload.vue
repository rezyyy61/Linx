<script setup lang="ts">
import { ref } from "vue";

type PresignResponse = {
  id: number;
  upload_url: string;
  key: string;
};

const file = ref<File | null>(null);
const progress = ref<number>(0);
const step = ref<"idle" | "presigned" | "uploading" | "finalized" | "error">(
  "idle",
);
const log = ref<string>("");

const apiBase = import.meta.env.VITE_API_BASE as string;
function pickFile(e: Event) {
  const input = e.target as HTMLInputElement;
  file.value = input.files?.[0] ?? null;
  progress.value = 0;
  step.value = "idle";
  log.value = "";
}

async function createPresigned(
  extension: string,
  type: string,
): Promise<PresignResponse> {
  const res = await fetch(`${apiBase}/media/presigned`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ extension, type }),
  });
  if (!res.ok) {
    throw new Error(`Presign failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

async function finalize(id: number, title?: string) {
  const res = await fetch(`${apiBase}/media/${id}/finalize`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json",
    },
    body: JSON.stringify({ title }),
  });
  if (!res.ok) {
    throw new Error(`Finalize failed: ${res.status} ${await res.text()}`);
  }
  return res.json();
}

function guessTypeByMime(
  mime: string,
): "image" | "video" | "audio" | "document" {
  if (mime.startsWith("image/")) return "image";
  if (mime.startsWith("video/")) return "video";
  if (mime.startsWith("audio/")) return "audio";
  return "document";
}

async function upload() {
  try {
    if (!file.value) return;
    const f = file.value;

    const ext = (f.name.split(".").pop() || "").toLowerCase() || "bin";
    const type = guessTypeByMime(f.type);
    log.value = `→ presign for .${ext} (${type})`;
    step.value = "presigned";

    // 1) presigned URL
    const presign = await createPresigned(ext, type);
    log.value += `\n✔ got URL`;

    // 2) PUT مستقیم با progress — XMLHttpRequest چون fetch progress نداره
    step.value = "uploading";
    await new Promise<void>((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      xhr.open("PUT", presign.upload_url, true);
      if (f.type) xhr.setRequestHeader("Content-Type", f.type);
      xhr.upload.onprogress = (e) => {
        if (e.lengthComputable) {
          progress.value = Math.round((e.loaded / e.total) * 100);
        }
      };
      xhr.onload = () => {
        if (xhr.status >= 200 && xhr.status < 300) {
          progress.value = 100;
          resolve();
        } else {
          reject(new Error(`Upload failed: ${xhr.status} ${xhr.responseText}`));
        }
      };
      xhr.onerror = () => reject(new Error("Upload network error"));
      xhr.send(f);
    });
    log.value += `\n✔ uploaded to MinIO`;

    // 3) finalize
    const media = await finalize(presign.id, f.name);
    log.value += `\n✔ finalized (media id=${media.id})`;
    step.value = "finalized";
  } catch (err: any) {
    step.value = "error";
    log.value += `\n✖ ${err?.message || err}`;
    console.error(err);
  }
}
</script>

<template>
  <div class="uploader border-gray-800">
    <h2>Media Upload (S3 presigned)</h2>

    <input
      type="file"
      @change="pickFile"
    >

    <button
      :disabled="!file"
      @click="upload"
    >
      Upload
    </button>

    <div
      v-if="step !== 'idle'"
      class="status"
    >
      <p><strong>Step:</strong> {{ step }}</p>
      <div v-if="step === 'uploading'">
        <progress
          :value="progress"
          max="100"
        /> {{ progress }}%
      </div>
      <pre class="log">{{ log }}</pre>
    </div>
  </div>
</template>
