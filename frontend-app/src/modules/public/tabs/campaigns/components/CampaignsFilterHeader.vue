<script setup lang="ts">
import { Icon } from "@iconify/vue"
const props = defineProps<{
  modelValue: {
    q?: string
    kind?: "" | "fundraising" | "petition" | "volunteer" | "awareness"
    visibility?: "" | "public" | "members" | "private"
    order_by?: "publish_at" | "created_at" | "ends_at" | "progress"
    order_dir?: "asc" | "desc"
    page?: number
    per_page?: number
    status?: string
  }
  loading?: boolean
}>()
const emit = defineEmits<{
  (e:"update:modelValue", v:any): void
  (e:"apply"): void
  (e:"clear"): void
}>()
function set<K extends keyof typeof props.modelValue>(k:K, v:any){
  emit("update:modelValue", { ...props.modelValue, [k]: v })
}
function toggleOrder(){
  const dir = props.modelValue.order_dir === "asc" ? "desc" : "asc"
  emit("update:modelValue", { ...props.modelValue, order_dir: dir, page: 1 })
}
function apply(){ emit("apply") }
function clear(){ emit("clear") }
</script>

<template>
  <div class="rounded-2xl border border-neutral-200 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-4">
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-3">
      <div class="lg:col-span-4">
        <div class="relative h-10">
          <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-neutral-400">
            <Icon
              icon="mdi:magnify"
              class="w-5 h-5"
            />
          </span>
          <input
            :value="modelValue.q || ''"
            type="text"
            placeholder="Search campaigns"
            :disabled="loading"
            class="h-10 w-full rounded-xl border border-neutral-300 pl-10 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800"
            @input="set('q', ($event.target as HTMLInputElement).value)"
          >
          <span class="absolute right-2 top-1/2 -translate-y-1/2 text-neutral-400">⌘K</span>
        </div>
      </div>

      <div class="lg:col-span-3 grid grid-cols-2 gap-2">
        <select
          :value="modelValue.kind || ''"
          :disabled="loading"
          class="h-10 w-full rounded-xl border border-neutral-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800"
          @change="set('kind', ($event.target as HTMLSelectElement).value as any)"
        >
          <option value="">
            All kinds
          </option>
          <option value="fundraising">
            Fundraising
          </option>
          <option value="petition">
            Petition
          </option>
          <option value="volunteer">
            Volunteer
          </option>
          <option value="awareness">
            Awareness
          </option>
        </select>

        <select
          :value="modelValue.visibility || ''"
          :disabled="loading"
          class="h-10 w-full rounded-xl border border-neutral-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800"
          @change="set('visibility', ($event.target as HTMLSelectElement).value as any)"
        >
          <option value="">
            All visibility
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

      <div class="lg:col-span-3 grid grid-cols-2 gap-2">
        <select
          :value="modelValue.order_by || 'publish_at'"
          :disabled="loading"
          class="h-10 w-full rounded-xl border border-neutral-300 px-3 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:border-neutral-700 dark:bg-neutral-800"
          @change="set('order_by', ($event.target as HTMLSelectElement).value as any)"
        >
          <option value="publish_at">
            Publish date
          </option>
          <option value="created_at">
            Created date
          </option>
          <option value="ends_at">
            Ending soon
          </option>
          <option value="progress">
            Progress
          </option>
        </select>

        <button
          type="button"
          class="h-10 w-full inline-flex items-center justify-center gap-1 rounded-xl border px-3 text-sm dark:border-neutral-700"
          :disabled="loading"
          @click="toggleOrder"
        >
          <Icon
            :icon="modelValue.order_dir==='asc' ? 'mdi:arrow-up' : 'mdi:arrow-down'"
            class="w-4 h-4"
          />
          <span class="uppercase">{{ modelValue.order_dir }}</span>
        </button>
      </div>

      <div class="lg:col-span-2 flex items-center gap-2">
        <button
          type="button"
          class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-neutral-300 dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800"
          :disabled="loading"
          @click="apply"
        >
          <Icon
            icon="mdi:filter"
            class="w-4 h-4"
          />
          <span>Apply</span>
        </button>
        <button
          type="button"
          class="h-10 inline-flex items-center gap-2 rounded-xl px-3 text-sm border border-neutral-300 dark:border-neutral-700"
          :disabled="loading"
          @click="clear"
        >
          <Icon
            icon="mdi:close"
            class="w-4 h-4"
          />
          <span>Clear</span>
        </button>
      </div>
    </div>
  </div>
</template>
