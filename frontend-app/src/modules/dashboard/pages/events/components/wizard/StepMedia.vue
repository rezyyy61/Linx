<script setup lang="ts">
import { ref } from "vue";
import EventCoverUploader from "../EventCoverUploader.vue";
import EventDocumentsUploader from "../EventDocumentsUploader.vue";

// eslint-disable-next-line @typescript-eslint/no-unused-vars
const props = defineProps<{
  eventId?: number | null;
  currentCoverUrl?: string | null;
  initialDocs?: Array<{ id: number; url: string | null }>;
}>();

const emit = defineEmits<{
  (e:"update:cover", v: { id: number; url: string | null } | null): void;
  (e:"update:docs", v: Array<{ id: number; url: string | null }>): void;
}>();

const cover = ref<{ id: number; url: string | null } | null>(null);
const docs  = ref<Array<{ id: number; url: string | null }>>([]);

function onCoverSelected(v: { id: number; url: string | null } | null) {
  cover.value = v;
  emit("update:cover", v);
}
function onDocsChanged(v: Array<{ id: number; url: string | null }>) {
  docs.value = v;
  emit("update:docs", v);
}
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <EventCoverUploader
      :event-id="eventId || undefined"
      :current-cover-url="(cover && cover.url) ? cover.url : (currentCoverUrl || null)"
      @selected="onCoverSelected"
      @updated="() => {}"
      @cleared="() => onCoverSelected(null)"
    />
    <EventDocumentsUploader
      :event-id="eventId || undefined"
      :initial-docs="initialDocs || []"
      @changed="onDocsChanged"
      @updated="() => {}"
    />
  </div>
</template>
