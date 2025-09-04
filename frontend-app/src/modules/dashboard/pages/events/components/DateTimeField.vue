<script setup lang="ts">
import { ref, watch, onMounted, computed } from "vue";
import { Icon } from "@iconify/vue";

const props = defineProps<{
  modelValue?: string | null;
  labelKey: string;
  datePlaceholderKey?: string;
  timePlaceholderKey?: string;
  errorKey?: string | null;
  minuteStep?: number;
}>();

const emit = defineEmits<{ (e: "update:modelValue", v: string | null): void }>();

const date = ref<string>("");
const time = ref<string>("");

function toIsoLocal(d: string, t: string): string | null {
  if (!d || !t) return null;
  return `${d}T${t}`;
}
function fromModel(v?: string | null) {
  if (!v) {
    date.value = "";
    time.value = "";
    return;
  }
  const m = v.split("T");
  date.value = m[0] || "";
  time.value = (m[1] || "").slice(0, 5);
}
function clearAll() {
  date.value = "";
  time.value = "";
  emit("update:modelValue", null);
}

watch([date, time], () => {
  const v = toIsoLocal(date.value, time.value);
  emit("update:modelValue", v);
});

onMounted(() => fromModel(props.modelValue));
watch(() => props.modelValue, (v) => fromModel(v));

const hasError = computed(() => !!props.errorKey);
const stepAttr = computed(() => Math.max(1, (props.minuteStep ?? 5)) * 60);
</script>

<template>
  <div class="space-y-2">
    <div class="flex items-center justify-between">
      <label class="block text-sm font-medium text-gray-800 dark:text-gray-200">{{ $t(labelKey) }}</label>
      <button
        type="button"
        class="inline-flex items-center gap-1 rounded-md border px-2 py-1 text-xs transition-colors"
        :class="hasError ? 'border-red-300 text-red-700 hover:bg-red-50' : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800'"
        @click="clearAll"
      >
        <Icon
          icon="mdi:close"
          class="h-3.5 w-3.5"
        />
        <span>{{ $t('common.clear') }}</span>
      </button>
    </div>

    <div
      class="rounded-xl border p-3 transition-colors"
      :class="hasError ? 'border-red-300 bg-red-50/40' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900'"
    >
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border text-gray-500 bg-white dark:bg-transparent dark:border-gray-600">
              <Icon
                icon="mdi:calendar"
                class="w-4 h-4"
              />
            </span>
          </span>
          <input
            v-model="date"
            type="date"
            class="w-full rounded-lg border pl-12 p-2.5 focus:outline-none focus:ring-2"
            :class="hasError ? 'border-red-300 focus:ring-red-400' : 'border-gray-300 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:placeholder-gray-400'"
            :placeholder="datePlaceholderKey ? ($t(datePlaceholderKey) as string) : ''"
            :aria-invalid="hasError"
          >
        </div>

        <div class="relative">
          <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-full border text-gray-500 bg-white dark:bg-transparent dark:border-gray-600">
              <Icon
                icon="mdi:clock-outline"
                class="w-4 h-4"
              />
            </span>
          </span>
          <input
            v-model="time"
            type="time"
            :step="stepAttr"
            class="w-full rounded-lg border pl-12 p-2.5 focus:outline-none focus:ring-2"
            :class="hasError ? 'border-red-300 focus:ring-red-400' : 'border-gray-300 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800 dark:placeholder-gray-400'"
            :placeholder="timePlaceholderKey ? ($t(timePlaceholderKey) as string) : ''"
            :aria-invalid="hasError"
          >
        </div>
      </div>
    </div>

    <p
      v-if="errorKey"
      class="text-xs text-red-600 mt-1"
    >
      {{ $t(errorKey) }}
    </p>
  </div>
</template>
