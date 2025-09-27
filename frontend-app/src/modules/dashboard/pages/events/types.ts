export type EventId = number;

export type EventSettings = {
  type?: 'in_person' | 'online' | 'hybrid' | null;
  visibility?: 'public' | 'unlisted' | 'private' | null;
  join_url?: string | null;
  join_platform?: string | null;
  join_passcode?: string | null;
  join_instructions?: string | null;
  join_visible_minutes_before?: number | null;
  access_code?: string | null;
  og_title?: string | null;
  og_description?: string | null;
} | null;

export type EventDocument = { id: number; url: string | null };

export interface EventItem {
  id: EventId;
  title: string;
  description: string | null;
  slug: string;
  timezone: string;
  starts_at: string | null;        // ISO
  ends_at: string | null;          // ISO
  starts_at_local: string | null;  // YYYY-MM-DDTHH:mm
  ends_at_local: string | null;
  location: string | null;
  capacity: number | null;
  is_published: boolean;
  publish_at: string | null;
  cover_url: string | null;
  cover_id?: number | null;
  documents?: EventDocument[];
  document_ids?: number[];
  settings?: EventSettings;
  created_at?: string;
  updated_at?: string;
}

export type EventListFilters = {
  q?: string;
  is_published?: boolean;
  starts_from?: string;
  starts_to?: string;
  order_by?: 'starts_at' | 'created_at' | 'updated_at';
  order_dir?: 'asc' | 'desc';
  per_page?: number;
  page?: number;
};

export type CreateEventPayload = {
  title: string;
  description: string | null;
  starts_at: string | Date | null;
  ends_at: string | Date | null;
  timezone: string;
  location: string | null;
  capacity: number | null;
  is_published: boolean;
  organizer_id: number | null;
  settings?: EventSettings;
  cover_id?: number;                         // optional
  documents?: Array<{ id: number; order?: number }>; // optional; null=touch-nothing, []=clear
};

export type UpdateEventPayload = Partial<CreateEventPayload>;
