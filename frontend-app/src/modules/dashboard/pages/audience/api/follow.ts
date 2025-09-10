import { api, ensureCsrfCookie } from '@/lib/http'
import type { Id, Paginated, ProfileLite, FollowRequestItem } from '../types'

export async function meLite(): Promise<{ ok: boolean; data: ProfileLite }> {
  const { data } = await api.get('/profile/me-lite')
  return data
}

export async function listFollowers(userId: Id, params: { page?: number; per_page?: number } = {}): Promise<Paginated<ProfileLite>> {
  const { data } = await api.get(`/users/${userId}/followers`, { params })
  return {
    data: (data.data ?? data) as ProfileLite[],
    meta: data.meta ?? { current_page: 1, per_page: params.per_page ?? 15, total: (data.data ?? []).length, last_page: 1 }
  }
}

export async function listFollowings(userId: Id, params: { page?: number; per_page?: number } = {}): Promise<Paginated<ProfileLite>> {
  const { data } = await api.get(`/users/${userId}/followings`, { params })
  return {
    data: (data.data ?? data) as ProfileLite[],
    meta: data.meta ?? { current_page: 1, per_page: params.per_page ?? 15, total: (data.data ?? []).length, last_page: 1 }
  }
}

export async function listSuggestions(userId: Id, limit = 10): Promise<ProfileLite[]> {
  const { data } = await api.get(`/users/${userId}/follow/suggestions`, { params: { limit } })
  return (data.data ?? data) as ProfileLite[]
}

export async function follow(userId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.post(`/users/${userId}/follow`)
}

export async function unfollow(userId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/users/${userId}/follow`)
}

export async function listIncomingRequests(): Promise<FollowRequestItem[]> {
  const { data } = await api.get('/follow-requests')
  return (data.data ?? data) as FollowRequestItem[]
}

export async function listOutgoingRequests(): Promise<FollowRequestItem[]> {
  const { data } = await api.get('/follow-requests/outgoing')
  return (data.data ?? data) as FollowRequestItem[]
}

export async function createFollowRequest(targetUserId: Id): Promise<{ id?: Id }> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/users/${targetUserId}/follow-requests`)
  return data?.data ?? {}
}

export async function acceptRequest(reqId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.post(`/follow-requests/${reqId}/accept`)
}

export async function rejectRequest(reqId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.post(`/follow-requests/${reqId}/reject`)
}

export async function cancelRequest(reqId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.post(`/follow-requests/${reqId}/cancel`)
}

export async function listMutuals(userId: Id, params: { page?: number; per_page?: number } = {}): Promise<Paginated<ProfileLite>> {
  const { data } = await api.get(`/users/${userId}/mutuals`, { params })
  return {
    data: (data.data ?? data) as ProfileLite[],
    meta: data.meta ?? { current_page: params.page ?? 1, per_page: params.per_page ?? 12, total: (data.data ?? []).length, last_page: 1 }
  }
}
