<script setup lang="ts">
import { computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useMediaStore } from '@/stores/post/post.media'
import CircularProgress from '@/modules/dashboard/pages/posts/components/upload/CircularProgress.vue'

const media = useMediaStore()
const { tasks } = storeToRefs(media)

const list = computed(() => Object.values(tasks.value || {}))
const value = computed(() => {
  if (!list.value.length) return 0
  const nums = list.value.map(t => (t.status === 'ready' ? 100 : (t.progress ?? 0)))
  const sum = nums.reduce((a, b) => a + b, 0)
  return Math.round(sum / nums.length)
})
</script>

<template>
  <div
    v-if="list.length"
    class="flex items-center justify-center py-2"
  >
    <CircularProgress
      :value="value"
      :size="56"
      :stroke="6"
    />
  </div>
</template>
