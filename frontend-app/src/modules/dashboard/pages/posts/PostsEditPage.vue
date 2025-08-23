<template>
  <div>
    <PageHeader
      :title="title"
      :description="description"
    />
    <div class="bg-white border rounded-xl p-4 max-w-2xl">
      <div class="text-gray-500">
        Edit form coming soon
      </div>
      <div class="text-xs text-gray-400 mt-2">
        ID: {{ id }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../../components/PageHeader.vue";
import { computed } from "vue";
import { useRoute } from "vue-router";

const route = useRoute();
const id = computed(() => route.params.id);
const map: Record<string, { title: string; desc: string }> = {
  "posts.edit": { title: "Edit Post", desc: "Update post" },
  "events.edit": { title: "Edit Event", desc: "Update event" },
  "campaigns.edit": { title: "Edit Campaign", desc: "Update campaign" },
  "announcements.edit": { title: "Edit Announcement", desc: "Update announcement" },
};
const meta = map[route.name?.toString() || "posts.edit"] || map["posts.edit"];
const title = computed(() => meta.title);
const description = computed(() => meta.desc);
</script>
