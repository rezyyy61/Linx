import { api, ensureCsrfCookie } from '@/lib/http'
import type {
  Id,
  Paginated,
  Membership,
  MembershipStatus,
  MemberContent,
  ContentType,
  ContentTarget,
} from '../types'

export async function listMemberships(ownerId: Id, params: { status?: MembershipStatus } = {}): Promise<Membership[]> {
  const { data } = await api.get(`/users/${ownerId}/members`, { params })
  return (data.data ?? data) as Membership[]
}

export async function listMyMemberships(userId: Id, params: { status?: MembershipStatus } = {}): Promise<Membership[]> {
  const { data } = await api.get(`/users/${userId}/my-memberships`, { params })
  return (data.data ?? data) as Membership[]
}

export async function updateMyMembership(membershipId: Id, payload: Partial<Membership>): Promise<Membership> {
  await ensureCsrfCookie()
  const { data } = await api.patch(`/my-memberships/${membershipId}`, payload)
  return (data.data ?? data) as Membership
}

export async function leaveMyMembership(membershipId: Id, reason?: string): Promise<Membership> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/my-memberships/${membershipId}/leave`, { reason })
  return (data.data ?? data) as Membership
}

export async function createMembership(ownerId: Id, payload: Partial<Membership>): Promise<Membership> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/users/${ownerId}/members`, payload)
  return (data.data ?? data) as Membership
}

export async function acceptMembership(membershipId: Id): Promise<Membership> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/memberships/${membershipId}/accept`)
  return (data.data ?? data) as Membership
}

export async function rejectMembership(membershipId: Id, reason?: string): Promise<Membership> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/memberships/${membershipId}/reject`, { reason })
  return (data.data ?? data) as Membership
}

export async function deleteMembership(membershipId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/memberships/${membershipId}`)
}

export async function listContents(ownerId: Id, params: { type?: ContentType } = {}): Promise<MemberContent[]> {
  const { data } = await api.get(`/users/${ownerId}/member-contents`, { params })
  return (data.data ?? data) as MemberContent[]
}

export async function createContent(ownerId: Id, payload: Partial<MemberContent>): Promise<MemberContent> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/users/${ownerId}/member-contents`, payload)
  return (data.data ?? data) as MemberContent
}

export async function updateContent(contentId: Id, payload: Partial<MemberContent>): Promise<MemberContent> {
  await ensureCsrfCookie()
  const { data } = await api.put(`/member-contents/${contentId}`, payload)
  return (data.data ?? data) as MemberContent
}

export async function deleteContent(contentId: Id): Promise<void> {
  await ensureCsrfCookie()
  await api.delete(`/member-contents/${contentId}`)
}

export async function scheduleContent(contentId: Id, scheduled_at: string): Promise<MemberContent> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/member-contents/${contentId}/schedule`, { scheduled_at })
  return (data.data ?? data) as MemberContent
}

export async function listTargets(contentId: Id, params: { page?: number; per_page?: number } = {}): Promise<Paginated<ContentTarget>> {
  const { data } = await api.get(`/member-contents/${contentId}/targets`, { params })
  return {
    data: (data.data ?? data) as ContentTarget[],
    meta: data.meta ?? { current_page: params.page ?? 1, per_page: params.per_page ?? 20, total: (data.data ?? []).length, last_page: 1 }
  }
}

export async function markTargetSent(targetId: Id): Promise<ContentTarget> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/member-content-targets/${targetId}/sent`)
  return (data.data ?? data) as ContentTarget
}

export async function markTargetFailed(targetId: Id): Promise<ContentTarget> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/member-content-targets/${targetId}/failed`)
  return (data.data ?? data) as ContentTarget
}

export async function markTargetOpened(targetId: Id): Promise<ContentTarget> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/member-content-targets/${targetId}/opened`)
  return (data.data ?? data) as ContentTarget
}

export async function markTargetClicked(targetId: Id): Promise<ContentTarget> {
  await ensureCsrfCookie()
  const { data } = await api.post(`/member-content-targets/${targetId}/clicked`)
  return (data.data ?? data) as ContentTarget
}
