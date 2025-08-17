<template>
  <div class="min-h-screen bg-gray-50 text-gray-900">
    <header class="border-b bg-white">
      <div class="mx-auto max-w-7xl px-4 py-3 flex items-center gap-6">
        <RouterLink
          to="/"
          class="font-semibold text-lg tracking-tight"
        >
          NewApp
        </RouterLink>

        <nav class="flex items-center gap-4 text-sm">
          <RouterLink
            to="/"
            class="nav-link"
          >
            Home
          </RouterLink>
          <RouterLink
            to="/dashboard"
            class="nav-link"
          >
            Dashboard
          </RouterLink>
        </nav>

        <div class="ml-auto flex items-center gap-3">
          <RouterLink
            to="/auth/login"
            class="btn btn-primary-outline"
          >
            Login
          </RouterLink>
          <RouterLink
            to="/auth/register"
            class="btn"
          >
            Register
          </RouterLink>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-7xl px-4 py-8">
      <RouterView />
    </main>

    <footer class="border-t bg-white">
      <div class="mx-auto max-w-7xl px-4 py-6 text-sm text-gray-500">
        © {{ new Date().getFullYear() }} NewApp. All rights reserved.
      </div>
    </footer>
  </div>
</template>

<style scoped>
.nav-link {
  @apply text-gray-600 hover:text-gray-900 transition;
}
.router-link-active.nav-link {
  @apply text-gray-900 font-semibold;
}
.btn { @apply inline-flex items-center justify-center rounded-md border px-3 py-1.5 text-sm transition; }
.btn-primary-outline { @apply border-gray-300 text-gray-700 hover:bg-gray-50; }
.btn:not(.btn-primary-outline) { @apply border-transparent bg-blue-600 text-white hover:bg-blue-700; }
</style>

