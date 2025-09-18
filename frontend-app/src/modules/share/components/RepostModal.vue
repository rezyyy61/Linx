<template>
  <transition
    appear
    enter-active-class="transition ease-out duration-200"
    enter-from-class="opacity-0"
    enter-to-class="opacity-100"
    leave-active-class="transition ease-in duration-150"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="open"
      class="fixed inset-0 z-[60]"
    >
      <div
        class="absolute inset-0 bg-black/50 dark:bg-black/70"
        @click="emit('close')"
      />
      <div class="absolute inset-0 flex items-center justify-center p-4">
        <div
          class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 shadow-xl ring-1 ring-black/5 dark:ring-white/10 flex max-h-[85vh] flex-col"
        >
          <!-- Header -->
          <div class="flex items-center justify-between px-5 py-2 border-b border-gray-100 dark:border-gray-800">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
              Repost
            </h3>
            <button
              class="p-2 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              <Icon
                icon="mdi:close"
                class="h-5 w-5 text-gray-500 dark:text-gray-400"
              />
            </button>
          </div>

          <div class="px-5 py-4 space-y-4 flex-1 min-h-0 overflow-hidden">
            <div
              v-if="previewPost"
              class="flex-1 max-h-[45vh] overflow-y-auto rounded-xl"
            >
              <PostPreviewCompact :post="previewPost" />
            </div>

            <div class="space-y-2 shrink-0">
              <textarea
                v-model="text"
                rows="4"
                dir="auto"
                maxlength="5000"
                class="w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100 px-3 py-2 resize-y"
                placeholder="Add a note…"
              />
              <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                <span>{{ text.length }}/5000</span>
                <span
                  v-if="errorMsg"
                  class="text-red-600 dark:text-red-400"
                >{{ errorMsg }}</span>
              </div>
            </div>
          </div>

          <!-- Footer -->
          <div class="px-5 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2">
            <button
              class="px-3 py-2 text-sm rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800"
              @click="emit('close')"
            >
              Cancel
            </button>
            <button
              class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-xl bg-indigo-600 text-white hover:bg-indigo-500 disabled:opacity-60"
              :disabled="loading"
              @click="onSubmit"
            >
              <Icon
                icon="mdi:repeat-variant"
                class="h-5 w-5"
              />
              <span>Repost</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </transition>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { Icon } from '@iconify/vue'
import { createRepost } from '../services/repostApi'
import PostPreviewCompact from './PostPreviewCompact.vue'
import {api} from "@/lib/http";

interface Props {
  open: boolean
  postId: number
  post?: any
}
const props = defineProps<Props>()
const emit = defineEmits<{ (e: 'close'): void; (e: 'done', result: { id: number; url: string }): void }>()

const text = ref('')
const loading = ref(false)
const errorMsg = ref('')

const previewPost = ref<any>(props.post ? (props.post.original ?? props.post) : null)

function normalizeApiPost(api: any) {
  return {
    id: String(api.id),
    text: api.content ?? '',
    createdAt: api.created_at ?? null,
    editedAt: api.updated_at ?? null,
    isPinned: false,
    isRepost: !!api.repost_of_id,
    media: api.media ?? [],
    author: {
      name: api.author?.name ?? '',
      username: api.author?.slug ?? '',
      avatarUrl: api.author?.avatar ?? null,
      avatarColor: api.author?.avatarColor ?? null,
      verified: !!api.author?.verified,
    },
  }
}

const isApiShape = (p: any) => p && ('content' in p || 'created_at' in p)
const getOriginalId = (p: any): number | null => {
  const id = p?.repostOfId ?? p?.repost_of_id ?? p?.originalId ?? p?.original_id ?? null
  return id ? Number(id) : null
}



watch(
  () => props.open,
  async v => {
    if (!v) return

    const raw = props.post ?? null

    if (raw?.original) {
      const o = raw.original
      previewPost.value = isApiShape(o) ? normalizeApiPost(o) : o
    } else if (raw) {
      const oid = getOriginalId(raw)
      if (oid) {
        try {
          const { data } = await api.get(`/public/v1/posts/${oid}`)
          const apiPost = data?.data ?? data
          previewPost.value = normalizeApiPost(apiPost)
        } catch {
          previewPost.value = isApiShape(raw) ? normalizeApiPost(raw) : raw
        }
      } else {
        previewPost.value = isApiShape(raw) ? normalizeApiPost(raw) : raw
      }
    } else {
      try {
        const { data } = await api.get(`/public/v1/posts/${props.postId}`)
        const apiPost = data?.data ?? data
        if (apiPost?.repost_of_id) {
          const { data: d2 } = await api.get(`/public/v1/posts/${apiPost.repost_of_id}`)
          previewPost.value = normalizeApiPost(d2?.data ?? d2)
        } else {
          previewPost.value = normalizeApiPost(apiPost)
        }
      } catch {
        previewPost.value = null
      }
    }

    text.value = ''
    errorMsg.value = ''
  }
)

async function onSubmit() {
  loading.value = true
  errorMsg.value = ''
  try {
    const res = await createRepost(props.postId, { text: text.value || null, visibility: 'public' })
    emit('done', res)
  } catch (e: any) {
    errorMsg.value = 'Failed'
    console.log(e)
  } finally {
    loading.value = false
  }
}
</script>

