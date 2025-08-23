import { defineStore } from "pinia";
import { api, ensureCsrfCookie } from "@/lib/http";

type ID = number;

type MediaType = "image" | "video" | "audio" | "document";
const MODEL_TYPE = "App\\Models\\Profile\\Profile";

export interface Media {
  id: ID;
  key: string;
  mime?: string | null;
  size?: number | null;
  width?: number | null;
  height?: number | null;
  type?: MediaType | null;
  ext?: string | null;
  url?: string | null;
  public_url?: string | null;
}

export interface ProfileTranslation {
  id?: ID;
  profile_id?: ID;
  locale: string;
  tagline?: string | null;
  about?: string | null;
  goals?: string | null;
  activities?: string | null;
  structure?: string | null;
}

export interface ProfileLink {
  id: ID;
  profile_id: ID;
  type: "website" | "email" | "phone" | "telegram" | "instagram" | "facebook" | "twitter" | "custom";
  title?: string | null;
  value?: string | null;
  url?: string | null;
  order: number;
}

export interface ProfileValue {
  id: ID;
  profile_id: ID;
  type: "predefined" | "custom";
  value: string;
  order: number;
}

export interface Profile {
  id: ID;
  user_id: ID;
  slug: string;
  entity_type: "individual" | "party" | "collective" | "media";
  location?: string | null;
  founded_year?: number | null;
  status: "draft" | "published" | "archived";
  verified: boolean;
  avatar_color?: string | null;
  published_at?: string | null;
  created_at: string;
  updated_at: string;
  translations: ProfileTranslation[];
  links: ProfileLink[];
  values: ProfileValue[];
  media: Media[];
  logo?: Media[];
  documents?: Media[];
  cover?: Media[];
  gallery?: Media[];
}

export interface User {
  id: ID;
  name: string;
  email: string;
  email_verified_at?: string | null;
  created_at?: string;
  updated_at?: string;
}

type ApiOk<T> = { ok: true; data: T };

function pickUrl(m?: Media | null): string {
  if (!m) return "";
  return m.url ?? m.public_url ?? "";
}

const PUBLIC_BASE = (import.meta as any).env?.VITE_S3_PUBLIC_BASE as string | undefined;
function fallbackUrl(key?: string | null): string {
  if (!key || !PUBLIC_BASE) return "";
  const base = PUBLIC_BASE.replace(/\/+$/, "");
  return `${base}/${key}`;
}

export const useProfileStore = defineStore("profile", {
  state: () => ({
    profile: null as Profile | null,
    user: null as User | null,
    loading: false,
    error: null as string | null,
  }),
  getters: {
    isLoaded: (s) => !!s.profile && !!s.user,
    links: (s) => s.profile?.links ?? [],
    values: (s) => s.profile?.values ?? [],
    files: (s) => s.profile?.documents ?? [],
    logo: (s) => (s.profile?.logo && s.profile.logo[0]) || null,
    logoUrl(): string {
      const m = this.logo as Media | null;
      return pickUrl(m) || fallbackUrl(m?.key);
    },
  },
  actions: {
    async fetchMe() {
      this.loading = true;
      this.error = null;
      try {
        const res = await api.get<ApiOk<{ profile: Profile; user: User }>>("/profile/me");
        this.profile = res.data.data.profile;
        this.user = res.data.data.user;
      } catch (e: any) {
        this.error = e?.response?.data?.error || e?.message || "Failed to load profile";
        throw e;
      } finally {
        this.loading = false;
      }
    },

    async updateProfile(payload: Partial<Pick<Profile, "slug" | "location" | "founded_year" | "avatar_color">> & { translation?: Partial<ProfileTranslation> }) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.put<ApiOk<{ profile: Profile }>>("/profile/me", payload);
      this.profile = res.data.data.profile;
      return this.profile;
    },

    async addLink(payload: Omit<ProfileLink, "id" | "profile_id" | "order"> & { order?: number }) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.post<ApiOk<{ link: ProfileLink }>>("/profile/me/links", payload);
      const link = res.data.data.link;
      if (this.profile) this.profile.links = [...this.links, link].sort((a, b) => a.order - b.order);
      return link;
    },

    async updateLink(id: ID, payload: Partial<Omit<ProfileLink, "id" | "profile_id">>) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.patch<ApiOk<{ link: ProfileLink }>>(`/profile/me/links/${id}`, payload);
      const link = res.data.data.link;
      if (this.profile) {
        this.profile.links = this.links.map((l) => (l.id === id ? link : l)).sort((a, b) => a.order - b.order);
      }
      return link;
    },

    async deleteLink(id: ID) {
      this.error = null;
      await ensureCsrfCookie();
      await api.delete<ApiOk<{}>>(`/profile/me/links/${id}`);
      if (this.profile) this.profile.links = this.links.filter((l) => l.id !== id);
    },

    async reorderLinks(ids: ID[]) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.put<ApiOk<{ links: ProfileLink[] }>>("/profile/me/links/reorder", { ids });
      if (this.profile) this.profile.links = res.data.data.links;
      return this.profile?.links ?? [];
    },

    async addValue(payload: Omit<ProfileValue, "id" | "profile_id" | "order"> & { order?: number }) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.post<ApiOk<{ value: ProfileValue }>>("/profile/me/values", payload);
      const value = res.data.data.value;
      if (this.profile) this.profile.values = [...this.values, value].sort((a, b) => a.order - b.order);
      return value;
    },

    async updateValue(id: ID, payload: Partial<Omit<ProfileValue, "id" | "profile_id">>) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.patch<ApiOk<{ value: ProfileValue }>>(`/profile/me/values/${id}`, payload);
      const value = res.data.data.value;
      if (this.profile) {
        this.profile.values = this.values.map((v) => (v.id === id ? value : v)).sort((a, b) => a.order - b.order);
      }
      return value;
    },

    async deleteValue(id: ID) {
      this.error = null;
      await ensureCsrfCookie();
      await api.delete<ApiOk<{}>>(`/profile/me/values/${id}`);
      if (this.profile) this.profile.values = this.values.filter((v) => v.id !== id);
    },

    async reorderValues(ids: ID[]) {
      this.error = null;
      await ensureCsrfCookie();
      const res = await api.put<ApiOk<{ values: ProfileValue[] }>>("/profile/me/values/reorder", { ids });
      if (this.profile) this.profile.values = res.data.data.values;
      return this.profile?.values ?? [];
    },

    async attachMediaToCollection(mediaId: ID, collection: "logo" | "documents" | "cover" | "gallery", order?: number) {
      this.error = null;
      await ensureCsrfCookie();
      if (!this.profile) await this.fetchMe();
      await api.post("/media/" + mediaId + "/attach", {
        model_type: MODEL_TYPE,
        model_id: this.profile!.id,
        collection,
        order: order ?? 0,
      });
      await this.fetchMe();
      return this.profile;
    },

    async detachMediaFromCollection(mediaId: ID, collection: "logo" | "documents" | "cover" | "gallery") {
      this.error = null;
      await ensureCsrfCookie();
      if (!this.profile) await this.fetchMe();
      await api.post("/media/" + mediaId + "/detach", {
        model_type: MODEL_TYPE,
        model_id: this.profile!.id,
        collection,
      });
      await this.fetchMe();
      return this.profile;
    },

    async setLogo(mediaId: ID) {
      this.error = null;
      await ensureCsrfCookie();
      if (!this.profile) await this.fetchMe();
      const profileId = this.profile!.id;

      await api.post("/media/" + mediaId + "/attach-single", {
        model_type: "App\\Models\\Profile\\Profile",
        model_id: profileId,
        collection: "logo",
        order: 0,
      });

      await this.fetchMe();
      return this.profile;
    },

    async clearLogo() {
      this.error = null;
      await ensureCsrfCookie();
      if (!this.profile) await this.fetchMe();
      const currentId = Array.isArray(this.profile?.logo) && this.profile!.logo.length > 0 ? (this.profile!.logo[0] as any).id : null;
      if (!currentId) return this.profile;
      await this.detachMediaFromCollection(currentId, "logo");
      return this.profile;
    },

    async addDocument(mediaId: ID, order?: number) {
      return this.attachMediaToCollection(mediaId, "documents", order);
    },

    async removeDocument(mediaId: ID) {
      return this.detachMediaFromCollection(mediaId, "documents");
    },

    async addCover(mediaId: ID, order?: number) {
      return this.attachMediaToCollection(mediaId, "cover", order);
    },

    async removeCover(mediaId: ID) {
      return this.detachMediaFromCollection(mediaId, "cover");
    },

    async addGallery(mediaId: ID, order?: number) {
      return this.attachMediaToCollection(mediaId, "gallery", order);
    },

    async removeGallery(mediaId: ID) {
      return this.detachMediaFromCollection(mediaId, "gallery");
    },
  },
});
