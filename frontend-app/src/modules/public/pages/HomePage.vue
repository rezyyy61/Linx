<script setup lang="ts">
import { onMounted, computed } from 'vue'
import { useRouter, RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth/auth'

const router = useRouter()
const auth = useAuthStore()

onMounted(() => {
  // یه بار وضعیت سشن رو می‌گیریم
  if (!auth.bootstrapDone) auth.bootstrap()
})

const username = computed(() => auth.user?.name ?? '')

async function onLogout() {
  await auth.logout()
  router.push({ name: 'home' })
}
</script>

<template>
  <section class="grid gap-10">
    <!-- Hero -->
    <div class="text-center py-10">
      <h1 class="text-3xl sm:text-4xl font-bold tracking-tight">
        Welcome to NewApp
      </h1>
      <p class="mt-3 text-gray-600 max-w-2xl mx-auto">
        A clean Vue 3 + Vite setup. This is the public area of your site.
      </p>

      <div class="mt-6 flex items-center justify-center gap-3">
        <!-- وقتی لاگین نیست -->
        <template v-if="!auth.isAuthenticated">
          <RouterLink
            to="/auth/login"
            class="btn"
          >
            Login
          </RouterLink>
          <RouterLink
            to="/auth/register"
            class="btn btn-outline"
          >
            Register
          </RouterLink>
          <RouterLink
            to="/dashboard"
            class="btn btn-outline"
          >
            Go to Dashboard
          </RouterLink>
        </template>

        <!-- وقتی لاگین هست -->
        <template v-else>
          <span class="inline-flex items-center rounded-md bg-gray-100 px-3 py-2 text-sm font-medium text-gray-800">
            Hi, {{ username }}
          </span>
          <RouterLink
            to="/dashboard"
            class="btn btn-outline"
          >
            Dashboard
          </RouterLink>
          <button
            type="button"
            class="btn"
            @click="onLogout"
          >
            Logout
          </button>
        </template>
      </div>
    </div>

    <!-- Features (placeholders) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <article class="card">
        <h3 class="card-title">
          Fast
        </h3>
        <p class="card-text">
          Vite + Vue 3 + Tailwind for a fast dev experience.
        </p>
      </article>
      <article class="card">
        <h3 class="card-title">
          Modular
        </h3>
        <p class="card-text">
          Public, Auth, and Dashboard are separated cleanly.
        </p>
      </article>
      <article class="card">
        <h3 class="card-title">
          Scalable
        </h3>
        <p class="card-text">
          Ready for routing, state, and API layers later.
        </p>
      </article>
    </div>
  </section>
</template>

<style scoped>
.btn {
  @apply inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition;
}
.btn-outline {
  @apply bg-white text-gray-900 border border-gray-300 hover:bg-gray-50;
}
.card {
  @apply rounded-lg border border-gray-200 bg-white p-5 shadow-sm;
}
.card-title {
  @apply text-lg font-semibold;
}
.card-text {
  @apply mt-1 text-gray-600;
}
</style>
