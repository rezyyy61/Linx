<template>
  <section
    class="min-h-screen grid place-items-center bg-surface px-4 py-10"
    dir="ltr"
  >
    <div class="w-full max-w-6xl rounded-2xl overflow-hidden border border-gray-200/70 bg-white/85 shadow-2xl backdrop-blur-md dark:border-zinc-800/80 dark:bg-zinc-900/75">
      <div
        :class="hasOauth
          ? 'grid grid-cols-1 md:grid-cols-12 md:divide-x md:divide-gray-200/70 dark:md:divide-white/10'
          : 'grid grid-cols-1'"
      >
        <div
          v-if="hasOauth"
          class="p-6 md:p-10 flex items-start md:items-center md:col-span-5"
        >
          <div class="w-full max-w-sm mx-auto md:mx-0">
            <slot name="oauth" />
          </div>
        </div>

        <div
          class="p-6 md:p-10 flex items-start md:items-center"
          :class="hasOauth ? 'md:col-span-7' : ''"
        >
          <div :class="hasOauth ? 'w-full max-w-md mx-auto md:mx-0' : 'w-full max-w-lg mx-auto'">
            <slot name="form" />
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
defineOptions({ name: 'AuthLayout' })
import { useSlots, computed, Comment } from 'vue'
const slots = useSlots()
const hasOauth = computed(() => {
  const vnodes = slots.oauth?.() || []
  return vnodes.some(v => v && v.type !== Comment)
})
</script>
