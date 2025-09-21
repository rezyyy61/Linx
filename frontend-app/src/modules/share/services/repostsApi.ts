import { api, ensureCsrfCookie } from '@/lib/http';

export type ShareableAlias = 'post' | 'event';

export interface CreateRepostGenericPayload {
  shareable_alias: ShareableAlias;
  shareable_id: number;
  text?: string | null;
  visibility?: string | null;
}

export interface CreateRepostResponse {
  id: number;
  url: string;
}

export async function createRepostGeneric(payload: CreateRepostGenericPayload): Promise<CreateRepostResponse> {
  await ensureCsrfCookie();
  const { data } = await api.post<CreateRepostResponse>('/reposts', payload);
  return data;
}
