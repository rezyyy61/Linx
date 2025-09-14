<template>
  <div class="space-y-4">
    <PostCard
      v-for="p in items"
      :key="p.id"
      :post="p"
    />
    <button
      class="mt-4 rounded-lg border px-3 py-1.5"
      @click="loadMore"
    >
      Load more
    </button>
  </div>
</template>

<script setup lang="ts">
import PostCard from '@/modules/public/postCard/PostCard.vue'
import { usePublicFeed } from '@/modules/public/postCard/composables/usePublicFeed'
import { useFeedRealtime } from '@/modules/public/postCard/composables/useFeedRealtime'
import { usePostActions } from '@/modules/public/postCard/composables/usePostActions'
import { onMounted, onBeforeUnmount } from 'vue'

const { items, loadMore } = usePublicFeed()
const { ensure, setCounts } = usePostActions()

async function initAfterLoad() {
  await loadMore()
  for (const p of items.value) ensure(p)
}

const { start, stop } = useFeedRealtime(items, {
  channelName: 'public.posts',
  prependNew: true,
  accept: (p) => p.visibility === 'public',
  onUpsert: (p) => ensure(p),
  onCounts: (id, counts) => setCounts(id, counts),
})

onMounted(async () => {
  await initAfterLoad()
  await start()
})

onBeforeUnmount(() => { void stop() })
</script>
