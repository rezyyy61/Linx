<template>
  <div class="flex items-center cursor-pointer gap-3 p-3 rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-800/60">
    <AvatarUser
      :src="user.avatar"
      :name="user.name"
      :color="user.avatar_color"
      size="md"
    />
    <div class="flex-1 min-w-0">
      <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
        {{ user.name }}
      </p>
      <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">
        @{{ user.slug }}
      </p>
    </div>
    <slot />
  </div>
</template>

<script setup lang="ts">
import AvatarUser from "@/components/shared/AvatarUser.vue"
type UserLite = { id:number; name:string; slug:string; avatar?:string|null; avatar_color?:string|null }
defineProps<{ user: UserLite }>()
</script>
