<script setup lang="ts">
import { reactive, computed, ref } from "vue";
import type { CreateEventPayload, EventItem } from "@/stores/event";
import { Icon } from "@iconify/vue";
import DateTimeField from "@/modules/dashboard/pages/events/components/DateTimeField.vue";
import EventCoverUploader from "@/modules/dashboard/pages/events/components/EventCoverUploader.vue";
import EventDocumentsUploader from "@/modules/dashboard/pages/events/components/EventDocumentsUploader.vue";

const props = withDefaults(defineProps<{
  modelValue?: Partial<EventItem | CreateEventPayload>;
  submitting?: boolean;
  isEdit?: boolean;
}>(), {
  modelValue: () => ({}),
  submitting: false,
  isEdit: false,
});

const emit = defineEmits<{
  (e: "submit", payload: CreateEventPayload): void;
  (e: "cancel"): void;
  (e: "update:modelValue", v: Partial<EventItem | CreateEventPayload>): void;
  (e: "media-updated"): void;
}>();

const form = reactive<CreateEventPayload>({
  title: (props.modelValue as any)?.title ?? "",
  description: (props.modelValue as any)?.description ?? null,
  starts_at: (props.modelValue as any)?.starts_at ?? "",
  ends_at: (props.modelValue as any)?.ends_at ?? null,
  timezone: (props.modelValue as any)?.timezone ?? "Europe/Amsterdam",
  location: (props.modelValue as any)?.location ?? null,
  capacity: (props.modelValue as any)?.capacity ?? null,
  is_published: (props.modelValue as any)?.is_published ?? false,
  organizer_id: (props.modelValue as any)?.organizer_id ?? null,
});

const selectedCover = ref<{ id: number; url: string | null } | null>(null);
const selectedDocs = ref<Array<{ id: number; url: string | null }>>([]);

function update<K extends keyof CreateEventPayload>(k: K, v: CreateEventPayload[K]) {
  (form[k] as any) = v as any;
  emit("update:modelValue", { ...form });
}

const errors = computed(() => {
  const e: Record<string, string> = {};
  if (!form.title || !form.title.trim()) e.title = "event.form.errors.titleRequired";
  if (!form.starts_at) e.starts_at = "event.form.errors.startsAtRequired";
  if (form.starts_at && form.ends_at && new Date(form.ends_at) < new Date(form.starts_at)) {
    e.ends_at = "event.form.errors.endsAfterStart";
  }
  if (form.capacity !== null && form.capacity !== undefined && form.capacity < 1) {
    e.capacity = "event.form.errors.capacityMin";
  }
  return e;
});

const isValid = computed(() => Object.keys(errors.value).length === 0);

function onSubmit() {
  if (!isValid.value) return;
  const payload = { ...form } as any;
  if (selectedCover.value?.id) payload.cover_id = selectedCover.value.id;
  if (selectedDocs.value.length) payload.documents = selectedDocs.value.map((d, i) => ({ id: d.id, order: i }));
  emit("submit", payload);
}

const eventId = computed(() => (props.modelValue as any)?.id as number | undefined);
const coverUrl = computed(() => (props.modelValue as any)?.cover_url as string | null | undefined);

function onCoverSelected(v: { id: number; url: string | null }) {
  selectedCover.value = v;
}
function onDocsChanged(v: Array<{ id: number; url: string | null }>) {
  selectedDocs.value = v;
}
</script>

<template>
  <form
    class="space-y-6"
    @submit.prevent="onSubmit"
  >
    <div class="grid grid-cols-1 gap-5">
      <div class="space-y-1">
        <label class="block text-sm font-medium">{{ $t("event.form.fields.title") }}</label>
        <input
          class="mt-1 w-full rounded border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:placeholder-gray-400"
          type="text"
          :value="form.title"
          :placeholder="$t('event.form.placeholders.title')"
          @input="update('title', ($event.target as HTMLInputElement).value)"
        >
        <p
          v-if="errors.title"
          class="text-xs text-red-600 mt-1"
        >
          {{ $t(errors.title) }}
        </p>
      </div>

      <div class="space-y-1">
        <label class="block text-sm font-medium">{{ $t("event.form.fields.location") }}</label>
        <input
          class="mt-1 w-full rounded border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          type="text"
          :value="form.location ?? ''"
          :placeholder="$t('event.form.placeholders.location')"
          @input="update('location', ($event.target as HTMLInputElement).value || null)"
        >
      </div>

      <div class="space-y-1">
        <label class="block text-sm font-medium">{{ $t("event.form.fields.description") }}</label>
        <textarea
          class="mt-1 w-full rounded border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 min-h-[100px] dark:border-gray-700 dark:bg-gray-800"
          :value="form.description ?? ''"
          :placeholder="$t('event.form.placeholders.description')"
          @input="update('description', ($event.target as HTMLTextAreaElement).value || null)"
        />
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <DateTimeField
        v-model="form.starts_at"
        label-key="event.form.fields.startsAt"
        date-placeholder-key="event.form.placeholders.date"
        time-placeholder-key="event.form.placeholders.time"
        :error-key="errors.starts_at || null"
        :minute-step="5"
      />
      <DateTimeField
        v-model="form.ends_at"
        label-key="event.form.fields.endsAt"
        date-placeholder-key="event.form.placeholders.date"
        time-placeholder-key="event.form.placeholders.time"
        :error-key="errors.ends_at || null"
        :minute-step="5"
      />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div class="space-y-1">
        <label class="block text-sm font-medium">{{ $t("event.form.fields.capacity") }}</label>
        <input
          class="mt-1 w-full rounded border p-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          type="number"
          min="1"
          :value="form.capacity ?? ''"
          :placeholder="$t('event.form.placeholders.capacity')"
          @input="update('capacity', ($event.target as HTMLInputElement).value ? Number(($event.target as HTMLInputElement).value) : null)"
        >
        <p
          v-if="errors.capacity"
          class="text-xs text-red-600 mt-1"
        >
          {{ $t(errors.capacity) }}
        </p>
      </div>
      <div class="flex items-center gap-2 pt-7">
        <input
          id="is_published"
          class="h-4 w-4"
          type="checkbox"
          :checked="!!form.is_published"
          @change="update('is_published', ( $event.target as HTMLInputElement).checked)"
        >
        <label
          for="is_published"
          class="text-sm"
        >{{ $t("event.form.fields.isPublished") }}</label>
      </div>
    </div>

    <div class="space-y-6">
      <EventCoverUploader
        v-if="eventId"
        :event-id="eventId!"
        :current-cover-url="coverUrl || null"
        @updated="$emit('media-updated')"
      />
      <EventCoverUploader
        v-else
        :current-cover-url="selectedCover?.url || null"
        @selected="onCoverSelected"
      />

      <EventDocumentsUploader
        v-if="eventId"
        :event-id="eventId!"
        @updated="$emit('media-updated')"
      />
      <EventDocumentsUploader
        v-else
        @changed="onDocsChanged"
      />
    </div>

    <div class="flex gap-3 pt-2">
      <button
        type="submit"
        :disabled="!isValid || submitting"
        class="inline-flex items-center gap-2 rounded bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 disabled:opacity-50 dark:bg-blue-500 dark:hover:bg-blue-600"
      >
        <Icon
          v-if="!isEdit"
          icon="mdi:plus-circle"
          class="w-5 h-5"
        />
        <Icon
          v-else
          icon="mdi:content-save"
          class="w-5 h-5"
        />
        <span>{{ isEdit ? $t("event.form.actions.submitEdit") : $t("event.form.actions.submitCreate") }}</span>
      </button>
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded border px-4 py-2 dark:border-gray-700"
        @click="$emit('cancel')"
      >
        <Icon
          icon="mdi:close"
          class="w-5 h-5"
        />
        <span>{{ $t("event.form.actions.cancel") }}</span>
      </button>
    </div>
  </form>
</template>
