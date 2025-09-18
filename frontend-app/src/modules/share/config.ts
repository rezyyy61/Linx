import type { ShareChannel } from './types';

export const DEFAULT_UTM_MEDIUM = 'share';

export const ENABLED_CHANNELS: ShareChannel[] = [
  'webshare',
  'whatsapp',
  'telegram',
  'facebook',
  'internal',
  'email',
  'embed',
];

export const WEB_SHARE_SUPPORTED =
  typeof navigator !== 'undefined' && typeof (navigator as any).share === 'function';
