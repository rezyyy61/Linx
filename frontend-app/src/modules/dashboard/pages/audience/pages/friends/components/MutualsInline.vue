<template>
  <button
    v-if="!loading && total > 0"
    class="group inline-flex items-center gap-2 text-xs text-slate-600 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200"
    :title="`${total} mutual friends`"
    @click="$emit('open')"
  >
    <span class="flex -space-x-2">
      <AvatarUser
        v-for="(m, i) in avatarsToShow"
        :key="m.id ?? i"
        :src="m.avatar || null"
        :name="m.name || ''"
        :color="m.avatar_color || null"
        size="xs"
        rounded="full"
        ring
        ring-color="zinc"
      />
      <span
        v-if="extraCount > 0"
        class="h-6 w-6 rounded-full ring-2 ring-white dark:ring-slate-900 flex items-center justify-center text-[10px] bg-slate-200 dark:bg-slate-700"
      >+{{ extraCount }}</span>
    </span>

    <span class="font-medium">{{ total }}</span>
    <span class="text-slate-500 dark:text-slate-400">mutual friends</span>
  </button>

  <div
    v-else-if="loading"
    class="h-6 w-28 rounded bg-slate-100 dark:bg-slate-800 animate-pulse"
  />
</template>

<script setup lang="ts">
import { onMounted, ref, computed, watch } from 'vue'
import AvatarUser from '@/components/shared/AvatarUser.vue'
import type { Id, ProfileLite } from '../../../types'
import { listMutuals } from '../../../api/follow'

const props = defineProps<{ userId: Id }>()
defineEmits<{ (e: 'open'): void }>()

const loading = ref(true)
const items = ref<ProfileLite[]>([])
const total = ref(0)

async function fetchMutuals() {
  loading.value = true
  try {
    const res = await listMutuals(props.userId, { per_page: 3, page: 1 })
    items.value = res.data ?? []
    total.value = res.meta?.total ?? items.value.length
  } finally {
    loading.value = false
  }
}

onMounted(fetchMutuals)
watch(() => props.userId, fetchMutuals)

const avatarsToShow = computed(() => items.value.slice(0, 3))
const extraCount = computed(() => Math.max(0, total.value - avatarsToShow.value.length))
</script>
