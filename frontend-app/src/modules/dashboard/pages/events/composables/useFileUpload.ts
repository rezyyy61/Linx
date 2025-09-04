// src/modules/dashboard/pages/events/composables/useFileUpload.ts
import { api, ensureCsrfCookie } from "@/lib/http";
import { useMediaStore } from "@/stores/post/post.media";

export type PresignResp = {
  id: number;
  upload_url: string;
  key: string;
  contentType: string;
};

export type UploadedMedia = { id: number; url: string | null };

const FQN_EVENT = "App\\Models\\Event\\Event";

export function useFileUpload() {
  const mediaStore = useMediaStore();

  // --- Low-level (API مستقیم) ------------------------------------------------
  async function presign(extension: string, type: "image" | "document"): Promise<PresignResp> {
    await ensureCsrfCookie();
    const { data } = await api.post<PresignResp>("/media/presigned", { extension, type });
    return data;
  }

  async function putToS3(uploadUrl: string, file: File, contentType: string) {
    // امضا روی Content-Type حساس است: دقیقاً همان contentType را بفرست
    const res = await fetch(uploadUrl, {
      method: "PUT",
      headers: { "Content-Type": contentType },
      body: file,
    });
    if (!res.ok) throw new Error(`S3 upload failed: ${res.status}`);
  }

  async function finalize(mediaId: number, extra: Record<string, unknown> = {}) {
    await ensureCsrfCookie();
    // بک‌اند خودش size/mime را می‌خواند؛ اگر بخواهی می‌توانی mime را هم بدهی
    await api.post(`/media/${mediaId}/finalize`, extra);
  }

  async function attachSingle(
    mediaId: number,
    modelType: string,
    modelId: number,
    collection = "event-cover",
    order = 0
  ) {
    await ensureCsrfCookie();
    await api.post(`/media/${mediaId}/attach-single`, {
      model_type: modelType,
      model_id: modelId,
      collection,
      order,
    });
  }

  async function attach(
    mediaId: number,
    modelType: string,
    modelId: number,
    collection = "event-document",
    order = 0
  ) {
    await ensureCsrfCookie();
    await api.post(`/media/${mediaId}/attach`, {
      model_type: modelType,
      model_id: modelId,
      collection,
      order,
    });
  }

  async function detachFromModel(
    mediaId: number,
    modelType: string,
    modelId: number,
    collection?: string
  ) {
    await ensureCsrfCookie();
    await api.post(`/media/${mediaId}/detach`, {
      model_type: modelType,
      model_id: modelId,
      collection,
    });
  }

  // --- Helpers برای Event -----------------------------------------------------
  async function attachCoverToEvent(mediaId: number, eventId: number) {
    return attachSingle(mediaId, FQN_EVENT, eventId, "event-cover", 0);
  }
  async function attachDocToEvent(mediaId: number, eventId: number, order = 0) {
    return attach(mediaId, FQN_EVENT, eventId, "event-document", order);
  }
  async function detachFromEvent(mediaId: number, eventId: number, collection?: "event-cover" | "event-document") {
    return detachFromModel(mediaId, FQN_EVENT, eventId, collection);
  }

  // --- High-level (با استور realtime و نوار پیشرفت) --------------------------
  async function uploadOne(file: File, kind: "image" | "document"): Promise<UploadedMedia> {
    // مسیر کامل: presign → PUT → finalize → fetch(public_url)
    const task = mediaStore.createTask(file, kind);
    await mediaStore.presign(task);
    await mediaStore.uploadToS3(task);
    // finalize با mime صحیح
    await mediaStore.finalize(task, { mime: task.contentType });

    const media = await mediaStore.fetchMedia(task.id!);
    const url = (media.public_url || media.url || null) as string | null;
    return { id: task.id!, url };
  }

  return {
    // low-level
    presign,
    putToS3,
    finalize,
    attachSingle,
    attach,
    detachFromModel,
    // helpers for events
    attachCoverToEvent,
    attachDocToEvent,
    detachFromEvent,
    // high-level
    uploadOne,
  };
}
