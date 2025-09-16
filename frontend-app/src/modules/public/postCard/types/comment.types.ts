export type PublicMiniUser = {
  id: number;
  name: string | null;
  slug: string | null;
  avatar: string | null;
  avatarColor: string | null;
};

export type Comment = {
  id: number;
  commentableType: string;
  commentableId: number | string;
  parentId: number | null;
  rootId: number | null;
  depth: number;
  path: string;
  body: string;
  status: "visible" | "pending" | "hidden" | "deleted_soft";
  repliesCount: number;
  reactionsCount: number;
  likedByMe: boolean;
  userId: number | null;
  user?: PublicMiniUser | null;
  createdAt: string | null;
  updatedAt: string | null;
};

export type Cursor<T> = {
  items: T[];
  cursor: { next: string | null; prev: string | null };
};

export type ToggleLikeResult = { liked: boolean; count: number };
