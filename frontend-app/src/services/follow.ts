import { api, ensureCsrfCookie } from "@/lib/http"

export async function sendFollowRequest(userId: number | string) {
  await ensureCsrfCookie()
  return api.post(`/follow/${userId}`)
}

export function getIncomingFollowRequests() {
  return api.get(`/follow/requests`)
}

export function getOutgoingFollowRequests() {
  return api.get(`/follow/requests/outgoing`)
}

export async function acceptFollowRequest(requestId: number | string) {
  await ensureCsrfCookie()
  return api.post(`/follow/requests/${requestId}/accept`)
}

export async function rejectFollowRequest(requestId: number | string) {
  await ensureCsrfCookie()
  return api.post(`/follow/requests/${requestId}/reject`)
}

export async function unfollowUser(userId: number | string) {
  await ensureCsrfCookie()
  return api.delete(`/follow/${userId}`, {
    params: { confirm: 1 }
  })
}

export function getFollowers(userId: number | string, page = 1) {
  return api.get(`/users/${userId}/followers`, { params: { page, include: "counts" } })
}

export function getFollowings(userId: number | string, page = 1) {
  return api.get(`/users/${userId}/followings`, { params: { page, include: "counts" } })
}

export function getSuggestions(userId: number | string, limit = 10) {
  return api.get(`/users/${userId}/suggestions`, { params: { limit, include: "counts" } })
}
