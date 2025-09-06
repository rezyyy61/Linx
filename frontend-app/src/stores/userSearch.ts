import { defineStore } from "pinia"
import { searchUsers } from "@/services/users"
import { useUserEntities } from "@/stores/entities/users"
import type { UserLite } from "@/services/profile"

export const useUserSearchStore = defineStore("userSearch", {
  state: () => ({
    query: "" as string,
    resultIds: [] as number[],
    loading: false,
    page: 1,
    hasMore: false,
  }),

  getters: {
    results(): UserLite[] {
      const entities = useUserEntities()
      return this.resultIds.map(id => entities.byId[id]).filter(Boolean) as UserLite[]
    },
  },

  actions: {
    async search(q: string) {
      this.query = q
      if (q.trim().length < 2) {
        this.resultIds = []
        this.page = 1
        this.hasMore = false
        return
      }
      this.loading = true
      try {
        const { data } = await searchUsers(q, 1)
        const items: UserLite[] = data?.data ?? data ?? []
        const entities = useUserEntities()
        entities.upsertMany(items)
        this.resultIds = items.map(u => u.id)
        this.page = 1
        this.hasMore = Array.isArray(items) && items.length >= 15
      } finally {
        this.loading = false
      }
    },

    async loadMore() {
      if (!this.hasMore || this.loading || this.query.trim().length < 2) return
      this.loading = true
      try {
        const nextPage = this.page + 1
        const { data } = await searchUsers(this.query, nextPage)
        const items: UserLite[] = data?.data ?? data ?? []
        const entities = useUserEntities()
        entities.upsertMany(items)
        this.resultIds = [...this.resultIds, ...items.map(u => u.id)]
        this.page = nextPage
        this.hasMore = Array.isArray(items) && items.length >= 15
      } finally {
        this.loading = false
      }
    },

    clear() {
      this.query = ""
      this.resultIds = []
      this.page = 1
      this.hasMore = false
      this.loading = false
    }
  }
})
