export type AnnouncementVisibility = "public" | "members" | "supporters" | "private";

export interface MediaRef {
  id: number;
  url: string | null;
  order: number;
}

export interface Announcement {
  id: number;
  owner_id: number;
  title: string;
  body: string | null;
  slug: string | null;
  is_pinned: boolean;
  visibility: AnnouncementVisibility;
  publish_at: string | null;
  cover_url?: string | null;
  covers?: MediaRef[];
  documents?: MediaRef[];
  created_at?: string;
  updated_at?: string;
}

export interface Paginated<T> {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
}

export const QK_ANNOUNCEMENTS = "announcements";
export const QK_ANNOUNCEMENT = (id: number) => ["announcement", id] as const;
