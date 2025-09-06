import { api } from "@/lib/http"

export type UserLite = {
  id: number
  name: string
  slug: string
  avatar?: string | null
  avatar_color?: string | null
}

export async function getMyProfileLite(): Promise<UserLite | null> {
  const { data } = await api.get("/profile/me-lite")
  const payload = data?.data ?? data
  return payload ?? null
}
