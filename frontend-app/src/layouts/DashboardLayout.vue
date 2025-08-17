<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-6">
        <RouterLink
          to="/dashboard"
          class="font-semibold text-lg tracking-tight"
        >
          Dashboard
        </RouterLink>
        <nav class="flex items-center gap-4 text-sm">
          <RouterLink
            to="/dashboard"
            class="nav-link"
          >
            Home
          </RouterLink>
        </nav>
        <div class="ml-auto">
          <RouterLink
            to="/"
            class="text-sm text-gray-600 hover:text-gray-900 transition"
          >
            Public Site
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
      <RouterView />
    </main>
  </div>
</template>

<style scoped>
.nav-link {
  @apply text-gray-600 hover:text-gray-900 transition;
}
.router-link-active.nav-link {
  @apply text-gray-900 font-semibold;
}
</style>
