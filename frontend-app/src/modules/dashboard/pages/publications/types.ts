export type Publication = {
  id: number;
  owner_id: number;
  owner?: {
    id: number;
    name: string;
    avatar_url?: string | null;
  } | null;

  title: string;
  issue: string;
  description?: string | null;

  slug: string;
  is_published: boolean;
  publish_at?: string | null;
  language?: string | null;

  cover_url?: string | null;
  cover_id?: number | null;

  documents?: Array<{
    id: number;
    url: string | null;
    order?: number;
  }> | null;

  document_ids?: number[];

  created_at?: string;
  updated_at?: string;
};

export type Paginated<T> = {
  data: T[];
  meta: {
    current_page: number;
    per_page: number;
    total: number;
    last_page: number;
  };
};
