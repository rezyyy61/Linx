import type { Comment, Cursor, PublicMiniUser } from "../types/comment.types";

function adaptUser(i: any): PublicMiniUser | null {
  if (!i) return null;
  return {
    id: Number(i.id),
    name: i.name ?? null,
    slug: i.slug ?? null,
    avatar: i.avatar ?? null,
    avatarColor: i.avatarColor ?? null,
  };
}

export function adaptComment(i: any): Comment {
  return {
    id: Number(i.id),
    commentableType: i.commentable_type,
    commentableId: i.commentable_id,
    parentId: i.parent_id ?? null,
    rootId: i.root_id ?? null,
    depth: Number(i.depth ?? 0),
    path: i.path ?? "",
    body: i.body ?? "",
    status: i.status,
    repliesCount: Number(i.replies_count ?? 0),
    reactionsCount: Number(i.reactions_count ?? 0),
    likedByMe: Boolean(i.liked_by_me ?? false),
    userId: i.user_id ?? null,
    user: i.user ? adaptUser(i.user) : null,
    createdAt: i.created_at ?? null,
    updatedAt: i.updated_at ?? null,
  };
}

// برای سازگاری با کدی که قبلاً نوشتی
export const mapServerComment = adaptComment;

function unwrapCollection(res: any): { items: any[]; cursor: { next: string | null; prev: string | null } } {
  const root = res?.data?.items ? res : res?.items ? { data: res } : res;
  const container = root?.data?.items ? root.data : root;
  const items = Array.isArray(container?.items) ? container.items : [];

  const cursor =
    root?.cursor ??
    container?.cursor ??
    {
      next: root?.meta?.next_cursor ?? null,
      prev: root?.meta?.prev_cursor ?? null,
    };

  return {
    items,
    cursor: {
      next: cursor?.next ?? null,
      prev: cursor?.prev ?? null,
    },
  };
}

export function adaptCommentCursor(res: any): Cursor<Comment> {
  const { items, cursor } = unwrapCollection(res);
  return {
    items: items.map(adaptComment),
    cursor: { next: cursor.next ?? null, prev: cursor.prev ?? null },
  };
}

export function adaptUserCursor(res: any): Cursor<PublicMiniUser> {
  const { items, cursor } = unwrapCollection(res);
  return {
    items: items.map(adaptUser).filter(Boolean) as PublicMiniUser[],
    cursor: { next: cursor.next ?? null, prev: cursor.prev ?? null },
  };
}
