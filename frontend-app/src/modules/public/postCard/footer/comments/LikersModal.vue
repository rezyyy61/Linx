<template>
  <div
    v-if="open"
    class="fixed inset-0 z-50 flex items-center justify-center"
  >
    <div
      class="absolute inset-0 bg-black/40"
      @click="emit('close')"
    />
    <div class="relative z-10 w-full max-w-sm rounded-2xl border border-zinc-200 bg-white p-4 shadow-xl dark:border-zinc-800 dark:bg-zinc-900">
      <div class="mb-3 flex items-center justify-between">
        <div class="text-sm font-medium text-zinc-800 dark:text-zinc-100">
          Liked by
        </div>
        <button
          class="rounded-lg p-1 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          @click="emit('close')"
        >
          ✕
        </button>
      </div>

      <div class="max-h-80 space-y-2 overflow-y-auto">
        <div
          v-for="u in items"
          :key="u.id"
          class="flex items-center gap-2 rounded-lg p-2 hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
        >
          <AvatarUser
            :src="u.avatar || undefined"
            :name="u.name || ''"
            size="sm"
            rounded="full"
          />
          <div class="min-w-0">
            <div class="truncate text-sm text-zinc-800 dark:text-zinc-100">
              {{ u.name || 'User' }}
            </div>
            <div class="text-xs text-zinc-500">
              {{ u.slug || '' }}
            </div>
          </div>
        </div>

        <div
          v-if="loading"
          class="py-2 text-center text-xs text-zinc-500"
        >
          Loading…
        </div>
        <div
          v-else-if="hasMore"
          class="pt-1"
        >
          <button
            class="w-full rounded-lg border px-3 py-1.5 text-sm hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800"
            @click="loadMore"
          >
            Load more
          </button>
        </div>
        <div
          v-else-if="!items.length && !loading"
          class="py-6 text-center text-sm text-zinc-500"
        >
          No likes yet
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import {computed, inject, onMounted, ref} from "vue";
import type { PublicMiniUser } from "@/modules/public/postCard/types/comment.types";
import AvatarUser from "@/components/shared/AvatarUser.vue";

const props = defineProps<{
  open: boolean
  postId: number | string
  commentId: number
}>();

const emit = defineEmits<{ (e: "close"): void }>();

const ctx = inject<any>("commentCtx");

const items = ref<PublicMiniUser[]>([]);
const next = ref<string | null>(null);
const loading = ref(false);

async function fetch(cursor?: string | null) {
  if (loading.value) return;
  loading.value = true;
  try {
    const res = await ctx.getLikers(props.commentId, cursor || null, 20);
    if (!cursor) items.value = res.items;
    else items.value = items.value.concat(res.items);
    next.value = res.cursor.next;
  } finally {
    loading.value = false;
  }
}

function loadMore() {
  if (!next.value) return;
  fetch(next.value);
}

const hasMore = computed(() => Boolean(next.value));

onMounted(() => {
  if (props.open) fetch(null);
});
</script>
