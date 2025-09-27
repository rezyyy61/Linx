<template>
  <div class="grid gap-5">
    <div
      v-if="loading && !items.length"
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
    >
      <AnnouncementSkeleton
        v-for="i in 6"
        :key="i"
      />
    </div>

    <div v-else-if="!items.length">
      <EmptyState />
    </div>

    <div
      v-else
      class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5"
    >
      <AnnouncementCard
        v-for="a in items"
        :key="a.id"
        :announcement="a"
      />
    </div>

    <div
      v-if="hasMore || loading"
      class="flex items-center justify-center pt-2"
    >
      <button
        class="rounded-xl border px-4 py-2 text-sm dark:border-zinc-700 hover:bg-gray-50 dark:hover:bg-zinc-800 disabled:opacity-60"
        :disabled="loading || !hasMore"
        @click="$emit('load-more')"
      >
        <span v-if="!loading">Load more</span>
        <span
          v-else
          class="inline-flex items-center gap-2"
        >
          <svg
            class="animate-spin h-4 w-4"
            viewBox="0 0 24 24"
          >
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
              fill="none"
            />
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"
            />
          </svg>
          Loading
        </span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import AnnouncementCard, { type AnnouncementCardModel } from "./AnnouncementCard.vue"
import AnnouncementSkeleton from "./AnnouncementSkeleton.vue"
import EmptyState from "./EmptyState.vue"

defineProps<{
  items: AnnouncementCardModel[];
  loading?: boolean;
  hasMore?: boolean;
}>();
defineEmits(["load-more"]);
</script>
