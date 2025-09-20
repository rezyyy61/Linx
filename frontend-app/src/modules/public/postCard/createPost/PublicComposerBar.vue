<!-- /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/public/postCard/createPost/PublicComposerBar.vue -->
<template>
  <div
    class="relative rounded-2xl border border-zinc-200/80 bg-white/80 shadow-sm backdrop-blur-sm transition
           hover:shadow-md dark:border-white/10 dark:bg-zinc-900/70"
  >
    <div class="pointer-events-none absolute inset-0 rounded-2xl ring-1 ring-inset ring-white/0 group-hover:ring-white/10" />
    <div class="p-3 md:p-4">
      <div class="flex items-start gap-3">
        <AvatarUser
          :src="avatarUrl"
          :name="displayName"
          :color="avatarColor"
          size="sm"
          rounded="full"
          ring
        />
        <button
          type="button"
          :disabled="disabled || loading"
          :aria-label="ariaLabel"
          class="group flex-1 rounded-2xl border border-zinc-200/80 bg-zinc-50/70 px-4 py-3 text-left text-zinc-600 shadow-inner transition
                     hover:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-60
                     dark:border-white/10 dark:bg-zinc-800/60 dark:text-zinc-300 dark:hover:bg-zinc-800"
          @click="openComposer"
        >
          <div class="flex items-center gap-3">
            <div class="flex-1 truncate">
              <span class="block truncate text-[15px] leading-6 text-zinc-500 group-hover:text-zinc-700 dark:text-zinc-400 dark:group-hover:text-zinc-200">
                {{ placeholder }}
              </span>
            </div>
            <div class="hidden items-center gap-2 sm:flex">
              <span
                v-if="loading"
                class="h-2 w-2 animate-pulse rounded-full bg-zinc-300 dark:bg-zinc-600"
              />
              <span
                v-else
                class="rounded-lg bg-zinc-100 px-2 py-1 text-xs text-zinc-500 transition group-hover:bg-zinc-200 dark:bg-zinc-700 dark:text-zinc-300 dark:group-hover:bg-zinc-600"
              >Compose</span>
            </div>
          </div>
        </button>
      </div>

      <div
        v-if="showActions"
        class="mt-3 flex items-center justify-between"
      >
        <div class="flex items-center gap-1.5">
          <button
            type="button"
            :disabled="disabled"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
            title="Add media"
            @click="onAction('media')"
          >
            <Icon
              icon="mdi:image-multiple-outline"
              class="h-5 w-5 text-indigo-600 dark:text-indigo-400"
            />
            <span class="hidden sm:inline">Media</span>
          </button>
          <button
            type="button"
            :disabled="disabled"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
            title="Add emoji"
            @click="onAction('emoji')"
          >
            <Icon
              icon="mdi:emoticon-outline"
              class="h-5 w-5 text-amber-500"
            />
            <span class="hidden sm:inline">Emoji</span>
          </button>
          <button
            type="button"
            :disabled="disabled"
            class="inline-flex items-center gap-2 rounded-xl px-3 py-2 text-sm text-zinc-700 transition hover:bg-zinc-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50 dark:text-zinc-200 dark:hover:bg-zinc-800"
            title="Start a poll"
            @click="onAction('poll')"
          >
            <Icon
              icon="mdi:poll"
              class="h-5 w-5 text-emerald-600"
            />
            <span class="hidden sm:inline">Poll</span>
          </button>
        </div>

        <div class="flex items-center gap-2">
          <span
            v-if="badge"
            class="rounded-lg bg-zinc-100 px-2 py-1 text-xs text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300"
          >{{ badge }}</span>
          <button
            type="button"
            :disabled="disabled || loading"
            class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-3 py-2 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
            @click="openComposer"
          >
            <Icon
              icon="mdi:pencil"
              class="h-5 w-5"
            />
            <span>Write</span>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import AvatarUser from '@/components/shared/AvatarUser.vue'
import { Icon } from '@iconify/vue'
import { computed } from 'vue'
import { useAuthStore } from '@/stores/auth/auth'
import { useProfileStore } from '@/stores/profile/profile'

const {
  placeholder = 'What’s on your mind?',
  disabled = false,
  loading = false,
  showActions = true,
  badge = '',
} = defineProps<{
  placeholder?: string
  disabled?: boolean
  loading?: boolean
  showActions?: boolean
  badge?: string
}>()

const emit = defineEmits<{
  (e: 'open'): void
  (e: 'action-media'): void
  (e: 'action-emoji'): void
  (e: 'action-poll'): void
}>()

const auth = useAuthStore()
const profile = useProfileStore()

const user = computed(() => auth.user)
const displayName = computed(() => profile.meLite?.name || user.value?.name || 'User')
const avatarUrl = computed<string>(() => profile.meLite?.avatar || '')
const avatarColor = computed<string | null>(() => profile.meLite?.avatar_color ?? null)

const ariaLabel = computed(() => `${displayName.value ? displayName.value + ', ' : ''}open composer`)

function openComposer() { emit('open') }
function onAction(kind: 'media' | 'emoji' | 'poll') {
  emit('open')
  if (kind === 'media') emit('action-media')
  else if (kind === 'emoji') emit('action-emoji')
  else emit('action-poll')
}
</script>

