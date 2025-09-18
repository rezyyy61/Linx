export type ShareChannel =
  | 'internal'
  | 'webshare'
  | 'whatsapp'
  | 'telegram'
  | 'facebook'
  | 'email'
  | 'embed'
  | 'repost';

export type ISODate = string;

export interface CreateSharePayload {
  shareable_type?: string;
  shareable_alias?: string;
  shareable_id: number;
  channel: ShareChannel;
  utm_source?: string | null;
  utm_medium?: string | null;
  utm_campaign?: string | null;
  expires_at?: ISODate | null;
  is_active?: boolean;
}

export interface CreateShareResponse {
  id: number;
  channel: ShareChannel;
  short_code: string;
  short_url: string;
  expires_at: string | null;
  is_active: boolean;
  utm_source: string | null;
  utm_medium: string | null;
  utm_campaign: string | null;
}
