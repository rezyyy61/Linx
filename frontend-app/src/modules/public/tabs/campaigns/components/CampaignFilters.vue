<script setup lang="ts">
import { ref, watch, computed } from "vue"
import { Icon } from "@iconify/vue"

const props = defineProps<{
  modelValue?: {
    q?: string
    visibility?: "" | "public" | "members" | "private"
    kind?: "" | "financial" | "non_financial"
    order?: "recent" | "ending_soon"
  }
}>()

const emit = defineEmits<{(e:'update:modelValue', v:any):void, (e:'reset'):void}>()

const state = ref({
  q: props.modelValue?.q || "",
  visibility: props.modelValue?.visibility || "",
  kind: props.modelValue?.kind || "",
  order: props.modelValue?.order || "recent"
})

watch(() => props.modelValue, v => {
  if (!v) return
  state.value = { q: v.q || "", visibility: v.visibility || "", kind: v.kind || "", order: v.order || "recent" }
})

function update() {
  emit("update:modelValue", { ...state.value })
}

function reset() {
  state.value = { q: "", visibility: "", kind: "", order: "recent" }
  emit("reset")
}

const orderIcon = computed(() => state.value.order === "recent" ? "mdi:clock-time-four-outline" : "mdi:calendar-end")
</script>

<template>
  <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4">
    <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
      <div class="md:col-span-5">
        <div class="relative h-10">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
            <Icon
              icon="mdi:magnify"
              class="w-5 h-5"
            />
          </span>
          <input
            v-model.trim="state.q"
            type="text"
            placeholder="Search campaigns"
            class="h-10 w-full rounded-xl border border-gray-300 pl-10 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
          >
        </div>
      </div>

      <div class="md:col-span-3">
        <select
          v-model="state.visibility"
          class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
        >
          <option value="">
            All visibilities
          </option>
          <option value="public">
            Public
          </option>
          <option value="members">
            Members
          </option>
          <option value="private">
            Private
          </option>
        </select>
      </div>

      <div class="md:col-span-2">
        <select
          v-model="state.kind"
          class="h-10 w-full rounded-xl border border-gray-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-700 dark:bg-gray-800"
        >
          <option value="">
            All types
          </option>
          <option value="financial">
            Financial
          </option>
          <option value="non_financial">
            Non-financial
          </option>
        </select>
      </div>

      <div class="md:col-span-2 flex items-center gap-2">
        <button
          type="button"
          class="h-10 w-full inline-flex items-center justify-center gap-2 rounded-xl border px-3 text-sm dark:border-gray-700"
          @click="state.order = state.order==='recent' ? 'ending_soon' : 'recent'; update()"
        >
          <Icon
            :icon="orderIcon"
            class="w-5 h-5"
          />
          <span class="capitalize">{{ state.order.replace('_',' ') }}</span>
        </button>
        <button
          type="button"
          class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-gray-300 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800"
          @click="reset"
        >
          <Icon
            icon="mdi:close"
            class="w-4 h-4"
          />
          <span>Clear</span>
        </button>
      </div>
    </div>

    <div class="mt-3 flex items-center justify-end">
      <button
        type="button"
        class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 text-white px-4 py-2 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30"
        @click="update"
      >
        <Icon
          icon="mdi:filter"
          class="w-5 h-5"
        />
        <span>Apply</span>
      </button>
    </div>
  </div>
</template>
