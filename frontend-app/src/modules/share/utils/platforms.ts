import type { ShareChannel } from '../types';

export interface PlatformParams {
  url: string;
  title?: string;
  text?: string;
}

export function buildPlatformUrl(channel: ShareChannel, params: PlatformParams): string {
  const u = params.url;
  const t = params.title || '';
  const x = params.text || '';
  if (channel === 'whatsapp') {
    return 'https://wa.me/?text=' + encodeURIComponent((t ? t + ' ' : '') + x + (x ? ' ' : '') + u);
  }
  if (channel === 'telegram') {
    const qs = new URLSearchParams({ url: u, text: t || x || '' }).toString();
    return 'https://t.me/share/url?' + qs;
  }
  if (channel === 'facebook') {
    const qs = new URLSearchParams({ u }).toString();
    return 'https://www.facebook.com/sharer/sharer.php?' + qs;
  }
  return u;
}
