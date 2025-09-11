<template>
  <form
    class="flex items-start gap-3"
    @submit.prevent="submit"
  >
    <img
      :src="avatar"
      alt=""
      class="h-8 w-8 rounded-full object-cover"
    >
    <div class="flex-1">
      <div
        v-if="replyingTo"
        class="mb-2 inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
      >
        Replying to <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ replyingTo }}</span>
        <button
          type="button"
          class="ml-1 rounded-full p-1 hover:bg-zinc-200 dark:hover:bg-zinc-700"
          @click="$emit('cancel')"
        >
          ✕
        </button>
      </div>
      <div :class="wrapperCls">
        <textarea
          v-model="text"
          :rows="compact ? 2 : 3"
          :placeholder="placeholder || 'Write a comment…'"
          class="w-full resize-none bg-transparent text-sm outline-none placeholder:text-zinc-400"
          @keydown.enter.exact.prevent="submit"
        />
        <div class="flex items-center justify-end gap-2">
          <button
            type="submit"
            class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            :disabled="!text.trim()"
          >
            Comment
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watchEffect, computed } from 'vue'

const props = defineProps<{
  avatar?: string
  initialText?: string
  placeholder?: string
  compact?: boolean
  replyingTo?: string
}>()

const emit = defineEmits<{ (e: 'submit', text: string): void; (e: 'cancel'): void }>()

const text = ref('')
watchEffect(() => { text.value = props.initialText || '' })

const avatar = props.avatar || 'https://i.pravatar.cc/80?u=me'
const compact = computed(() => !!props.compact)
const wrapperCls = computed(() =>
  compact.value
    ? 'rounded-xl border border-zinc-300 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900'
    : 'rounded-xl border border-zinc-300 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900'
)

function submit() {
  const v = text.value.trim()
  if (!v) return
  emit('submit', v)
  text.value = ''
}
</script>
