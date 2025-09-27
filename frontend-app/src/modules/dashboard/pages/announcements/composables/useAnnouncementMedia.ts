import { ref } from "vue";
import { useMediaStore } from "@/stores/post/post.media";
import { useFileUpload } from "@/modules/dashboard/pages/events/composables/useFileUpload";

const FQN_ANNOUNCEMENT = "App\\Models\\Announcement\\Announcement";
const COVER_COLLECTION = "announcement-cover";
const DOCS_COLLECTION = "announcement-document";

export function useAnnouncementMedia(
  announcementId?: number,
  init?: { cover_id?: number | null; cover_url?: string | null; documents?: Array<{ id: number; url: string | null }> }
) {
  const mediaStore = useMediaStore();
  const { attach, attachSingle } = useFileUpload();

  const coverId = ref<number | null>(init?.cover_id ?? null);
  const coverUrl = ref<string | null>(init?.cover_url ?? null);
  const docs = ref<Array<{ id: number; url: string | null }>>(Array.isArray(init?.documents) ? [...init!.documents] : []);

  async function setCover(newId: number) {
    if (announcementId && coverId.value && coverId.value !== newId) {
      await mediaStore.detachFromModel(coverId.value, FQN_ANNOUNCEMENT, announcementId, COVER_COLLECTION);
      await mediaStore.deleteMediaOnServer(coverId.value);
    }
    coverId.value = newId;
    const m = await mediaStore.fetchMedia(newId);
    coverUrl.value = (m.public_url || m.url || null) as string | null;
    if (announcementId) await attachSingle(newId, FQN_ANNOUNCEMENT, announcementId, COVER_COLLECTION, 0);
  }

  async function clearCover() {
    if (!coverId.value) {
      coverUrl.value = null;
      return;
    }
    const id = coverId.value;
    if (announcementId) {
      await mediaStore.detachFromModel(id, FQN_ANNOUNCEMENT, announcementId, COVER_COLLECTION);
      await mediaStore.deleteMediaOnServer(id);
    } else {
      await mediaStore.deleteMediaOnServer(id);
    }
    coverId.value = null;
    coverUrl.value = null;
  }

  async function addDoc(id: number, order: number) {
    const m = await mediaStore.fetchMedia(id);
    const url = (m.public_url || m.url || null) as string | null;
    if (!docs.value.some(d => d.id === id)) docs.value.push({ id, url });
    if (announcementId) await attach(id, FQN_ANNOUNCEMENT, announcementId, DOCS_COLLECTION, order);
  }

  async function removeDoc(id: number) {
    if (announcementId) {
      await mediaStore.detachFromModel(id, FQN_ANNOUNCEMENT, announcementId, DOCS_COLLECTION);
      await mediaStore.deleteMediaOnServer(id);
    } else {
      await mediaStore.deleteMediaOnServer(id);
    }
    docs.value = docs.value.filter(x => x.id !== id);
  }

  return {
    coverId,
    coverUrl,
    docs,
    setCover,
    clearCover,
    addDoc,
    removeDoc,
  };
}
