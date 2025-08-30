import { z } from 'zod'

export const postSchema = z.object({
  content: z.string().max(5000).nullable().optional(),
  visibility: z.enum(['public','private','friends']).default('public'),
  status: z.enum(['draft','published','archived']).default('published'),
  published_at: z.string().datetime().nullable().optional(),
  media: z.array(z.object({ id: z.number().int().positive(), order: z.number().int().min(0) })).max(10).optional()
}).refine(v => v.status !== 'published' || !!v.published_at, { path: ['published_at'], message: 'required_when_published' })

export type PostForm = z.infer<typeof postSchema>

export function validatePost(input: unknown) {
  const r = postSchema.safeParse(input)
  if (r.success) return { ok: true as const, value: r.data, errors: null as null }
  const errs: Record<string,string> = {}
  for (const i of r.error.issues) errs[i.path.join('.') || '_'] = i.message
  return { ok: false as const, value: null as null, errors: errs }
}
