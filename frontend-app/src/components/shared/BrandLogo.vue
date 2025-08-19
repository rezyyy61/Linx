<script setup lang="ts"></script>

<template>
  <RouterLink
      to="/"
      aria-label="Linxx — Home"
      class="group inline-flex items-center gap-3 select-none focus:outline-none"
  >
      <span class="inline-flex h-8 w-8 rounded-full bg-red-600 relative overflow-hidden">
            <span class="absolute inset-0 bg-gradient-to-br from-red-500/40 to-black/20"></span>
            <span class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 h-5 w-5 rounded-full bg-white dark:bg-[#0f1115]"></span>
          </span>
    <span class="font-extrabold tracking-wide text-gray-900 dark:text-white text-2xl leading-none">
            Linxx
          </span>
  </RouterLink>
</template>

<style scoped>
.group:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px white, 0 0 0 4px #dc2626;
}
@media (prefers-reduced-motion: reduce) {
  .group :is(span, svg) {
    transition: none !important;
  }
}
</style>
