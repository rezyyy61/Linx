<template>
  <aside class="sticky top-20 space-y-5">
    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight">
          Announcements
        </h3>
        <RouterLink
          to="/announcements"
          class="text-xs text-indigo-600 hover:underline"
        >
          All
        </RouterLink>
      </div>
      <ul class="mt-3 space-y-3">
        <li
          v-for="a in announcements"
          :key="a.id"
          class="flex items-start gap-3 rounded-xl p-2 transition hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40"
        >
          <span
            :class="a.important ? 'bg-rose-500' : 'bg-emerald-500'"
            class="mt-1 h-2.5 w-2.5 rounded-full"
          />
          <div class="min-w-0">
            <div class="truncate text-sm font-medium">
              {{ a.title }}
            </div>
            <div class="truncate text-xs text-zinc-500">
              {{ a.when }}
            </div>
          </div>
        </li>
      </ul>
    </section>

    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight">
          Featured books
        </h3>
        <RouterLink
          to="/books"
          class="text-xs text-indigo-600 hover:underline"
        >
          Browse
        </RouterLink>
      </div>
      <ul class="mt-3 space-y-3">
        <li
          v-for="b in books"
          :key="b.id"
          class="flex items-center gap-3"
        >
          <div class="h-12 w-9 shrink-0 rounded-md bg-gradient-to-br from-indigo-400 to-fuchsia-500 opacity-80 dark:opacity-70" />
          <div class="min-w-0">
            <div class="truncate text-sm font-medium">
              {{ b.title }}
            </div>
            <div class="truncate text-xs text-zinc-500">
              {{ b.author }}
            </div>
          </div>
          <RouterLink
            :to="`/books/${b.id}`"
            class="ml-auto rounded-lg border px-2.5 py-1 text-xs dark:border-zinc-700"
          >
            View
          </RouterLink>
        </li>
      </ul>
    </section>

    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <h3 class="text-sm font-semibold tracking-tight">
        Who to follow
      </h3>
      <ul class="mt-3 space-y-3">
        <li
          v-for="u in whoToFollow"
          :key="u.id"
          class="flex items-center gap-3"
        >
          <img
            :src="u.avatar"
            class="h-9 w-9 rounded-full object-cover ring-2 ring-indigo-500/10"
          >
          <div class="min-w-0">
            <div class="truncate text-sm font-medium">
              {{ u.name }}
            </div>
            <div class="truncate text-xs text-zinc-500">
              @{{ u.handle }}
            </div>
          </div>
          <button class="ml-auto rounded-xl border px-3 py-1 text-xs transition hover:border-indigo-300 hover:text-indigo-600 dark:border-zinc-700">
            Follow
          </button>
        </li>
      </ul>
    </section>

    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <h3 class="text-sm font-semibold tracking-tight">
        Trends
      </h3>
      <ul class="mt-3 space-y-2 text-sm">
        <li
          v-for="t in trends"
          :key="t.tag"
          class="flex items-center justify-between"
        >
          <RouterLink
            :to="`/search?q=${encodeURIComponent('#' + t.tag)}`"
            class="hover:underline"
          >
            #{{ t.tag }}
          </RouterLink>
          <span class="text-xs text-zinc-500">{{ compact(t.count) }}</span>
        </li>
      </ul>
    </section>
  </aside>
</template>

<script setup lang="ts">
const announcements = [
  { id: 101, title: 'Site maintenance tonight', when: 'Today · 21:00', important: true },
  { id: 102, title: 'New campaign toolkit released', when: 'Yesterday', important: false },
  { id: 103, title: 'Book fair registrations open', when: '2d ago', important: false },
]

const books = [
  { id: 201, title: 'Patterns of Vue 3', author: 'A. Dev' },
  { id: 202, title: 'Designing Campaigns', author: 'R. Strategist' },
  { id: 203, title: 'Events That Scale', author: 'M. Planner' },
]

const whoToFollow = [
  { id: 301, name: 'Sara K.',   handle: 'sarak',  avatar: 'https://picsum.photos/seed/s1/80' },
  { id: 302, name: 'Ali M.',    handle: 'alim',   avatar: 'https://picsum.photos/seed/s2/80' },
  { id: 303, name: 'Niloofar',  handle: 'nili',   avatar: 'https://picsum.photos/seed/s3/80' },
]

const trends = [
  { tag: 'frontend', count: 18420 },
  { tag: 'vue',      count: 12200 },
  { tag: 'tailwind', count:  8800 },
]

function compact(n: number) {
  return new Intl.NumberFormat(undefined, { notation: 'compact', maximumFractionDigits: 1 }).format(n)
}
</script>
