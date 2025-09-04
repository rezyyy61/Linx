<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import PageContainer from "@/modules/dashboard/pages/events/layout/PageContainer.vue";
import EventWizard from "./components/EventWizard.vue";
import { useEventStore } from "@/stores/event";
import type { EventItem } from "@/stores/event";

const route = useRoute();
const router = useRouter();
const store = useEventStore();

const id = Number(route.params.id);
const loading = ref(true);
const current = ref<EventItem | null>(null);

onMounted(async () => {
  try {
    const res = await store.fetchOne(id);
    current.value = (res as any).data ?? res;
  } catch {
    router.push("/dashboard/events");
  } finally {
    loading.value = false;
  }
});


</script>

<template>
  <PageContainer>
    <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <router-link
            to="/dashboard/events"
            class="inline-flex items-center gap-2 rounded border px-3 py-1.5 text-sm hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800"
          >
            <Icon
              icon="mdi:arrow-left"
              class="w-4 h-4"
            />
            <span>{{ $t("common.back") }}</span>
          </router-link>
          <h1 class="text-xl font-semibold">
            {{ $t("event.edit.title", { title: current?.title || '' }) }}
          </h1>
        </div>
      </div>

      <div
        v-if="loading"
        class="space-y-3"
      >
        <div class="h-10 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" />
        <div class="h-96 bg-gray-100 dark:bg-gray-800 rounded animate-pulse" />
      </div>

      <EventWizard
        v-else
        mode="edit"
        :event-id="id"
        :initial="current || {}"
      />
    </div>
  </PageContainer>
</template>
