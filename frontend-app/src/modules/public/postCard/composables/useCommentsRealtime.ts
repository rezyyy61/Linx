import { onMounted, onBeforeUnmount, inject } from "vue";
import { subscribePublic, unsubscribeExact } from "@/lib/echo";
import type { Comment } from "../types/comment.types";
import { adaptComment } from "../adapters/comment.adapter";

type ServerCommentPayload = { comment: any };
type LikePayload = { comment_id: number; liked: boolean; count: number };
type DeletePayload = { comment_id: number; parent_id?: number | null; root_id: number };

export function useCommentRealtime(postId: number | string, ctx?: any) {
  const _ctx = ctx ?? inject<any>("commentCtx");

  let chan: any | null = null;
  let subscribedName = `public.comments.${postId}`;

  function onUpsert(payload: ServerCommentPayload) {
    try {
      const c: Comment = adaptComment(payload.comment);
      _ctx?.upsertFromRealtime?.(c);
    } catch { /* no-op */ }
  }

  function onDeleted(p: DeletePayload) {
    _ctx?.removeFromRealtime?.(p.comment_id, p.parent_id ?? null);
  }

  function onLike(p: LikePayload) {
    _ctx?.patchLikeFromRealtime?.(p.comment_id, p.count);
  }

  async function start() {
    if (chan) return;
    chan = await subscribePublic(`public.comments.${postId}`);
    subscribedName = chan?.name || subscribedName;

    chan.bind("comment.created", onUpsert);
    chan.bind("comment.updated", onUpsert);
    chan.bind("comment.deleted", onDeleted);
    chan.bind("comment.like.toggled", onLike);
  }

  async function stop() {
    if (!chan) return;
    try {
      chan.unbind_all && chan.unbind_all();
      await unsubscribeExact(subscribedName);
    } finally {
      chan = null;
    }
  }

  onMounted(() => { void start(); });
  onBeforeUnmount(() => { void stop(); });

  return { start, stop };
}
