<template>
  <div class="flex gap-3">
    <img
      :src="item.author.avatarUrl"
      alt=""
      class="h-8 w-8 rounded-full object-cover"
    >
    <div class="min-w-0 flex-1">
      <div class="flex items-start justify-between">
        <div class="min-w-0">
          <div class="flex items-center gap-2">
            <span class="text-sm font-semibold">{{ item.author.name }}</span>
            <span class="text-xs text-zinc-500">@{{ item.author.username }}</span>
            <span class="text-xs text-zinc-400">· {{ time }}</span>
          </div>
        </div>
        <div class="relative">
          <button
            class="rounded-lg p-1 text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800"
            aria-label="More"
            @click="toggleMenu"
          >
            <svg
              viewBox="0 0 24 24"
              class="h-5 w-5"
              fill="currentColor"
            ><circle
              cx="12"
              cy="6"
              r="1.5"
            /><circle
              cx="12"
              cy="12"
              r="1.5"
            /><circle
              cx="12"
              cy="18"
              r="1.5"
            /></svg>
          </button>
          <div
            v-if="menuOpen"
            class="absolute right-0 z-10 mt-1 w-36 overflow-hidden rounded-lg border border-zinc-200 bg-white text-sm shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
          >
            <button
              v-if="isMine"
              class="block w-full px-3 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800"
              @click="startEdit"
            >
              Edit
            </button>
            <button
              v-if="isMine"
              class="block w-full px-3 py-2 text-left text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20"
              @click="$emit('delete', item)"
            >
              Delete
            </button>
            <button
              class="block w-full px-3 py-2 text-left hover:bg-zinc-100 dark:hover:bg-zinc-800"
              @click="$emit('report', item)"
            >
              Report
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="editing"
        class="mt-2"
      >
        <form
          class="flex gap-2"
          @submit.prevent="submitEdit"
        >
          <input
            v-model="text"
            type="text"
            class="flex-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-indigo-500 dark:border-zinc-800 dark:bg-zinc-900"
          >
          <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white hover:bg-indigo-700"
          >
            Save
          </button>
          <button
            type="button"
            class="rounded-lg px-3 py-2 text-sm hover:bg-zinc-100 dark:hover:bg-zinc-800"
            @click="cancelEdit"
          >
            Cancel
          </button>
        </form>
      </div>

      <div
        v-else
        class="mt-1 text-[15px] leading-relaxed text-zinc-800 dark:text-zinc-100"
      >
        <ClampText>
          <p class="whitespace-pre-wrap">
            {{ item.text }}
          </p>
        </ClampText>
      </div>

      <div class="mt-2 flex items-center gap-3 text-xs text-zinc-500">
        <button
          class="rounded px-1.5 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          @click="$emit('reply', item)"
        >
          Reply
        </button>
        <button
          class="rounded px-1.5 py-0.5 hover:bg-zinc-100 dark:hover:bg-zinc-800"
          @click="$emit('like', item)"
        >
          Like {{ item.likes || 0 }}
        </button>
      </div>

      <div
        v-if="replying"
        class="mt-3"
      >
        <slot name="composer" />
      </div>

      <div
        v-if="children && children.length"
        class="mt-3 space-y-3 rounded-lg border border-zinc-100 bg-zinc-50 p-3 dark:border-zinc-800 dark:bg-zinc-900/40"
      >
        <slot name="children" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Comment } from '@/modules/public/postCard/composables/useComments'
import { computed, ref } from 'vue'
import ClampText from './ClampText.vue'

const props = defineProps<{ item: Comment; isMine: boolean; replying?: boolean; children?: Comment[] }>()
const emit = defineEmits<{ (e: 'reply', c: Comment): void; (e: 'like', c: Comment): void; (e: 'delete', c: Comment): void; (e: 'report', c: Comment): void; (e: 'edit', payload: { id: string; text: string }): void }>()

const menuOpen = ref(false)
function toggleMenu() { menuOpen.value = !menuOpen.value }
const editing = ref(false)
const text = ref(props.item.text)
function startEdit() { editing.value = true; menuOpen.value = false }
function cancelEdit() { editing.value = false; text.value = props.item.text }
function submitEdit() { emit('edit', { id: props.item.id, text: text.value }); editing.value = false }

const time = computed(() => {
  const diff = Date.now() - new Date(props.item.createdAt).getTime()
  const s = Math.floor(diff / 1000)
  if (s < 60) return `${s}s`
  const m = Math.floor(s / 60)
  if (m < 60) return `${m}m`
  const h = Math.floor(m / 60)
  if (h < 24) return `${h}h`
  const d = Math.floor(h / 24)
  return `${d}d`
})
</script>
