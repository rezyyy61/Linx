import { defineStore } from "pinia";
import { api, ensureCsrfCookie } from "@/lib/http";

export type MediaType = "IMAGE" | "VIDEO" | "AUDIO" | "DOCUMENT";

export type PresignedResponse = {
  id: number;
  upload_url: string;
  key: string;
  contentType: string;
};

export type MediaItem = {
  id: number;
  url: string | null;
  key?: string;
  type?: MediaType;
  status?: string;
  ext?: string | null;
  size?: number | null;
  mime?: string | null;
  width?: number | null;
  height?: number | null;
  duration?: number | null;
  meta?: Record<string, any> | null;
  created_at?: string;
  updated_at?: string;
};

export type UploadState = "idle" | "presigning" | "uploading" | "finalizing" | "done" | "error";

export type UploadTask = {
  id: string;
  file: File;
  type: MediaType;
  state: UploadState;
  progress: number | null;
  mediaId?: number;
  media?: MediaItem;
  error?: unknown;
};

function getExt(file: File): string {
  const byName = /\.([a-zA-Z0-9]+)$/.exec(file.name)?.[1];
  const byMime = file.type?.split("/")?.[1];
  return (byName || byMime || "bin").toLowerCase();
}

function loadImageDims(file: File): Promise<{ width?: number; height?: number }> {
  return new Promise((resolve) => {
    if (!file.type.startsWith("image/")) return resolve({});
    const img = new Image();
    img.onload = () => resolve({ width: img.naturalWidth, height: img.naturalHeight });
    img.onerror = () => resolve({});
    img.src = URL.createObjectURL(file);
  });
}

export const useMediaStore = defineStore("media", {
  state: () => ({
    tasks: [] as UploadTask[],
    byId: {} as Record<number, MediaItem>,
    loading: false,
    lastError: null as unknown,
  }),

  getters: {
    get: (s) => (id: number) => s.byId[id] || null,
    all: (s) => Object.values(s.byId),
  },

  actions: {
    addTask(file: File, type: MediaType): UploadTask {
      const t: UploadTask = {
        id: crypto.randomUUID(),
        file,
        type,
        state: "idle",
        progress: null,
      };
      this.tasks.unshift(t);
      return t;
    },

    removeTask(localId: string) {
      this.tasks = this.tasks.filter((t) => t.id !== localId);
    },

    async presign(extension: string, type: MediaType): Promise<PresignedResponse> {
      await ensureCsrfCookie();
      const res = await api.post<PresignedResponse>("/media/presigned", {
        extension,
        type, // "IMAGE" | "DOCUMENT" | ...
      });
      return res.data;
    },

    async uploadToS3(uploadUrl: string, file: File, contentType: string): Promise<void> {
      const r = await fetch(uploadUrl, {
        method: "PUT",
        headers: { "Content-Type": contentType },
        body: file,
      });
      if (!r.ok) {
        const msg = await r.text().catch(() => "");
        throw new Error(`S3 PUT failed: ${r.status} ${msg}`);
      }
    },

    async finalize(mediaId: number, extra?: Partial<Pick<MediaItem, "width" | "height" | "duration" | "meta">>): Promise<MediaItem> {
      await ensureCsrfCookie();
      const res = await api.post<MediaItem>(`/media/${mediaId}/finalize`, extra || {});
      const item = res.data;
      this.byId[item.id] = item;
      return item;
    },

    async uploadFile(file: File, type: MediaType): Promise<MediaItem> {
      const task = this.addTask(file, type);
      try {
        task.state = "presigning";
        const ext = getExt(file);
        const p = await this.presign(ext, type);

        task.state = "uploading";
        await this.uploadToS3(p.upload_url, file, p.contentType);

        task.state = "finalizing";
        const dims = await loadImageDims(file);
        const media = await this.finalize(p.id, dims);

        task.state = "done";
        task.mediaId = media.id;
        task.media = media;

        this.byId[media.id] = media;
        return media;
      } catch (e) {
        task.state = "error";
        task.error = e;
        this.lastError = e;
        throw e;
      }
    },

    async fetchOne(mediaId: number): Promise<MediaItem> {
      this.loading = true;
      this.lastError = null;
      try {
        const res = await api.get<MediaItem>(`/media/${mediaId}`);
        const m = res.data;
        this.byId[m.id] = m;
        return m;
      } catch (e) {
        this.lastError = e;
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async destroy(mediaId: number): Promise<void> {
      await ensureCsrfCookie();
      await api.delete(`/media/${mediaId}`);
      delete this.byId[mediaId];
    },

    toOrderedRefs(ids: number[]): Array<{ id: number; order: number }> {
      return ids.map((id, idx) => ({ id, order: idx }));
    },
  },
});
