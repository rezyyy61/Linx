<template>
  <div>
    <PageHeader
      :title="title"
      :description="description"
    />
    <div class="bg-white border rounded-xl p-4 max-w-2xl">
      <div class="text-gray-500">
        Form coming soon
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../../components/PageHeader.vue";
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const map: Record<string, { title: string; desc: string }> = {
  "posts.create": { title: "Create Post", desc: "Add a new post" },
  "events.create": { title: "Create Event", desc: "Add a new event" },
  "campaigns.create": { title: "Create Campaign", desc: "Add a new campaign" },
  "announcements.create": { title: "Create Announcement", desc: "Add a new announcement" },
};
const meta = map[route.name?.toString() || "posts.create"] || map["posts.create"];
const title = computed(() => meta.title);
const description = computed(() => meta.desc);
</script>
