import { ref } from 'vue'
import type { Membership, MembershipStatus } from '../../../types'
import {
  listMemberships,
  createMembership,
  acceptMembership,
  rejectMembership,
  deleteMembership
} from '../../../api/members'

export function useMembers(ownerId: number) {
  const state = ref({
    loading: false,
    error: null as string | null,
    items: [] as Membership[],
    status: null as MembershipStatus | null
  })

  async function refresh() {
    state.value.loading = true
    state.value.error = null
    try {
      const res = await listMemberships(ownerId, { status: state.value.status ?? undefined })
      state.value.items = res
    } catch (e: any) {
      state.value.error = e.message || 'Failed to load members'
    } finally {
      state.value.loading = false
    }
  }

  async function add(payload: Partial<Membership>) {
    const item = await createMembership(ownerId, payload)
    state.value.items.push(item)
    return item
  }

  async function accept(id: number) {
    const item = await acceptMembership(id)
    const idx = state.value.items.findIndex(x => x.id === id)
    if (idx >= 0) state.value.items[idx] = item
    return item
  }

  async function reject(id: number, reason?: string) {
    const item = await rejectMembership(id, reason)
    const idx = state.value.items.findIndex(x => x.id === id)
    if (idx >= 0) state.value.items[idx] = item
    return item
  }

  async function remove(id: number) {
    await deleteMembership(id)
    state.value.items = state.value.items.filter(x => x.id !== id)
  }

  return { state, refresh, add, accept, reject, remove }
}
