<template>
  <div
    v-if="open"
    class="fixed z-[1000] w-72 overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-xl dark:border-zinc-700 dark:bg-zinc-800"
    :style="{ left: x + 'px', top: y + 'px' }"
  >
    <ul class="max-h-80 overflow-auto">
      <li
        v-if="loading"
        class="px-3 py-2 text-xs text-zinc-500"
      >
        Searching…
      </li>
      <template v-else>
        <li
          v-for="(u,i) in items"
          :key="u.id"
        >
          <button
            type="button"
            class="flex w-full items-center gap-3 px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700"
            :class="i===active ? 'bg-zinc-100 dark:bg-zinc-700' : ''"
            @pointerdown.prevent.stop="$emit('pick', u)"
            @keydown.enter.prevent.stop="$emit('pick', u)"
          >
            <AvatarUser
              :src="u.avatar || undefined"
              :name="u.name || u.slug"
              :color="u.avatarColor || undefined"
              size="sm"
              rounded="full"
              ring
              zoomable
            />
            <div class="min-w-0">
              <div class="truncate font-medium">
                {{ u.name || u.slug }}
              </div>
              <div class="truncate text-xs text-zinc-500">
                @{{ u.slug }}
              </div>
            </div>
          </button>
        </li>
        <li
          v-if="!items.length"
          class="px-3 py-2 text-xs text-zinc-500"
        >
          No results
        </li>
      </template>
    </ul>
  </div>
</template>

<script setup lang="ts">
import AvatarUser from "@/components/shared/AvatarUser.vue"

defineProps<{
  open: boolean
  x: number
  y: number
  items: { id:number; slug:string; name?:string; avatar?:string|null; avatarColor?:string|null }[]
  active: number
  loading: boolean
}>()

defineEmits<{ (e:'pick', u:any):void }>()
</script>
