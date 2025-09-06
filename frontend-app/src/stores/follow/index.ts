import { defineStore } from "pinia"
import { ref, computed } from "vue"
import type { FollowRequestItem, FollowState } from "./types"
import * as api from "./api"
import { useUserEntities } from "@/stores/entities/users"
import type { UserLite } from "@/services/profile"
import { subscribePrivate, unsubscribe } from "@/lib/echo"

function uniqPush(arr: number[], id: number) {
  if (!arr.includes(id)) arr.push(id)
}
function removeId(arr: number[], id: number) {
  const i = arr.indexOf(id); if (i >= 0) arr.splice(i, 1)
}

export const useFollowStore = defineStore("follow", () => {
  const state = ref<FollowState>({
    meId: null,

    followerIds: [],
    followingIds: [],
    suggestionIds: [],

    requestIds: [],
    requestsById: {},

    outgoingRequestIds: [],
    outgoingByTargetId: {},

    loading: false,
  })

  const entities = useUserEntities()

  const followers = computed<UserLite[]>(() =>
    state.value.followerIds.map(id => entities.byId[id]).filter(Boolean) as UserLite[]
  )
  const followings = computed<UserLite[]>(() =>
    state.value.followingIds.map(id => entities.byId[id]).filter(Boolean) as UserLite[]
  )
  const friends = computed<UserLite[]>(() => {
    const s = new Set(state.value.followingIds)
    const both = state.value.followerIds.filter(id => s.has(id))
    return both.map(id => entities.byId[id]).filter(Boolean) as UserLite[]
  })
  const suggestions = computed<UserLite[]>(() =>
    state.value.suggestionIds.map(id => entities.byId[id]).filter(Boolean) as UserLite[]
  )
  const incomingRequests = computed<FollowRequestItem[]>(() =>
    state.value.requestIds.map(id => state.value.requestsById[id]).filter(Boolean)
  )
  const loading = computed(() => state.value.loading)

  const isPendingWith = (userId: number | string) => {
    const id = Number(userId)
    if (state.value.outgoingByTargetId[id]) return true
    return incomingRequests.value.some(r => r.actor_id === id && r.status === "pending")
  }
  const isConnected = (userId: number | string) => {
    const id = Number(userId)
    const a = new Set(state.value.followerIds)
    const b = new Set(state.value.followingIds)
    return a.has(id) && b.has(id)
  }

  async function ensureMe(): Promise<number | null> {
    if (state.value.meId) return state.value.meId
    const me = await api.getMyProfileLite().catch(() => null)
    if (me) {
      entities.upsertMany([me])
      state.value.meId = me.id
    } else {
      state.value.meId = null
    }
    return state.value.meId
  }

  async function loadFollowers(userId?: number | string) {
    const id = userId ?? (await ensureMe())
    if (!id) return
    state.value.loading = true
    try {
      const { data } = await api.getFollowers(id)
      const payload = data?.data ?? data
      const items: UserLite[] = Array.isArray(payload) ? payload : payload?.data ?? []
      entities.upsertMany(items)
      state.value.followerIds = items.map(u => u.id)
    } finally { state.value.loading = false }
  }

  async function loadFollowings(userId?: number | string) {
    const id = userId ?? (await ensureMe())
    if (!id) return
    state.value.loading = true
    try {
      const { data } = await api.getFollowings(id)
      const payload = data?.data ?? data
      const items: UserLite[] = Array.isArray(payload) ? payload : payload?.data ?? []
      entities.upsertMany(items)
      state.value.followingIds = items.map(u => u.id)
    } finally { state.value.loading = false }
  }

  async function loadSuggestions(userId?: number | string) {
    const id = userId ?? (await ensureMe())
    if (!id) return
    state.value.loading = true
    try {
      const { data } = await api.getSuggestions(id)
      const items: UserLite[] = data?.data ?? data ?? []
      entities.upsertMany(items)
      state.value.suggestionIds = items.map(u => u.id)
    } finally { state.value.loading = false }
  }

  async function loadIncomingRequests() {
    state.value.loading = true
    try {
      const { data } = await api.getIncomingFollowRequests()
      const items: FollowRequestItem[] = data?.data ?? data ?? []
      entities.upsertMany(items.map(i => i.actor!).filter(Boolean) as UserLite[])
      state.value.requestsById = {}
      state.value.requestIds = []
      for (const r of items) {
        state.value.requestsById[r.id] = r
        state.value.requestIds.push(r.id)
      }
    } finally { state.value.loading = false }
  }

  async function loadOutgoingRequests() {
    state.value.loading = true
    try {
      const { data } = await api.getOutgoingFollowRequests()
      const items: FollowRequestItem[] = data?.data ?? data ?? []
      entities.upsertMany(items.map(i => i.target!).filter(Boolean) as UserLite[])
      state.value.outgoingRequestIds = items.map(i => i.id)
      state.value.outgoingByTargetId = {}
      for (const r of items) state.value.outgoingByTargetId[r.target_id] = r.id
    } finally { state.value.loading = false }
  }

  async function sendFollow(userId: number | string) {
    await api.sendFollowRequest(userId)
    await Promise.all([loadOutgoingRequests(), loadSuggestions()])
  }

  async function acceptRequest(requestId: number | string) {
    await api.acceptFollowRequest(requestId)
    await Promise.all([
      loadFollowers(),
      loadFollowings(),
      loadIncomingRequests(),
      loadOutgoingRequests(),
      loadSuggestions(),
    ])
  }

  async function rejectRequest(requestId: number | string) {
    await api.rejectFollowRequest(requestId)
    await Promise.all([loadIncomingRequests(), loadSuggestions()])
  }

  async function unfollow(userId: number | string) {
    await api.unfollowUser(userId)
    await Promise.all([loadFollowings(), loadFollowers(), loadSuggestions()])
  }

  function reset() {
    state.value = {
      meId: null,
      followerIds: [],
      followingIds: [],
      suggestionIds: [],
      requestIds: [],
      requestsById: {},
      outgoingRequestIds: [],
      outgoingByTargetId: {},
      loading: false,
    }
  }

  function applyRequestCreated(r: FollowRequestItem) {
    if (!state.value.meId) return
    const me = state.value.meId
    if (r.actor?.id) entities.upsertMany([r.actor])
    if (r.target?.id) entities.upsertMany([r.target])

    if (r.target_id === me) {
      state.value.requestsById[r.id] = r
      uniqPush(state.value.requestIds, r.id)
      removeId(state.value.suggestionIds, r.actor_id)
    } else if (r.actor_id === me) {
      state.value.outgoingByTargetId[r.target_id] = r.id
      uniqPush(state.value.outgoingRequestIds, r.id)
      removeId(state.value.suggestionIds, r.target_id)
    }
  }

  function applyRequestResolved(r: { id?: number; actor_id: number; target_id: number; status: "accepted"|"rejected" }) {
    if (!state.value.meId) return
    const me = state.value.meId
    const other = r.actor_id === me ? r.target_id : r.actor_id

    if (r.status === "accepted") {
      uniqPush(state.value.followerIds, other)
      uniqPush(state.value.followingIds, other)
      if (r.id) {
        delete state.value.requestsById[r.id]
        removeId(state.value.requestIds, r.id)
        removeId(state.value.outgoingRequestIds, r.id)
      }
      delete state.value.outgoingByTargetId[other]
      removeId(state.value.suggestionIds, other)
    } else {
      if (r.id) {
        delete state.value.requestsById[r.id]
        removeId(state.value.requestIds, r.id)
        removeId(state.value.outgoingRequestIds, r.id)
      }
      delete state.value.outgoingByTargetId[other]
    }
  }

  function applyRequestCancelled(r: { id?: number; actor_id: number; target_id: number }) {
    if (!state.value.meId) return
    const me = state.value.meId
    const other = r.actor_id === me ? r.target_id : r.actor_id
    if (r.id) {
      delete state.value.requestsById[r.id]
      removeId(state.value.requestIds, r.id)
      removeId(state.value.outgoingRequestIds, r.id)
    }
    delete state.value.outgoingByTargetId[other]
  }

  function applyFriendAdded(payload: { actor_id?: number; target_id?: number; other_id?: number }) {
    if (!state.value.meId) return
    const me = state.value.meId
    const other = payload.other_id ?? (payload.actor_id === me ? payload.target_id! : payload.actor_id!)
    if (!other) return
    uniqPush(state.value.followerIds, other)
    uniqPush(state.value.followingIds, other)
    delete state.value.outgoingByTargetId[other]
    removeId(state.value.suggestionIds, other)
  }

  function applyFriendRemoved(payload: { actor_id?: number; target_id?: number; other_id?: number }) {
    if (!state.value.meId) return
    const me = state.value.meId
    const other = payload.other_id ?? (payload.actor_id === me ? payload.target_id! : payload.actor_id!)
    if (!other) return
    removeId(state.value.followerIds, other)
    removeId(state.value.followingIds, other)
  }

  let boundChannelName: string | null = null

  function bindMany(channel: any, names: string[], handler: (e:any)=>void) {
    names.forEach(n => channel.bind(n, handler))
  }

  async function bindRealtime() {
    const me = await ensureMe()
    if (!me) return
    const chName = `user.${me}`
    const channel = await subscribePrivate(chName)

    // CREATED
    bindMany(channel, [
      'follow.request.created',
      'FollowRequestCreated',
      'App\\Events\\Follow\\FollowRequestCreated'
    ], (e:any) => {
      const r: FollowRequestItem = e?.request ?? e
      applyRequestCreated(r)
    })

    // ACCEPTED
    bindMany(channel, [
      'follow.request.accepted',
      'FollowRequestAccepted',
      'App\\Events\\Follow\\FollowRequestAccepted'
    ], (e:any) => {
      const r = e?.request ?? e
      applyRequestResolved({ id: r?.id, actor_id: +r.actor_id, target_id: +r.target_id, status: 'accepted' })
    })

    // REJECTED
    bindMany(channel, [
      'follow.request.rejected',
      'FollowRequestRejected',
      'App\\Events\\Follow\\FollowRequestRejected'
    ], (e:any) => {
      const r = e?.request ?? e
      applyRequestResolved({ id: r?.id, actor_id: +r.actor_id, target_id: +r.target_id, status: 'rejected' })
    })

    // CANCELLED
    bindMany(channel, [
      'follow.request.cancelled',
      'FollowRequestCancelled',
      'App\\Events\\Follow\\FollowRequestCancelled'
    ], (e:any) => {
      const r = e?.request ?? e
      applyRequestCancelled({ id: r?.id, actor_id: +r.actor_id, target_id: +r.target_id })
    })

    // FRIEND ADDED/REMOVED (اگر تو backend گذاشتی)
    bindMany(channel, [
      'friend.added',
      'FriendAdded',
      'App\\Events\\Follow\\FriendAdded'
    ], (e:any) => {
      applyFriendAdded({ other_id: +e?.other_id, actor_id: +e?.actor_id, target_id: +e?.target_id })
    })
    bindMany(channel, [
      'friend.removed',
      'FriendRemoved',
      'App\\Events\\Follow\\FriendRemoved'
    ], (e:any) => {
      applyFriendRemoved({ other_id: +e?.other_id, actor_id: +e?.actor_id, target_id: +e?.target_id })
    })

    channel.bind('pusher:subscription_succeeded', () => {
    })
    channel.bind('pusher:subscription_error', () => {
    })
  }

  async function unbindRealtime() {
    if (!boundChannelName) return
    await unsubscribe(boundChannelName)
    boundChannelName = null
  }

  return {
    loading,
    ensureMe,

    followers,
    followings,
    friends,
    suggestions,
    incomingRequests,

    isPendingWith,
    isConnected,

    loadFollowers,
    loadFollowings,
    loadSuggestions,
    loadIncomingRequests,
    loadOutgoingRequests,
    sendFollow,
    acceptRequest,
    rejectRequest,
    unfollow,
    reset,

    bindRealtime,
    unbindRealtime,
  }
})
