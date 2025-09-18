import { api, ensureCsrfCookie } from '@/lib/http';

export interface CreateRepostPayload {
  text?: string | null;
  visibility?: string | null;
}

export interface CreateRepostResponse {
  id: number;
  url: string;
}

export async function createRepost(postId: number, payload: CreateRepostPayload): Promise<CreateRepostResponse> {
  await ensureCsrfCookie();
  const { data } = await api.post<CreateRepostResponse>(`/posts/${postId}/repost`, payload);
  return data;
}
