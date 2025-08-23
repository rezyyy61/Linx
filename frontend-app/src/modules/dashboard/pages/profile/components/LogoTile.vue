<template>
  <div class="space-y-3">
    <div
      class="w-full aspect-square rounded-xl border-2 border-dashed flex items-center justify-center overflow-hidden relative"
      :class="logoUrl ? 'border-transparent bg-gray-50 dark:bg-gray-800' : 'bg-teal-500/80 border-teal-300 text-white'"
    >
      <template v-if="logoUrl">
        <img
          :src="logoUrl"
          class="w-full h-full object-cover"
          alt="logo"
        >
        <button
          class="absolute top-2 right-2 text-white bg-black/50 hover:bg-black/70 rounded-full px-2 py-1 text-xs"
          :disabled="busy"
          @click="onRemove"
        >
          Remove
        </button>
      </template>

      <template v-else>
        <div class="flex flex-col items-center gap-2">
          <Icon
            icon="mdi:image-plus"
            width="28"
          />
          <div class="text-sm">
            Upload Logo
          </div>
        </div>
      </template>

      <div
        v-if="busy"
        class="absolute inset-0 bg-black/40 flex items-center justify-center text-white text-sm"
      >
        {{ progressText }}
      </div>
    </div>

    <div class="flex gap-2">
      <button
        class="flex-1 px-4 py-2 rounded-md bg-emerald-600 text-white hover:bg-emerald-500 inline-flex items-center justify-center gap-2 disabled:opacity-60"
        :disabled="busy"
        @click="pick"
      >
        <Icon
          icon="mdi:upload"
          width="18"
        />
        <span>Upload Logo</span>
      </button>

      <button
        v-if="logoUrl"
        class="px-4 py-2 rounded-md bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-gray-100 inline-flex items-center justify-center gap-2 disabled:opacity-60"
        :disabled="busy"
        @click="refreshFromServer"
      >
        <Icon
          icon="mdi:reload"
          width="18"
        />
        <span>Refresh</span>
      </button>
    </div>

    <input
      ref="fileEl"
      type="file"
      class="hidden"
      accept="image/*"
      @change="onPick"
    >
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { Icon } from "@iconify/vue";
import { api, ensureCsrfCookie } from "@/lib/http";
import { useProfileStore } from "@/stores/profile/profile";

const logoUrl = defineModel<string>("logoUrl", { required: false });

const store = useProfileStore();

const fileEl = ref<HTMLInputElement | null>(null);
const busy = ref(false);
const progress = ref<number | null>(null);

const progressText = computed(() => {
  if (progress.value === null) return "Uploading...";
  return `Uploading ${progress.value}%`;
});

function pick() {
  fileEl.value?.click();
}

async function onPick(e: Event) {
  const input = e.target as HTMLInputElement;
  const f = input.files?.[0];
  input.value = "";
  if (!f) return;
  await uploadLogo(f);
}

async function onRemove() {
  if (busy.value) return;
  busy.value = true;
  try {
    await ensureCsrfCookie();
    await store.clearLogo();
    logoUrl.value = "";
  } finally {
    busy.value = false;
  }
}

async function refreshFromServer() {
  if (busy.value) return;
  busy.value = true;
  try {
    await store.fetchMe();
    const p = store.profile;
    const url = Array.isArray(p?.logo) && p!.logo.length > 0
      ? ((p!.logo[0] as any).url ?? (p!.logo[0] as any).public_url ?? "")
      : "";
    if (url) logoUrl.value = url;
  } finally {
    busy.value = false;
  }
}

function getExt(name: string) {
  const m = name.split(".").pop() || "";
  return m.toLowerCase();
}

function getMime(file: File) {
  return file.type || "application/octet-stream";
}

function readArrayBuffer(file: File): Promise<ArrayBuffer> {
  return new Promise((resolve, reject) => {
    const r = new FileReader();
    r.onload = () => resolve(r.result as ArrayBuffer);
    r.onerror = reject;
    r.readAsArrayBuffer(file);
  });
}

async function sha256Hex(file: File) {
  const buf = await readArrayBuffer(file);
  const hash = await crypto.subtle.digest("SHA-256", buf);
  const arr = Array.from(new Uint8Array(hash));
  return arr.map(b => b.toString(16).padStart(2, "0")).join("");
}

function imageSize(file: File): Promise<{ width: number; height: number }> {
  return new Promise((resolve, reject) => {
    const url = URL.createObjectURL(file);
    const img = new Image();
    img.onload = () => {
      URL.revokeObjectURL(url);
      resolve({ width: img.naturalWidth, height: img.naturalHeight });
    };
    img.onerror = (e) => {
      URL.revokeObjectURL(url);
      reject(e);
    };
    img.src = url;
  });
}

function uploadPutWithProgress(url: string, file: File, contentType: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest();
    xhr.open("PUT", url, true);
    xhr.setRequestHeader("Content-Type", contentType);
    xhr.upload.onprogress = (evt) => {
      if (evt.lengthComputable) {
        progress.value = Math.max(0, Math.min(100, Math.round((evt.loaded / evt.total) * 100)));
      }
    };
    xhr.onload = () => {
      if (xhr.status >= 200 && xhr.status < 300) resolve();
      else reject(new Error(`Upload failed ${xhr.status}`));
    };
    xhr.onerror = () => reject(new Error("Upload network error"));
    xhr.send(file);
  });
}

async function uploadLogo(file: File) {
  busy.value = true;
  progress.value = null;
  try {
    const mime = getMime(file);
    const extension = getExt(file.name);
    const hash = await sha256Hex(file);
    const dim = await imageSize(file);

    await ensureCsrfCookie();
    const pres = await api.post("/media/presigned", { extension, type: "image" });

    const resp = (pres.data?.data ?? pres.data) || {};
    const mediaId: number = resp.id;
    const uploadUrl: string = resp.upload_url;
    const signedCT: string = resp.contentType || mime;

    if (!mediaId || !uploadUrl) throw new Error("Upload init failed");

    await uploadPutWithProgress(uploadUrl, file, signedCT);

    await api.post(`/media/${mediaId}/finalize`, {
      extension,
      sha256: hash,
      width: dim.width,
      height: dim.height,
    });

    await store.setLogo(mediaId);

    const p = store.profile;
    const publicUrl = Array.isArray(p?.logo) && p!.logo.length > 0
      ? ((p!.logo[0] as any).url ?? (p!.logo[0] as any).public_url ?? "")
      : "";
    if (publicUrl) {
      logoUrl.value = publicUrl;
    } else {
      const preview = URL.createObjectURL(file);
      logoUrl.value = preview;
      setTimeout(() => URL.revokeObjectURL(preview), 10000);
    }
  } finally {
    progress.value = null;
    busy.value = false;
  }
}
</script>
