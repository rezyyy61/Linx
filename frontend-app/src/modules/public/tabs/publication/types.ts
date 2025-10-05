export type PublicOwner = {
  id: number;
  name: string;
  slug?: string;
  avatar_url?: string | null;
  verified?: boolean;
};

export type Publication = {
  id: number;
  title: string;
  slug: string;
  issue: string;
  description?: string | null;
  is_published: boolean;
  publish_at?: string | null;
  language: string;
  cover_url?: string | null;
  owner?: PublicOwner | null;
  documents_count?: number;
};

export type Paginated<T> = {
  data: T[];
  meta: { current_page: number; per_page: number; total: number; last_page: number };
};

export type PublicationOrderBy = "publish_at" | "created_at" | "updated_at";
export type DateRange = "all" | "today" | "this_week" | "this_month";
