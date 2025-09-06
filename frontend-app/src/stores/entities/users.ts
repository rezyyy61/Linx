import { defineStore } from "pinia"
import type { UserLite } from "@/services/profile"
import { getMyProfileLite } from "@/services/profile"

export const useUserEntities = defineStore("userEntities", {
  state: () => ({
    byId: {} as Record<number, UserLite>,
    bySlug: {} as Record<string, number>,
    meId: null as number | null,
  }),
  getters: {
    get: (s) => (id: number) => s.byId[id],
    getBySlug: (s) => (slug: string) => s.byId[s.bySlug[slug]],
    me: (s) => (s.meId ? s.byId[s.meId] : undefined),
  },
  actions: {
    upsertMany(list: UserLite[]) {
      list.forEach(u => {
        if (!u || typeof u.id !== "number") return
        this.byId[u.id] = { ...this.byId[u.id], ...u }
        if (u.slug) this.bySlug[u.slug] = u.id
      })
    },
    upsertOne(u: UserLite) {
      this.upsertMany([u]); return u
    },
    async fetchMe() {
      const me = await getMyProfileLite()
      if (me) {
        this.upsertOne(me)
        this.meId = me.id
      } else {
        this.meId = null
      }
      return me
    },
  }
})
