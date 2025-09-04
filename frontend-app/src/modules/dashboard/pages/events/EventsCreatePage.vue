<script setup lang="ts">
import { Icon } from "@iconify/vue";
import EventWizard from "./components/EventWizard.vue";
import PageContainer from "@/modules/dashboard/pages/events/layout/PageContainer.vue";
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
          <h1 class="text-2xl font-semibold text-red-500 my-4">
            {{ $t("event.create.title") }}
          </h1>
        </div>
      </div>

      <EventWizard />
    </div>
  </PageContainer>
</template>
