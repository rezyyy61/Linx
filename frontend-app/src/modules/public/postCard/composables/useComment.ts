import { reactive, ref, computed } from "vue";
import {
  listPostRootComments,
  listChildComments,
  createPostComment,
  updateComment as apiUpdateComment,
  deleteComment as apiDeleteComment,
  toggleLike,
  listLikers,
} from "../api/comments";
import { adaptComment, adaptCommentCursor, adaptUserCursor } from "../adapters/comment.adapter";
import type { Comment, Cursor, PublicMiniUser, ToggleLikeResult } from "../types/comment.types";

type ChildrenState = {
  items: Comment[];
  next: string | null;
  loading: boolean;
  loadedOnce: boolean;
};

function mergeComment(target: Comment, src: Comment) {
  target.body = src.body;
  target.status = src.status;
  target.repliesCount = src.repliesCount;
  target.reactionsCount = src.reactionsCount;
  target.likedByMe = src.likedByMe;
  target.updatedAt = src.updatedAt;
  target.user = src.user ?? target.user;
}

export function useComment(postId: number | string) {
  const roots = ref<Comment[]>([]);
  const next = ref<string | null>(null);
  const loading = ref(false);
  const byId = reactive(new Map<number, Comment>());
  const children = reactive(new Map<number, ChildrenState>());

  function ensureChildState(parentId: number): ChildrenState {
    if (!children.has(parentId)) {
      children.set(parentId, { items: [], next: null, loading: false, loadedOnce: false });
    }
    return children.get(parentId)!;
  }

  function indexItems(items: Comment[]) {
    for (const c of items) byId.set(c.id, c);
  }

  async function fetchRoots(opts: { cursor?: string | null; perPage?: number; sort?: "new" | "top" } = {}) {
    if (loading.value) return;
    loading.value = true;
    try {
      const res = await listPostRootComments(postId, opts);
      const data = adaptCommentCursor(res);
      if (!opts.cursor) roots.value = data.items;
      else roots.value = roots.value.concat(data.items);
      next.value = data.cursor.next;
      indexItems(data.items);
    } finally {
      loading.value = false;
    }
  }

  async function fetchMoreRoots() {
    if (!next.value) return;
    return fetchRoots({ cursor: next.value });
  }

  async function fetchChildren(parentId: number, cursor?: string | null) {
    const st = ensureChildState(parentId);
    if (st.loading) return;
    st.loading = true;
    try {
      const res = await listChildComments(parentId, { cursor: cursor || undefined });
      const data = adaptCommentCursor(res);
      if (!cursor) st.items = data.items;
      else st.items = st.items.concat(data.items);
      st.next = data.cursor.next;
      st.loadedOnce = true;
      indexItems(data.items);
    } finally {
      st.loading = false;
    }
  }

  async function fetchMoreChildren(parentId: number) {
    const st = ensureChildState(parentId);
    if (!st.next) return;
    return fetchChildren(parentId, st.next);
  }

  async function submit(body: string, parentId?: number | null) {
    const res = await createPostComment(postId, body, parentId ?? null);
    const item = adaptComment(res.data ?? res);

    const existed = byId.get(item.id);
    if (existed) {
      mergeComment(existed, item);
      return existed;
    }
    byId.set(item.id, item);
    if (parentId) {
      const st = ensureChildState(parentId);
      st.items.unshift(item);
      const p = byId.get(parentId);
      if (p) p.repliesCount = (p.repliesCount || 0) + 1;
    } else {
      roots.value.unshift(item);
    }

    return item;
  }


  async function edit(commentId: number, body: string) {
    const res = await apiUpdateComment(commentId, body);
    const fresh = adaptComment(res.data ?? res);
    const cur = byId.get(commentId);
    if (cur) mergeComment(cur, fresh);
    return cur ?? fresh;
  }

  async function remove(commentId: number) {
    await apiDeleteComment(commentId);
    const cur = byId.get(commentId);
    if (!cur) return;
    const parentId = cur.parentId;
    if (parentId) {
      const st = ensureChildState(parentId);
      const i = st.items.findIndex(x => x.id === commentId);
      if (i !== -1) st.items.splice(i, 1);
      const p = byId.get(parentId);
      if (p && p.repliesCount > 0) p.repliesCount -= 1;
    } else {
      const i = roots.value.findIndex(x => x.id === commentId);
      if (i !== -1) roots.value.splice(i, 1);
    }
    byId.delete(commentId);
  }

  async function toggleLikeOn(id: number) {
    const r: ToggleLikeResult = await toggleLike(id);
    const c = byId.get(id);
    if (c) {
      c.likedByMe = r.liked;
      c.reactionsCount = r.count;
    }
    return r;
  }

  async function getLikers(commentId: number, cursor?: string | null, perPage = 20): Promise<Cursor<PublicMiniUser>> {
    const res = await listLikers(commentId, cursor, perPage);
    return adaptUserCursor(res);
  }

  // ===== hooks مخصوص realtime =====
  function upsertFromRealtime(c: Comment) {
    byId.set(c.id, { ...(byId.get(c.id) || {} as any), ...c });
    if (!c.parentId) {
      const i = roots.value.findIndex(x => x.id === c.id);
      if (i >= 0) roots.value[i] = { ...roots.value[i], ...c };
      else roots.value.unshift(c);
      return;
    }
    const st = ensureChildState(c.parentId);
    const j = st.items.findIndex(x => x.id === c.id);
    if (j >= 0) st.items[j] = { ...st.items[j], ...c };
    else st.items.push(c);
  }

  function removeFromRealtime(id: number, parentId: number | null) {
    byId.delete(id);
    if (!parentId) {
      const i = roots.value.findIndex(x => x.id === id);
      if (i !== -1) roots.value.splice(i, 1);
      return;
    }
    const st = ensureChildState(parentId);
    const j = st.items.findIndex(x => x.id === id);
    if (j !== -1) st.items.splice(j, 1);
  }

  function patchLikeFromRealtime(id: number, count: number) {
    const c = byId.get(id);
    if (c) c.reactionsCount = count;
  }

  async function expandChain(c: Comment) {
    let pid = c.parentId
    while (pid) {
      await fetchChildren(pid)
      pid = byId.get(pid)?.parentId || null
    }
  }

  function scrollToComment(id: number) {
    const el = document.getElementById(`c-${id}`)
    if (el) {
      el.scrollIntoView({ behavior: "smooth", block: "center" })
      el.classList.add("ring-2","ring-indigo-500","ring-offset-2","ring-offset-white","dark:ring-offset-zinc-900")
      setTimeout(() => {
        el.classList.remove("ring-2","ring-indigo-500","ring-offset-2","ring-offset-white","dark:ring-offset-zinc-900")
      }, 2000)
    }
  }

  async function deepExpandUntilFound(parentId: number, targetId: number, depth = 3) {
    if (depth <= 0) return
    await fetchChildren(parentId)
    if (byId.get(targetId)) return
    const kids = children.get(parentId)?.items || []
    for (const k of kids) {
      if (k.repliesCount > 0) {
        await deepExpandUntilFound(k.id, targetId, depth - 1)
        if (byId.get(targetId)) return
      }
    }
  }

  async function reveal(commentId: number) {
    const existing = byId.get(commentId)
    if (existing) {
      await expandChain(existing)
      scrollToComment(commentId)
      return existing
    }

    if (!roots.value.length) await fetchRoots()

    for (const r of roots.value) {
      await fetchChildren(r.id)
      const found = byId.get(commentId)
      if (found) { await expandChain(found); scrollToComment(commentId); return found }
    }

    for (const r of roots.value) {
      await deepExpandUntilFound(r.id, commentId, 3)
      const found = byId.get(commentId)
      if (found) { await expandChain(found); scrollToComment(commentId); return found }
    }

    return null
  }

  const hasMoreRoots = computed(() => Boolean(next.value));
  const getChildren = (parentId: number) => ensureChildState(parentId).items;
  const hasMoreChildren = (parentId: number) => Boolean(ensureChildState(parentId).next);
  const isChildrenLoading = (parentId: number) => ensureChildState(parentId).loading;
  const childrenLoadedOnce = (parentId: number) => ensureChildState(parentId).loadedOnce;

  return {
    roots,
    loading,
    hasMoreRoots,
    fetchRoots,
    fetchMoreRoots,
    fetchChildren,
    fetchMoreChildren,
    getChildren,
    hasMoreChildren,
    isChildrenLoading,
    childrenLoadedOnce,
    submit,
    edit,
    remove,
    toggleLikeOn,
    getLikers,
    byId,
    reveal,
    scrollToComment,

    // realtime handlers
    upsertFromRealtime,
    removeFromRealtime,
    patchLikeFromRealtime,
  };
}
