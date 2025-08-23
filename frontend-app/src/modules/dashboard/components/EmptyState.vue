<template>
  <div class="border rounded-xl bg-white p-8 text-center">
    <div class="mx-auto w-12 h-12 mb-3 flex items-center justify-center rounded-full bg-gray-100">
      <Icon
        :icon="icon"
        width="22"
      />
    </div>
    <h3 class="font-medium">
      {{ title }}
    </h3>
    <p class="text-gray-500 mt-1">
      {{ description }}
    </p>
    <div class="mt-4">
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Icon } from "@iconify/vue";
defineProps<{ icon: string; title: string; description: string }>();
</script>
