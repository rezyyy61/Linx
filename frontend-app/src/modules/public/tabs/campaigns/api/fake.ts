import type { CampaignPublic, CampaignQuery, Paged } from "./types"

let seed: CampaignPublic[] = [
  {
    id: 1,
    slug: "green-city-park",
    title: "Build The Green City Park",
    excerpt: "Help us fund a new public park and green playground.",
    description: "<p>We plan to convert an unused lot into a vibrant community park.</p>",
    kind: "fundraising",
    status: "published",
    visibility: "public",
    starts_at: "2025-09-01T08:00:00Z",
    ends_at: "2025-12-15T20:00:00Z",
    publish_at: "2025-09-01T08:00:00Z",
    cover_url: "https://picsum.photos/seed/campaign1/1200/600",
    goal_amount: 50000,
    goal_currency: "EUR",
    raised_amount: 18250,
    owner: { id: 11, name: "Civic Alliance", slug: "civic-alliance", avatar: "https://i.pravatar.cc/64?img=11" },
    created_at: "2025-08-25T10:00:00Z",
    updated_at: "2025-09-20T10:00:00Z"
  },
  {
    id: 2,
    slug: "clean-rivers-petition",
    title: "Sign For Clean Rivers",
    excerpt: "We demand stricter regulations to protect our rivers.",
    description: "<p>Join thousands calling for clean waterways.</p>",
    kind: "petition",
    status: "published",
    visibility: "public",
    starts_at: "2025-09-05T08:00:00Z",
    ends_at: "2026-01-10T20:00:00Z",
    publish_at: "2025-09-05T08:00:00Z",
    cover_url: "https://picsum.photos/seed/campaign2/1200/600",
    signature_goal: 100000,
    signatures_count: 42750,
    owner: { id: 12, name: "River Watch", slug: "river-watch", avatar: "https://i.pravatar.cc/64?img=12" },
    created_at: "2025-09-04T09:00:00Z",
    updated_at: "2025-09-22T09:00:00Z"
  },
  {
    id: 3,
    slug: "election-volunteers",
    title: "Volunteer For Election Day",
    excerpt: "Become a booth helper or driver for seniors.",
    description: "<p>We need 300 volunteers to support turnout.</p>",
    kind: "volunteer",
    status: "published",
    visibility: "public",
    starts_at: "2025-10-01T08:00:00Z",
    ends_at: "2025-11-20T20:00:00Z",
    publish_at: "2025-09-15T08:00:00Z",
    cover_url: "https://picsum.photos/seed/campaign3/1200/600",
    needed_slots: 300,
    filled_slots: 124,
    owner: { id: 13, name: "People First", slug: "people-first", avatar: "https://i.pravatar.cc/64?img=13" },
    created_at: "2025-09-12T12:00:00Z",
    updated_at: "2025-09-23T12:00:00Z"
  },
  {
    id: 4,
    slug: "voter-awareness-drive",
    title: "Voter Awareness Drive",
    excerpt: "Reach every neighborhood with voter info packs.",
    description: "<p>We will distribute leaflets and run online ads.</p>",
    kind: "awareness",
    status: "published",
    visibility: "public",
    starts_at: "2025-09-20T08:00:00Z",
    ends_at: "2025-11-01T20:00:00Z",
    publish_at: "2025-09-18T08:00:00Z",
    cover_url: "https://picsum.photos/seed/campaign4/1200/600",
    target_reach: 200000,
    current_reach: 68500,
    owner: { id: 14, name: "Open Vote", slug: "open-vote", avatar: "https://i.pravatar.cc/64?img=14" },
    created_at: "2025-09-18T10:00:00Z",
    updated_at: "2025-09-24T09:30:00Z"
  }
]

function progressOf(c: CampaignPublic): number {
  if (c.kind === "fundraising") {
    const g = c.goal_amount || 0
    const r = c.raised_amount || 0
    if (g <= 0) return 0
    return Math.min(100, Math.round((r / g) * 100))
  }
  if (c.kind === "petition") {
    const g = c.signature_goal || 0
    const r = c.signatures_count || 0
    if (g <= 0) return 0
    return Math.min(100, Math.round((r / g) * 100))
  }
  if (c.kind === "volunteer") {
    const g = c.needed_slots || 0
    const r = c.filled_slots || 0
    if (g <= 0) return 0
    return Math.min(100, Math.round((r / g) * 100))
  }
  if (c.kind === "awareness") {
    const g = c.target_reach || 0
    const r = c.current_reach || 0
    if (g <= 0) return 0
    return Math.min(100, Math.round((r / g) * 100))
  }
  return 0
}

function matches(c: CampaignPublic, q: CampaignQuery): boolean {
  if (q.q) {
    const s = q.q.toLowerCase()
    if (!(`${c.title} ${c.excerpt || ""} ${c.description || ""}`.toLowerCase().includes(s))) return false
  }
  if (q.kind && c.kind !== q.kind) return false
  if (q.status && c.status !== q.status) return false
  if (q.visibility && c.visibility !== q.visibility) return false
  return true
}

export async function listCampaigns(q: CampaignQuery = {}): Promise<Paged<CampaignPublic>> {
  const page = q.page && q.page > 0 ? q.page : 1
  const per = q.per_page && q.per_page > 0 ? Math.min(q.per_page, 50) : 12
  let rows = seed.filter(c => matches(c, q))
  if (q.order_by === "progress") {
    rows = rows.sort((a, b) => {
      const pa = progressOf(a)
      const pb = progressOf(b)
      return q.order_dir === "asc" ? pa - pb : pb - pa
    })
  } else if (q.order_by === "ends_at") {
    rows = rows.sort((a, b) => {
      const da = a.ends_at ? new Date(a.ends_at).getTime() : 0
      const db = b.ends_at ? new Date(b.ends_at).getTime() : 0
      return q.order_dir === "asc" ? da - db : db - da
    })
  } else if (q.order_by === "created_at") {
    rows = rows.sort((a, b) => {
      const da = a.created_at ? new Date(a.created_at).getTime() : 0
      const db = b.created_at ? new Date(b.created_at).getTime() : 0
      return q.order_dir === "asc" ? da - db : db - da
    })
  } else {
    rows = rows.sort((a, b) => {
      const da = a.publish_at ? new Date(a.publish_at).getTime() : 0
      const db = b.publish_at ? new Date(b.publish_at).getTime() : 0
      return q.order_dir === "asc" ? da - db : db - da
    })
  }
  const total = rows.length
  const start = (page - 1) * per
  const data = rows.slice(start, start + per)
  return {
    data,
    meta: { page, per_page: per, total, last_page: Math.max(1, Math.ceil(total / per)) }
  }
}

export async function getCampaignBySlug(slug: string): Promise<CampaignPublic | null> {
  const row = seed.find(x => x.slug === slug) || null
  return row
}
