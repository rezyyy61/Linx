<template>
  <div class="space-y-4">
    <div class="flex items-center justify-between">
      <div class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
        Comments
      </div>
      <div class="flex items-center gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
        <button
          class="rounded-md px-2 py-1 text-xs"
          :class="sortBy==='newest' ? 'bg-white shadow-sm dark:bg-zinc-700' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
          @click="sortBy='newest'"
        >
          Newest
        </button>
        <button
          class="rounded-md px-2 py-1 text-xs"
          :class="sortBy==='top' ? 'bg-white shadow-sm dark:bg-zinc-700' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200'"
          @click="sortBy='top'"
        >
          Top
        </button>
      </div>
    </div>

    <div
      v-if="pageItems.length"
      class="divide-y divide-zinc-100 dark:divide-zinc-800"
    >
      <div
        v-for="root in pageItems"
        :key="root.id"
        class="py-3 first:pt-0 last:pb-0"
      >
        <FlatThread
          :post-id="postId"
          :root="root"
          :sort-by="sortBy"
          @added="emit('added')"
        />
      </div>
    </div>
    <div
      v-else
      class="py-10 text-center text-sm text-zinc-500"
    >
      No comments yet
    </div>

    <div
      v-if="totalPages>1"
      class="flex justify-center pt-1"
    >
      <button
        class="rounded-lg border px-3 py-1.5 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800 disabled:opacity-50"
        :disabled="page>=totalPages"
        @click="loadMore"
      >
        Load more
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, onMounted } from 'vue'
import { useComments, type CommentSort } from '@/modules/public/postCard/composables/useComments'
import FlatThread from './FlatThread.vue'

const emit = defineEmits<{ (e:'added'): void }>()
const props = defineProps<{ postId: string; pageSize?: number }>()
const { roots, sort, loadFirst, loadMore: _loadMore } = useComments(props.postId)

const sortBy = ref<CommentSort>('newest')
const pageSize = computed(() => props.pageSize ?? 3)
const page = ref(1)

const rootItems = computed(() => sort(roots(props.postId), sortBy.value))
const totalPages = computed(() => Math.max(1, Math.ceil(rootItems.value.length / pageSize.value)))
const pageItems = computed(() => rootItems.value.slice(0, page.value * pageSize.value))

function loadMore() {
  page.value += 1
  if (page.value * pageSize.value > rootItems.value.length) {
    _loadMore(props.postId)
  }
}

onMounted(() => { void loadFirst(props.postId) })

</script>
