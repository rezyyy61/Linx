<template>
  <div class="bg-white/80 dark:bg-gray-800/80 border border-slate-200/60 dark:border-slate-800 rounded-2xl p-6 md:p-8 backdrop-blur">
    <div class="mb-4 flex items-center justify-between">
      <h3 class="text-lg font-semibold">
        {{ t('profile.media.documents') }}
      </h3>
      <div class="text-xs text-slate-500 dark:text-slate-400">
        {{ filesModel.length }} {{ t('common.selected') }}
      </div>
    </div>

    <div
      class="relative rounded-2xl border-2 border-dashed border-slate-300 dark:border-slate-700 bg-white/50 dark:bg-gray-900/40 p-5 cursor-pointer"
      :class="dragFiles ? 'ring-2 ring-emerald-500/40 border-emerald-400/60' : ''"
      @click="openFilesPicker"
      @dragover.prevent="dragFiles=true"
      @dragleave.prevent="dragFiles=false"
      @drop.prevent="onFilesDrop"
    >
      <input
        ref="filesInput"
        type="file"
        multiple
        :accept="acceptList"
        class="hidden"
        @change="onFilesPicked"
      >
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex items-center gap-3">
          <div class="h-12 w-12 rounded-xl border border-slate-200 dark:border-slate-700 grid place-items-center">
            <span class="text-2xl">📄</span>
          </div>
          <div>
            <div class="text-sm text-slate-700 dark:text-slate-200">
              {{ t('profile.media.addFiles') }}
            </div>
            <div class="text-xs text-slate-500 dark:text-slate-400">
              {{ t('profile.media.fileHint') }}
            </div>
          </div>
        </div>
        <div class="flex flex-wrap gap-2">
          <span class="text-[10px] px-2 py-1 rounded-md border">.pdf</span>
          <span class="text-[10px] px-2 py-1 rounded-md border">.docx</span>
          <span class="text-[10px] px-2 py-1 rounded-md border">.pptx</span>
          <span class="text-[10px] px-2 py-1 rounded-md border">.xlsx</span>
          <span class="text-[10px] px-2 py-1 rounded-md border">.csv</span>
          <span class="text-[10px] px-2 py-1 rounded-md border">.txt</span>
        </div>
      </div>

      <div
        v-if="busy"
        class="absolute inset-0 bg-black/40 flex items-center justify-center text-white text-sm"
      >
        {{ progressText }}
      </div>
    </div>

    <p
      v-if="filesError"
      class="mt-2 text-sm text-red-600"
    >
      {{ filesError }}
    </p>

    <div
      v-if="filesModel.length"
      class="mt-6 grid gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3"
    >
      <div
        v-for="item in filesModel"
        :key="item.id"
        class="group relative rounded-xl border border-slate-200/70 dark:border-slate-800 overflow-hidden bg-white/70 dark:bg-gray-900/50"
      >
        <div class="p-4 flex items-start gap-3">
          <div class="shrink-0 h-11 w-11 rounded-lg border grid place-items-center bg-white/80 dark:bg-gray-900/60">
            <span class="text-xl">{{ fileEmoji(item.ext || 'file') }}</span>
          </div>
          <div class="min-w-0 flex-1">
            <div
              class="text-sm font-medium truncate"
              :title="item.name"
            >
              {{ item.name }}
            </div>
            <div class="text-xs text-slate-500">
              {{ formatSize(item.size || 0) }} · {{ (item.ext || 'file').toUpperCase() }}
            </div>
          </div>
        </div>

        <div class="px-4 pb-4 flex gap-2">
          <a
            :href="item.previewUrl || '#'"
            target="_blank"
            rel="noopener"
            class="px-2.5 py-1.5 text-xs rounded-md border"
          >
            {{ t('profile.media.open') }}
          </a>
          <button
            class="px-2.5 py-1.5 text-xs rounded-md border disabled:opacity-60"
            :disabled="busy"
            @click="removeItem(item.id)"
          >
            {{ t('profile.links.remove') }}
          </button>
        </div>
      </div>
    </div>

    <div
      v-else
      class="mt-6 text-sm text-slate-500 dark:text-slate-400"
    >
      {{ t('profile.media.noFiles') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { api, ensureCsrfCookie } from "@/lib/http"
import { useProfileStore } from "@/stores/profile/profile"

type DocItem = {
  id: string
  name: string
  size: number
  type: string
  ext: string
  previewUrl: string
  file?: File
}

const { t } = useI18n()
const store = useProfileStore()

const filesModel = defineModel<DocItem[]>("files", { required: true })

const filesInput = ref<HTMLInputElement | null>(null)
const dragFiles = ref(false)
const filesError = ref("")
const busy = ref(false)
const totalToUpload = ref(0)
const uploadedCount = ref(0)

const acceptList = [
  "application/pdf",
  "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
  "application/vnd.openxmlformats-officedocument.presentationml.presentation",
  "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
  "text/plain",
  "text/csv",
  "application/csv"
].join(",")

const progressText = computed(() => {
  if (!busy.value) return ""
  if (totalToUpload.value === 0) return t("common.uploading") as string
  return `${t("common.uploading")} ${uploadedCount.value}/${totalToUpload.value}`
})

function openFilesPicker() { filesInput.value?.click() }
function onFilesPicked(e: Event) {
  const input = e.target as HTMLInputElement
  if (!input.files) return
  handleFiles(Array.from(input.files))
  if (filesInput.value) filesInput.value.value = ""
}
function onFilesDrop(e: DragEvent) {
  dragFiles.value = false
  const list = e.dataTransfer?.files ? Array.from(e.dataTransfer.files) : []
  if (list.length) handleFiles(list)
}

function isAccepted(f: File) {
  const tpe = f.type || "application/octet-stream"
  return acceptList.split(",").includes(tpe)
}
function extFromName(name: string) {
  const m = name.toLowerCase().match(/\.([a-z0-9]+)$/)
  return m ? m[1] : "file"
}
function fileEmoji(ext: string) {
  if (ext === "pdf") return "📕"
  if (ext === "docx") return "📝"
  if (ext === "pptx") return "📊"
  if (ext === "xlsx" || ext === "csv") return "📈"
  if (ext === "txt") return "📄"
  return "📁"
}
function formatSize(bytes: number) {
  if (bytes < 1024) return `${bytes} B`
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`
}
function cryptoRandomId() {
  // @ts-ignore
  return typeof crypto !== "undefined" && "randomUUID" in crypto ? crypto.randomUUID() : `id_${Math.random().toString(36).slice(2, 10)}`
}
function fileToItem(f: File): DocItem {
  const url = URL.createObjectURL(f)
  return { id: cryptoRandomId(), name: f.name, size: f.size, type: f.type || "application/octet-stream", ext: extFromName(f.name), previewUrl: url, file: f }
}

async function sha256Hex(file: File) {
  const buf = await file.arrayBuffer()
  const hash = await crypto.subtle.digest("SHA-256", buf)
  const arr = Array.from(new Uint8Array(hash))
  return arr.map(b => b.toString(16).padStart(2, "0")).join("")
}

async function presign(ext: string) {
  await ensureCsrfCookie()
  const res = await api.post("/media/presigned", { extension: ext, type: "document" })
  return (res.data?.data ?? res.data) as { id: number; upload_url: string; key: string; contentType: string }
}
function uploadPut(url: string, file: File, contentType: string): Promise<void> {
  return new Promise((resolve, reject) => {
    const xhr = new XMLHttpRequest()
    xhr.open("PUT", url, true)
    xhr.setRequestHeader("Content-Type", contentType || "application/octet-stream")
    xhr.onload = () => { if (xhr.status >= 200 && xhr.status < 300) resolve(); else reject(new Error(String(xhr.status))) }
    xhr.onerror = () => reject(new Error("network"))
    xhr.send(file)
  })
}
async function finalize(id: number, meta: { extension: string; sha256: string }) {
  await api.post(`/media/${id}/finalize`, meta)
}
async function attachDocument(mediaId: number, order: number) {
  await store.addDocument(mediaId, order)
}

function publicUrlFromKey(key?: string | null) {
  const base = (import.meta as any).env?.VITE_S3_PUBLIC_BASE as string | undefined
  if (!key || !base) return ""
  return `${base.replace(/\/+$/,"")}/${key}`
}
async function refreshFromServer() {
  await store.fetchMe()
  const docs = store.profile?.documents || []
  filesModel.value = docs.map((m: any) => ({
    id: String(m.id),
    name: m.key ? (m.key.split("/").pop() || `file-${m.id}`) : `file-${m.id}`,
    size: m.size || 0,
    type: m.mime || "application/octet-stream",
    ext: (m.ext || (m.key ? (m.key.split(".").pop() || "file") : "file")).toLowerCase(),
    previewUrl: m.url || m.public_url || publicUrlFromKey(m.key) || "#",
  }))
}

async function handleFiles(files: File[]) {
  filesError.value = ""
  const accepted = files.filter(f => isAccepted(f) && f.size <= 20 * 1024 * 1024)
  if (accepted.length === 0) {
    filesError.value = t('profile.media.unsupportedType') as string
    return
  }

  const local = accepted.map(fileToItem)
  filesModel.value = [...filesModel.value, ...local]

  busy.value = true
  totalToUpload.value = local.length
  uploadedCount.value = 0
  try {
    for (let i = 0; i < local.length; i++) {
      const item = local[i]
      const file = item.file!
      const ext = item.ext
      const mime = file.type || "application/octet-stream"
      const hash = await sha256Hex(file)

      const p = await presign(ext)
      await uploadPut(p.upload_url, file, p.contentType || mime)
      await finalize(p.id, { extension: ext, sha256: hash })
      await attachDocument(p.id, filesModel.value.length + i)

      uploadedCount.value = i + 1
    }
    await refreshFromServer()
  } finally {
    busy.value = false
    totalToUpload.value = 0
    uploadedCount.value = 0
  }
}

async function removeItem(id: string) {
  const isNumeric = /^\d+$/.test(id)
  const idx = filesModel.value.findIndex(i => i.id === id)
  if (idx >= 0) {
    const [removed] = filesModel.value.splice(idx, 1)
    if (removed?.file && removed.previewUrl) { try { URL.revokeObjectURL(removed.previewUrl) } catch { /* empty */ } }
  }
  if (isNumeric) {
    await store.removeDocument(parseInt(id, 10))
    await refreshFromServer()
  }
}
</script>
