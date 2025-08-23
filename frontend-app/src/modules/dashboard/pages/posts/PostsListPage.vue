<template>
  <div>
    <PageHeader
      :title="title"
      :description="description"
    >
      <template #actions>
        <RouterLink
          :to="createTo"
          class="px-3 py-2 rounded-md bg-gray-900 text-white"
        >
          Create
        </RouterLink>
      </template>
    </PageHeader>
    <EmptyState
      :icon="icon"
      :title="emptyTitle"
      :description="emptyDesc"
    >
      <RouterLink
        :to="createTo"
        class="px-3 py-2 rounded-md bg-gray-900 text-white"
      >
        New
      </RouterLink>
    </EmptyState>
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../../components/PageHeader.vue";
import EmptyState from "../../components/EmptyState.vue";
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const name = route.name?.toString() || "";
const map: Record<string, { title: string; desc: string; icon: string; create: string; emptyTitle: string; emptyDesc: string }> = {
  "posts.list": { title: "Posts", desc: "Manage posts", icon: "solar:document-linear", create: "posts.create", emptyTitle: "No posts", emptyDesc: "Create your first post" },
  "events.list": { title: "Events", desc: "Manage events", icon: "solar:calendar-linear", create: "events.create", emptyTitle: "No events", emptyDesc: "Create your first event" },
  "campaigns.list": { title: "Campaigns", desc: "Manage campaigns", icon: "solar:leaf-linear", create: "campaigns.create", emptyTitle: "No campaigns", emptyDesc: "Create your first campaign" },
  "announcements.list": { title: "Announcements", desc: "Manage announcements", icon: "solar:megaphone-linear", create: "announcements.create", emptyTitle: "No announcements", emptyDesc: "Create your first announcement" },
};
const meta = map[name] || map["posts.list"];
const title = computed(() => meta.title);
const description = computed(() => meta.desc);
const icon = computed(() => meta.icon);
const createTo = computed(() => ({ name: meta.create }));
const emptyTitle = computed(() => meta.emptyTitle);
const emptyDesc = computed(() => meta.emptyDesc);
</script>
