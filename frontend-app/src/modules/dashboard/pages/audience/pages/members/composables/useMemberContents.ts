import { ref } from 'vue'
import type { MemberContent, ContentType } from '../../../types'
import {
  listContents,
  createContent,
  updateContent,
  deleteContent,
  scheduleContent
} from '../../../api/members'

export function useMemberContents(ownerId: number) {
  const state = ref({
    loading: false,
    error: null as string | null,
    items: [] as MemberContent[],
    type: null as ContentType | null
  })

  async function refresh() {
    state.value.loading = true
    state.value.error = null
    try {
      const res = await listContents(ownerId, { type: state.value.type ?? undefined })
      state.value.items = res
    } catch (e: any) {
      state.value.error = e.message || 'Failed to load contents'
    } finally {
      state.value.loading = false
    }
  }

  async function add(payload: Partial<MemberContent>) {
    const item = await createContent(ownerId, payload)
    state.value.items.push(item)
    return item
  }

  async function update(id: number, payload: Partial<MemberContent>) {
    const item = await updateContent(id, payload)
    const idx = state.value.items.findIndex(x => x.id === id)
    if (idx >= 0) state.value.items[idx] = item
    return item
  }

  async function remove(id: number) {
    await deleteContent(id)
    state.value.items = state.value.items.filter(x => x.id !== id)
  }

  async function schedule(id: number, when: string) {
    const item = await scheduleContent(id, when)
    const idx = state.value.items.findIndex(x => x.id === id)
    if (idx >= 0) state.value.items[idx] = item
    return item
  }

  return { state, refresh, add, update, remove, schedule }
}
