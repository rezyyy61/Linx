<template>
  <section class="grid gap-6">
    <!-- Header -->
    <div class="flex items-end justify-between">
      <div>
        <h1 class="text-2xl font-bold tracking-tight">
          Dashboard
        </h1>
        <p class="text-gray-600 mt-1">
          Overview of your app.
        </p>
      </div>
      <div class="flex items-center gap-3">
        <button
          type="button"
          class="btn"
        >
          Primary Action
        </button>
        <button
          type="button"
          class="btn btn-outline"
        >
          Secondary
        </button>
      </div>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="stat-card">
        <p class="stat-label">
          Users
        </p>
        <p class="stat-value">
          1,234
        </p>
      </div>
      <div class="stat-card">
        <p class="stat-label">
          Sessions
        </p>
        <p class="stat-value">
          8,912
        </p>
      </div>
      <div class="stat-card">
        <p class="stat-label">
          Conversions
        </p>
        <p class="stat-value">
          324
        </p>
      </div>
      <div class="stat-card">
        <p class="stat-label">
          Errors
        </p>
        <p class="stat-value">
          7
        </p>
      </div>
    </div>

    <!-- Content cards -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <article class="card lg:col-span-2">
        <h3 class="card-title">
          Recent Activity
        </h3>
        <p class="card-text">
          This is a placeholder area for recent events.
        </p>
      </article>
      <article class="card">
        <h3 class="card-title">
          Quick Links
        </h3>
        <ul class="mt-3 space-y-2 text-sm">
          <li>
            <a
              href="#"
              class="link"
            >View reports</a>
          </li>
          <li>
            <a
              href="#"
              class="link"
            >Manage users</a>
          </li>
          <li>
            <a
              href="#"
              class="link"
            >System settings</a>
          </li>
        </ul>
      </article>
    </div>
  </section>
</template>

<style scoped>
.btn {
  @apply inline-flex items-center justify-center rounded-md bg-blue-600 px-3 py-2 text-sm font-medium text-white hover:bg-blue-700 transition;
}
.btn-outline {
  @apply bg-white text-gray-900 border border-gray-300 hover:bg-gray-50;
}
.stat-card {
  @apply rounded-lg border border-gray-200 bg-white p-4 shadow-sm;
}
.stat-label {
  @apply text-xs uppercase tracking-wide text-gray-500;
}
.stat-value {
  @apply mt-1 text-2xl font-semibold;
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
.link {
  @apply text-blue-600 hover:underline;
}
</style>
