import type { BoostPayload, DonatePayload, SignPayload, VolunteerPayload, CampaignPublic } from "./types"

let memoryById = new Map<number, CampaignPublic>()

export function seedMemory(rows: CampaignPublic[]) {
  for (const r of rows) memoryById.set(r.id, { ...r })
}

export function getById(id: number): CampaignPublic | null {
  return memoryById.get(id) || null
}

export async function donateToCampaign(p: DonatePayload): Promise<CampaignPublic> {
  const row = memoryById.get(p.campaignId)
  if (!row || row.kind !== "fundraising") throw new Error("not_found_or_invalid")
  const amt = Number(p.amount || 0)
  if (!Number.isFinite(amt) || amt <= 0) throw new Error("invalid_amount")
  const raised = Number(row.raised_amount || 0) + amt
  const updated = { ...row, raised_amount: raised }
  memoryById.set(row.id, updated)
  await new Promise(r => setTimeout(r, 300))
  return updated
}

export async function signPetition(p: SignPayload): Promise<CampaignPublic> {
  const row = memoryById.get(p.campaignId)
  if (!row || row.kind !== "petition") throw new Error("not_found_or_invalid")
  const cnt = Number(row.signatures_count || 0) + 1
  const updated = { ...row, signatures_count: cnt }
  memoryById.set(row.id, updated)
  await new Promise(r => setTimeout(r, 250))
  return updated
}

export async function volunteerApply(p: VolunteerPayload): Promise<CampaignPublic> {
  const row = memoryById.get(p.campaignId)
  if (!row || row.kind !== "volunteer") throw new Error("not_found_or_invalid")
  const filled = Number(row.filled_slots || 0) + 1
  const updated = { ...row, filled_slots: filled }
  memoryById.set(row.id, updated)
  await new Promise(r => setTimeout(r, 250))
  return updated
}

export async function boostAwareness(p: BoostPayload): Promise<CampaignPublic> {
  const row = memoryById.get(p.campaignId)
  if (!row || row.kind !== "awareness") throw new Error("not_found_or_invalid")
  const inc = p.channel === "share" ? 50 : 10
  const cur = Number(row.current_reach || 0) + inc
  const updated = { ...row, current_reach: cur }
  memoryById.set(row.id, updated)
  await new Promise(r => setTimeout(r, 200))
  return updated
}
