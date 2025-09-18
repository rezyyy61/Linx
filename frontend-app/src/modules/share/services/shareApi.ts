import type { CreateSharePayload, CreateShareResponse } from '../types';
import {api, ensureCsrfCookie} from "@/lib/http";


export async function createShare(payload: CreateSharePayload): Promise<CreateShareResponse> {
  await ensureCsrfCookie();
  const { data } = await api.post<CreateShareResponse>('/share', payload);
  return data;
}
