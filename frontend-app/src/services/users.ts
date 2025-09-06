import { api } from "@/lib/http"

export function searchUsers(q: string, page = 1) {
  return api.get("/users/search", { params: { q, page } })
}
