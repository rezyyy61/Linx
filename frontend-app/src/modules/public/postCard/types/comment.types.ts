export type CommentAuthor = {
  id: string
  name: string
  username: string
  avatarUrl: string | null
  avatarColor: string | null
  verified: boolean
}

export type Comment = {
  id: string
  postId: string
  parentId: string | null
  body: string
  createdAt: string
  updatedAt: string | null
  author: CommentAuthor
  likes: number
  liked?: boolean
}
