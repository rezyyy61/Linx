import { ref } from "vue";
import { useMediaStore } from "@/stores/post/post.media";
import { useFileUpload } from "./useFileUpload";

const FQN_EVENT = "App\\Models\\Event\\Event";

export function useEventMedia(eventId?: number, init?: { cover_id?: number | null; cover_url?: string | null; documents?: Array<{ id: number; url: string | null }> }) {
  const mediaStore = useMediaStore();
  const { attach, attachSingle } = useFileUpload();

  const coverId = ref<number | null>(init?.cover_id ?? null);
  const coverUrl = ref<string | null>(init?.cover_url ?? null);
  const docs = ref<Array<{ id: number; url: string | null }>>(Array.isArray(init?.documents) ? [...init!.documents] : []);

  async function setCover(newId: number) {
    coverId.value = newId;
    const m = await mediaStore.fetchMedia(newId);
    coverUrl.value = (m.public_url || m.url || null) as string | null;
    if (eventId) await attachSingle(newId, FQN_EVENT, eventId, "event-cover", 0);
  }

  async function clearCover() {
    if (!coverId.value) {
      coverUrl.value = null;
      return;
    }
    const id = coverId.value;
    if (eventId) {
      await mediaStore.detachFromModel(id, FQN_EVENT, eventId, "event-cover");
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
    if (eventId) await attach(id, FQN_EVENT, eventId, "event-document", order);
  }

  async function removeDoc(id: number) {
    if (eventId) {
      await mediaStore.detachFromModel(id, FQN_EVENT, eventId, "event-document");
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
