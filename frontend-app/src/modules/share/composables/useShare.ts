import { ref } from 'vue';
import type { ShareChannel, CreateSharePayload, CreateShareResponse } from '../types';
import { DEFAULT_UTM_MEDIUM, WEB_SHARE_SUPPORTED } from '../config';
import { createShare } from '../services/shareApi';
import { buildPlatformUrl } from '../utils/platforms';

export interface UseShareOptions {
  shareableType?: string;
  shareableAlias?: string;
  shareableId: number;
  defaultUtm?: {
    source?: string | null;
    medium?: string | null;
    campaign?: string | null;
  };
  defaultTitle?: string;
  defaultText?: string;
}

export function useShare(options: UseShareOptions) {
  const loading = ref(false);
  const error = ref<unknown | null>(null);
  const lastShare = ref<CreateShareResponse | null>(null);

  async function shareTo(channel: ShareChannel, overrides?: Partial<Omit<CreateSharePayload, 'shareable_type' | 'shareable_alias' | 'shareable_id' | 'channel'>>) {
    loading.value = true;
    error.value = null;
    try {
      const payload: CreateSharePayload = {
        shareable_type: options.shareableType,
        shareable_alias: options.shareableAlias,
        shareable_id: options.shareableId,
        channel,
        utm_source: overrides?.utm_source ?? options.defaultUtm?.source ?? null,
        utm_medium: overrides?.utm_medium ?? options.defaultUtm?.medium ?? DEFAULT_UTM_MEDIUM,
        utm_campaign: overrides?.utm_campaign ?? options.defaultUtm?.campaign ?? null,
        expires_at: overrides?.expires_at ?? null,
        is_active: overrides?.is_active ?? true,
      };
      const res = await createShare(payload);
      lastShare.value = res;
      return res;
    } catch (e) {
      error.value = e;
      throw e;
    } finally {
      loading.value = false;
    }
  }

  async function shareViaWebApi(title?: string, text?: string) {
    if (!WEB_SHARE_SUPPORTED) return false;
    const res = await shareTo('webshare');
    const url = res.short_url;
    const payload: any = {};
    if (title || options.defaultTitle) payload.title = title || options.defaultTitle;
    if (text || options.defaultText) payload.text = text || options.defaultText;
    payload.url = url;
    try {
      await (navigator as any).share(payload);
      return true;
    } catch {
      return false;
    }
  }

  function buildExternalUrl(channel: ShareChannel, title?: string, text?: string) {
    const url = lastShare.value?.short_url || '';
    return buildPlatformUrl(channel, { url, title: title || options.defaultTitle, text: text || options.defaultText });
  }

  return {
    loading,
    error,
    lastShare,
    shareTo,
    shareViaWebApi,
    buildExternalUrl,
  };
}
