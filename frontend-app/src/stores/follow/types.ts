export type ID = number

export type FollowRequestStatus = "pending" | "accepted" | "rejected"

export type FollowRequestItem = {
  id: number
  actor_id: number
  target_id: number
  status: FollowRequestStatus
  created_at: string
  actor?: import("@/services/profile").UserLite
  target?: import("@/services/profile").UserLite
}

export type FollowState = {
  meId: number | null
  followerIds: number[]
  followingIds: number[]
  suggestionIds: number[]

  requestIds: number[]
  requestsById: Record<number, FollowRequestItem>

  outgoingRequestIds: number[]
  outgoingByTargetId: Record<number, number> // targetId -> requestId

  loading: boolean
}
