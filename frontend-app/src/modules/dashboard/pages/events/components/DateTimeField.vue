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
  if (!v) { date.value = ""; time.value = ""; return; }
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
const stepMinutes = computed(() => Math.max(1, (props.minuteStep ?? 5)));
const stepAttr = computed(() => stepMinutes.value * 60);

const isFirefox = computed(() => typeof navigator !== "undefined" && /firefox/i.test(navigator.userAgent));

const timeOptions = computed(() => {
  const step = stepMinutes.value;
  const out: string[] = [];
  for (let m = 0; m < 24 * 60; m += step) {
    const hh = String(Math.floor(m / 60)).padStart(2, "0");
    const mm = String(m % 60).padStart(2, "0");
    out.push(`${hh}:${mm}`);
  }
  return out;
});

const showList = ref(false);
const activeIdx = ref(-1);
const filtered = computed(() => {
  const q = (time.value || "").trim();
  if (!q) return timeOptions.value;
  return timeOptions.value.filter(o => o.startsWith(q));
});
function openList() { if (isFirefox.value) { showList.value = true; activeIdx.value = -1; } }
function closeList() { showList.value = false; activeIdx.value = -1; }
function pickTime(v: string) { time.value = v; closeList(); }
function onKey(e: KeyboardEvent) {
  if (!isFirefox.value) return;
  if (!showList.value && (e.key === "ArrowDown" || e.key === "Enter")) { showList.value = true; e.preventDefault(); return; }
  if (!showList.value) return;
  if (e.key === "Escape") { closeList(); return; }
  if (e.key === "ArrowDown") { activeIdx.value = Math.min(filtered.value.length - 1, activeIdx.value + 1); e.preventDefault(); return; }
  if (e.key === "ArrowUp") { activeIdx.value = Math.max(0, activeIdx.value - 1); e.preventDefault(); return; }
  if (e.key === "Enter") {
    const v = filtered.value[activeIdx.value] || filtered.value[0];
    if (v) pickTime(v);
    e.preventDefault();
  }
}
let blurTimer: number | undefined;
function onBlur() { blurTimer = window.setTimeout(closeList, 100); }
function onListMouseDown(e: MouseEvent) { e.preventDefault(); if (blurTimer) { clearTimeout(blurTimer); blurTimer = undefined as any; } }
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
            class="w-full rounded-lg border pl-12 p-2.5 focus:outline-none focus:ring-2 bg-white dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 dark:[color-scheme:dark]"
            :class="hasError ? 'border-red-300 focus:ring-red-400' : 'border-gray-300 focus:ring-blue-500 dark:border-gray-700'"
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
            :type="isFirefox ? 'text' : 'time'"
            :step="!isFirefox ? stepAttr : undefined"
            inputmode="numeric"
            pattern="^([01]\\d|2[0-3]):([0-5]\\d)$"
            :placeholder="timePlaceholderKey ? ($t(timePlaceholderKey) as string) : 'HH:MM'"
            class="w-full rounded-lg border pl-12 p-2.5 focus:outline-none focus:ring-2 bg-white dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 dark:[color-scheme:dark]"
            :class="hasError ? 'border-red-300 focus:ring-red-400' : 'border-gray-300 focus:ring-blue-500 dark:border-gray-700'"
            :aria-invalid="hasError"
            @focus="openList"
            @blur="onBlur"
            @keydown="onKey"
          >

          <transition name="fade">
            <div
              v-if="isFirefox && showList && filtered.length"
              class="absolute z-20 mt-1 w-full rounded-lg border border-gray-200 bg-white shadow dark:border-gray-700 dark:bg-gray-800"
              @mousedown="onListMouseDown"
            >
              <ul class="max-h-56 overflow-auto py-1 text-sm">
                <li
                  v-for="(opt, i) in filtered"
                  :key="opt"
                  class="px-3 py-2 cursor-pointer"
                  :class="i === activeIdx ? 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200' : 'hover:bg-gray-50 dark:hover:bg-gray-700/60'"
                  @mouseenter="activeIdx = i"
                  @click="pickTime(opt)"
                >
                  {{ opt }}
                </li>
              </ul>
            </div>
          </transition>
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

<style scoped>
.fade-enter-active,.fade-leave-active{transition:opacity .12s ease}
.fade-enter-from,.fade-leave-to{opacity:0}
</style>
