<script setup lang="ts">
import { onMounted } from "vue"
import EventsFilters from "../components/EventsFilters.vue"
import EventCard from "../components/EventCard.vue"
import { useEvents } from "../composables/useEvents"

const { events, query, status, loadEvents } = useEvents()

onMounted(() => {
  loadEvents()
})
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-8">
    <h1 class="text-2xl font-bold mb-6 text-neutral-900 dark:text-neutral-100">
      Events
    </h1>

    <EventsFilters
      v-model:query="query"
      v-model:status="status"
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
