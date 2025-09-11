<template>
  <aside class="sticky top-20 space-y-5">
    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <h3 class="text-sm font-semibold tracking-tight">
        Explore
      </h3>
      <div class="mt-3 grid grid-cols-2 gap-2">
        <RouterLink
          v-for="l in explore"
          :key="l.to"
          :to="l.to"
          class="group relative overflow-hidden rounded-xl border border-zinc-200/70 bg-white/70 px-3 py-2 text-sm transition hover:border-indigo-300/60 hover:shadow-md dark:border-zinc-800/70 dark:bg-zinc-900/50"
        >
          <div class="flex items-center gap-2">
            <span
              :class="l.icon"
              class="h-4 w-4 text-indigo-600/90 dark:text-indigo-400"
            />
            <span class="truncate">{{ l.label }}</span>
          </div>
          <div class="pointer-events-none absolute -right-6 -top-6 h-16 w-16 rounded-full bg-gradient-to-tr from-indigo-500/10 to-fuchsia-500/10 opacity-0 blur-lg transition group-hover:opacity-100" />
        </RouterLink>
      </div>
    </section>

    <section
      class="overflow-hidden rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight">
          Upcoming events
        </h3>
        <RouterLink
          to="/events"
          class="text-xs text-indigo-600 hover:underline"
        >
          View all
        </RouterLink>
      </div>
      <ul class="mt-4 space-y-3">
        <li
          v-for="e in events"
          :key="e.id"
          class="flex items-center gap-3 rounded-xl p-2 transition hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40"
        >
          <div
            class="flex h-12 w-12 flex-col items-center justify-center rounded-xl border border-zinc-200/80 bg-white text-[11px] dark:border-zinc-800/80 dark:bg-zinc-900"
          >
            <div class="font-semibold text-indigo-700 dark:text-indigo-400">
              {{ e.month }}
            </div>
            <div class="text-zinc-500">
              {{ e.day }}
            </div>
          </div>
          <div class="min-w-0">
            <div class="truncate text-sm font-medium">
              {{ e.title }}
            </div>
            <div class="truncate text-xs text-zinc-500">
              {{ e.city }} · {{ e.time }}
            </div>
          </div>
          <span class="ml-auto rounded-full bg-zinc-900/5 px-2 py-0.5 text-[10px] tracking-wide text-zinc-600 dark:bg-white/5 dark:text-zinc-300">{{ e.type }}</span>
        </li>
      </ul>
    </section>

    <section
      class="rounded-2xl border border-zinc-200/70 bg-white/80 p-5 shadow-lg backdrop-blur dark:border-zinc-800/70 dark:bg-zinc-900/60"
    >
      <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold tracking-tight">
          Active campaigns
        </h3>
        <RouterLink
          to="/campaigns"
          class="text-xs text-indigo-600 hover:underline"
        >
          See more
        </RouterLink>
      </div>
      <ul class="mt-4 space-y-4">
        <li
          v-for="c in campaigns"
          :key="c.id"
        >
          <div class="flex items-center justify-between text-sm font-medium">
            <span class="truncate">{{ c.title }}</span>
            <span class="text-xs text-zinc-500">{{ c.progress }}%</span>
          </div>
          <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-200 dark:bg-zinc-800">
            <div
              class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-fuchsia-500"
              :style="{ width: c.progress + '%' }"
            />
          </div>
          <div class="mt-1 flex justify-between text-[11px] text-zinc-500">
            <span>{{ currency(c.raised) }} raised</span>
            <span>goal {{ currency(c.goal) }}</span>
          </div>
        </li>
      </ul>
    </section>
  </aside>
</template>

<script setup lang="ts">
const explore = [
  { to: '/events',        label: 'Events',        icon: 'i-mdi:calendar-star' },
  { to: '/campaigns',     label: 'Campaigns',     icon: 'i-mdi:bullhorn' },
  { to: '/announcements', label: 'Announcements', icon: 'i-mdi:megaphone-outline' },
  { to: '/books',         label: 'Books',         icon: 'i-mdi:book-open-page-variant' },
  { to: '/media',         label: 'Media',         icon: 'i-mdi:play-circle-outline' },
  { to: '/profiles',      label: 'Profiles',      icon: 'i-mdi:account-group-outline' },
]

const events = [
  { id: 1, title: 'Vue Tehran Meetup',  city: 'Tehran',  time: 'Sat 18:00', month: 'OCT', day: '12', type: 'Meetup' },
  { id: 2, title: 'Book Signing Night', city: 'Shiraz',  time: 'Sun 16:30', month: 'OCT', day: '20', type: 'Book' },
  { id: 3, title: 'Frontend Conf',      city: 'Isfahan', time: 'Thu 09:00', month: 'NOV', day: '02', type: 'Conf' },
]

const campaigns = [
  { id: 11, title: 'Community Library', progress: 72, raised: 18500, goal: 25000 },
  { id: 12, title: 'Open Media Fund',   progress: 41, raised:  8200, goal: 20000 },
  { id: 13, title: 'Education For All', progress: 88, raised: 35400, goal: 40000 },
]

function currency(n: number) {
  return new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(n)
}
</script>
