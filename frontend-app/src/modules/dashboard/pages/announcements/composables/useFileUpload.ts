import { api, ensureCsrfCookie } from "@/lib/http";
import { useMediaStore } from "@/stores/post/post.media";

export type PresignResp = {
  id: number;
  upload_url: string;
  key: string;
  contentType: string;
};

export type UploadedMedia = { id: number; url: string | null };

const FQN_CAMPAIGN = "App\\Models\\Campaign\\Campaign";

export function useFileUpload() {
  const mediaStore = useMediaStore();

  async function presign(extension: string, type: "image" | "document"): Promise<PresignResp> {
    await ensureCsrfCookie();
    const { data } = await api.post<PresignResp>("/media/presigned", { extension, type });
    return data;
  }

  async function putToS3(uploadUrl: string, file: File, contentType: string) {
    const res = await fetch(uploadUrl, {
      method: "PUT",
      headers: { "Content-Type": contentType },
      body: file,
    });
    if (!res.ok) throw new Error(`S3 upload failed: ${res.status}`);
  }

  async function finalize(mediaId: number, extra: Record<string, unknown> = {}) {
    await ensureCsrfCookie();
    await api.post(`/media/${mediaId}/finalize`, extra);
  }

  async function attachSingle(
    mediaId: number,
    modelType: string,
    modelId: number,
    collection = "campaign-cover",
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
    collection = "campaign-document",
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

  async function attachCoverToCampaign(mediaId: number, campaignId: number) {
    return attachSingle(mediaId, FQN_CAMPAIGN, campaignId, "campaign-cover", 0);
  }

  async function attachDocToCampaign(mediaId: number, campaignId: number, order = 0) {
    return attach(mediaId, FQN_CAMPAIGN, campaignId, "campaign-document", order);
  }

  async function detachFromCampaign(mediaId: number, campaignId: number, collection?: "campaign-cover" | "campaign-document") {
    return detachFromModel(mediaId, FQN_CAMPAIGN, campaignId, collection);
  }

  async function uploadOne(file: File, kind: "image" | "document"): Promise<UploadedMedia> {
    const task = mediaStore.createTask(file, kind);
    await mediaStore.presign(task);
    await mediaStore.uploadToS3(task);
    await mediaStore.finalize(task, { mime: task.contentType });
    const media = await mediaStore.fetchMedia(task.id!);
    const url = (media.public_url || media.url || null) as string | null;
    return { id: task.id!, url };
  }

  return {
    presign,
    putToS3,
    finalize,
    attachSingle,
    attach,
    detachFromModel,
    attachCoverToCampaign,
    attachDocToCampaign,
    detachFromCampaign,
    uploadOne,
  };
}
