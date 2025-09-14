<template>
  <div>
    <div
      v-if="!loggedIn"
      class="flex items-center justify-between gap-3 rounded-xl border border-zinc-300 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900"
    >
      <div class="flex items-center gap-3">
        <AvatarUser
          size="sm"
          fallback="icon"
          icon="mdi:account"
          color="#d4d4d8"
        />
        <div class="text-sm text-zinc-600 dark:text-zinc-300">
          Login is required to comment
        </div>
      </div>
      <button
        type="button"
        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
        @click="$emit('login')"
      >
        Login to comment
      </button>
    </div>

    <form
      v-else
      class="flex items-start gap-3"
      @submit.prevent="emitSubmit"
    >
      <AvatarUser
        :src="avatar"
        :color="color"
        :name="displayName"
        size="sm"
        rounded="full"
        ring
        zoomable
      />

      <div class="flex-1">
        <div
          v-if="replyingTo"
          class="mb-2 inline-flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1 text-xs text-zinc-700 dark:bg-zinc-800 dark:text-zinc-200"
        >
          Replying to
          <span class="font-medium text-indigo-600 dark:text-indigo-400">{{ replySlug }}</span>
          <button
            type="button"
            class="ml-1 rounded-full p-1 hover:bg-zinc-200 dark:hover:bg-zinc-700"
            @click="$emit('cancel')"
          >
            ✕
          </button>
        </div>

        <div :class="wrapperCls">
          <SmartEditor
            v-model="html"
            variant="input"
            :placeholder="placeholder || 'Write a comment…'"
            @submit="sendPlain"
          />
          <div class="mt-2 flex items-center justify-between gap-2">
            <div class="text-xs text-zinc-400">
              {{ plainLen }}/5000
            </div>
            <button
              type="submit"
              class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
              :disabled="!plainText.trim()"
            >
              Comment
            </button>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watchEffect } from 'vue'
import { normalizeNewlines } from '@/modules/public/postCard/utils/text'
import SmartEditor from '@/components/shared/SmartEditor.vue'
import AvatarUser from '@/components/shared/AvatarUser.vue'
import { useProfileStore } from "@/stores/profile/profile"
import { useAuthStore } from "@/stores/auth/auth"

const props = defineProps<{
  loggedIn: boolean
  avatarUrl?: string | null
  avatarColor?: string | null
  name?: string | null
  initialText?: string
  placeholder?: string
  compact?: boolean
  replyingTo?: string
}>()

const emit = defineEmits<{
  (e: 'submit', text: string): void
  (e: 'cancel'): void
  (e: 'login'): void
}>()

const auth = useAuthStore()
const profile = useProfileStore()
const user = computed(() => auth.user)

const displayName = computed(() => profile.meLite?.name || user.value?.name || "User")
const avatar = computed<string>(() => profile.meLite?.avatar || "")
const color = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

const replySlug = computed(() => {
  const s = (props.replyingTo || '').trim()
  if (!s) return ''
  return s.startsWith('@') ? s : '@' + s
})

const html = ref('<p></p>')
watchEffect(() => {
  if (replySlug.value) {
    html.value = '<p></p>'
  } else {
    const safe = (props.initialText || '')
      .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    html.value = safe ? `<p>${safe}</p>` : '<p></p>'
  }
})

function htmlToPlain(input: string): string {
  if (!input) return ''
  return input
    .replace(/<\/p>\s*<p>/gi, '\n\n')
    .replace(/<br\s*\/?>/gi, '\n')
    .replace(/<\/(div|li|h[1-6])>/gi, '\n')
    .replace(/<li>/gi, '- ')
    .replace(/<[^>]+>/g, '')
    .trim()
}

const plainText = computed(() => htmlToPlain(html.value))
const plainLen = computed(() => plainText.value.length)

const wrapperCls = computed(() =>
  (props.compact ?? true)
    ? 'rounded-xl border border-zinc-300 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900'
    : 'rounded-xl border border-zinc-300 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900'
)

function buildFinalText(typed: string) {
  const base = normalizeNewlines(typed.slice(0, 5000), 2, 40)
  if (!replySlug.value) return base
  const withoutDup = base.replace(new RegExp(`^\\s*@?${replySlug.value.replace(/^@/,'')}(\\b|\\s)`, 'i'), '').trim()
  const out = `${replySlug.value} ${withoutDup}`.trim()
  return out.slice(0, 5000)
}

function emitSubmit() {
  const typed = plainText.value
  if (!typed.trim()) return
  const finalText = buildFinalText(typed)
  if (!finalText.trim()) return
  emit('submit', finalText)
  html.value = '<p></p>'
}

function sendPlain(textPlain: string) {
  if (!textPlain.trim()) return
  const finalText = buildFinalText(textPlain)
  if (!finalText.trim()) return
  emit('submit', finalText)
  html.value = '<p></p>'
}
</script>
