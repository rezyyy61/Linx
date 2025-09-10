import { ref, reactive } from 'vue'
import type { ProfileLite, Paginated } from '../../../types'
import { meLite, listFollowers, listFollowings, unfollow } from '../../../api/follow'

type OrderBy = 'pinned' | 'name'

const PINS_KEY = 'audience:pins'

export function useFriends() {
  const me = ref<ProfileLite | null>(null)
  const state = reactive({
    loading: false,
    limit: 50,
    search: '',
    orderBy: 'pinned' as OrderBy
  })

  const friends = ref<ProfileLite[]>([])
  const pins = ref<Record<number, boolean>>(
    JSON.parse(localStorage.getItem(PINS_KEY) || '{}')
  )

  function savePins() {
    localStorage.setItem(PINS_KEY, JSON.stringify(pins.value))
  }

  async function refresh() {
    state.loading = true
    try {
      if (!me.value) {
        const res = await meLite()
        me.value = res.data
      }
      const [folw, folg] = await Promise.all([
        listFollowers(me.value!.id, { page: 1, per_page: state.limit }) as Promise<Paginated<ProfileLite>>,
        listFollowings(me.value!.id, { page: 1, per_page: state.limit }) as Promise<Paginated<ProfileLite>>
      ])
      const mapFollowings = new Map<number, ProfileLite>()
      for (const p of folg.data) mapFollowings.set(p.id, p)
      const out: ProfileLite[] = []
      for (const p of folw.data) if (mapFollowings.has(p.id)) out.push(p)
      friends.value = out
    } finally {
      state.loading = false
    }
  }

  function togglePin(id: number) {
    pins.value[id] = !pins.value[id]
    savePins()
  }

  async function removeFriend(userId: number) {
    if (!confirm('Remove this friend?')) return
    await unfollow(userId)
    friends.value = friends.value.filter(f => f.id !== userId)
    if (pins.value[userId]) {
      delete pins.value[userId]
      savePins()
    }
  }

  return { me, state, friends, refresh, removeFriend, pins, togglePin }
}
