export const QK_CAMPAIGNS = "campaigns";
export const QK_CAMPAIGN = (id: number) => ["campaign", id] as const;
export const QK_CAMPAIGN_STATS = (id: number) => ["campaign-stats", id] as const;
export const QK_CONTENT = (campaignId: number, contentId: number) =>
  ["campaign-content", campaignId, contentId] as const;
