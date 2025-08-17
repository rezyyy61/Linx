<template>
  <div class="mx-auto max-w-xl text-center py-24">
    <p class="text-sm font-semibold text-blue-600">
      404
    </p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight">
      Page not found
    </h1>
    <p class="mt-2 text-gray-600">
      The page you’re looking for doesn’t exist or has been moved.
    </p>
    <div class="mt-6 flex items-center justify-center gap-3">
      <RouterLink
        to="/"
        class="btn"
      >
        Go Home
      </RouterLink>
      <RouterLink
        to="/dashboard"
        class="btn btn-secondary"
      >
        Dashboard
      </RouterLink>
    </div>
  </div>
</template>

<style scoped>
.btn {
  @apply inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition;
}
.btn-secondary {
  @apply bg-gray-900 hover:bg-gray-800;
}
</style>
