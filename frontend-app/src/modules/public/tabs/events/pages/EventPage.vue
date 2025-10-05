<script setup lang="ts">
import { watch, onMounted } from "vue"
import EventsFilters from "../components/EventsFilters.vue"
import EventCard from "../components/EventCard.vue"
import { useEvents } from "../composables/useEvents"

const { events, query, status, dateRange, loadEvents } = useEvents()

onMounted(() => {
  loadEvents()
})

watch([query, status, dateRange], () => {
  loadEvents()
})
</script>

<template>
  <section class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <h1 class="text-2xl font-semibold text-rose-600 tracking-tight relative mb-4">
      <span class="relative z-10">Events</span>
      <span class="absolute left-0 bottom-0 w-12 h-1 bg-rose-500 rounded-full" />
    </h1>

    <EventsFilters
      v-model:query="query"
      v-model:status="status"
      v-model:date_range="dateRange"
    />

    <div
      v-if="events.length === 0"
      class="text-center py-20 text-neutral-600 dark:text-neutral-400"
    >
      No events found.
    </div>

    <div
      v-else
      class="grid md:grid-cols-2 lg:grid-cols-3 gap-6"
    >
      <EventCard
        v-for="ev in events"
        :key="ev.id"
        :event="ev"
      />
    </div>
  </section>
</template>
