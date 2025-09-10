<template>
  <div
    class="flex items-center gap-2 px-2 py-2 rounded-xl bg-white/60 dark:bg-zinc-900/40 border border-zinc-200/60 dark:border-white/10 shadow-sm"
    role="toolbar"
    aria-label="Notification controls"
  >
    <div class="inline-flex rounded-lg overflow-hidden border border-zinc-300/70 dark:border-white/10 bg-white/70 dark:bg-zinc-900/60">
      <button
        :class="tab==='all' ? activeTab : baseTab"
        :aria-pressed="tab === 'all'"
        @click="emit('update:tab','all')"
      >
        All
      </button>
      <button
        :class="tab==='unread' ? activeTab : baseTab"
        :aria-pressed="tab === 'unread'"
        @click="emit('update:tab','unread')"
      >
        Unread
      </button>
    </div>
    <div class="ms-auto flex items-center gap-2">
      <select
        v-model="modelKind"
        class="px-3 py-2 rounded-lg border border-zinc-300/70 dark:border-white/10 bg-white/90 dark:bg-zinc-900/60 text-sm text-zinc-800 dark:text-zinc-100"
      >
        <option value="all">
          All types
        </option>
        <option
          v-for="k in kinds"
          :key="k"
          :value="k"
        >
          {{ k }}
        </option>
      </select>
      <button
        class="px-3 py-2 rounded-lg border border-zinc-300/70 dark:border-white/10 bg-white/90 dark:bg-zinc-900/60 hover:bg-white dark:hover:bg-zinc-900/70 text-sm"
        @click="emit('mark-all')"
      >
        Mark all
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue"
type Tab = "all" | "unread"
const props = defineProps<{ tab: Tab; kind: string; kinds: string[] }>()
const emit = defineEmits<{ (e:"update:tab", v:Tab):void; (e:"update:kind", v:string):void; (e:"mark-all"):void }>()
const modelKind = computed({ get: () => props.kind, set: v => emit("update:kind", v) })
const baseTab = "px-3 py-1 text-sm text-zinc-700 dark:text-zinc-100 hover:bg-white/90 dark:hover:bg-zinc-900/70"
const activeTab = baseTab + " bg-white/95 dark:bg-zinc-900/80 border border-indigo-500/30 dark:border-indigo-400/30 text-zinc-900 dark:text-white"
</script>
