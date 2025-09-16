import { api, ensureCsrfCookie } from "@/lib/http";

export const COMMENTABLE_POST = "App\\Models\\Post\\Post";

type ListOpts = { cursor?: string | null; perPage?: number; sort?: "new" | "top" };

export async function listPostRootComments(postId: number | string, opts: ListOpts = {}) {
  const { cursor, perPage = 20, sort = "new" } = opts;
  const { data } = await api.get("/public/v1/comments", {
    params: {
      commentable_type: COMMENTABLE_POST,
      commentable_id: postId,
      cursor: cursor || undefined,
      per_page: perPage,
      sort,
    },
  });
  return data;
}

export async function listChildComments(parentId: number, opts: Omit<ListOpts, "sort"> = {}) {
  const { cursor, perPage = 20 } = opts;
  const { data } = await api.get(`/public/v1/comments/${parentId}/children`, {
    params: {
      cursor: cursor || undefined,
      per_page: perPage,
    },
  });
  console.log('child: ', data);
  return data;
}

export async function listLikers(commentId: number, cursor?: string | null, perPage = 20) {
  const { data } = await api.get(`/public/v1/comments/${commentId}/likes`, {
    params: { cursor: cursor || undefined, per_page: perPage },
  });
  return data;
}

export async function createPostComment(postId: number | string, body: string, parentId?: number | null) {
  await ensureCsrfCookie();
  const payload = {
    commentable_type: COMMENTABLE_POST,
    commentable_id: postId,
    parent_id: parentId ?? null,
    body,
  };
  const { data } = await api.post("/public/v1/comments", payload);
  return data;
}

export async function updateComment(commentId: number, body: string) {
  await ensureCsrfCookie();
  const { data } = await api.patch(`/public/v1/comments/${commentId}`, { body });
  return data;
}

export async function deleteComment(commentId: number) {
  await ensureCsrfCookie();
  const { data } = await api.delete(`/public/v1/comments/${commentId}`);
  return data;
}

export async function toggleLike(commentId: number) {
  await ensureCsrfCookie();
  const { data } = await api.post(`/public/v1/comments/${commentId}/like`);
  return data as { liked: boolean; count: number };
}
