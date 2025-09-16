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
            ref="edRef"
            v-model="html"
            variant="input"
            :placeholder="placeholder || 'Write a comment…'"
            @submit="sendPlain"
            @mention-query="onMentionQuery"
            @mention-close="closeMention"
          />

          <MentionsPopover
            :open="mOpen"
            :x="mX"
            :y="mY"
            :items="users"
            :active="mActive"
            :loading="us.loading"
            @pick="onPick"
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
import { computed, ref } from "vue";
import SmartEditor from "@/components/shared/SmartEditor.vue";
import AvatarUser from "@/components/shared/AvatarUser.vue";
import MentionsPopover from "@/modules/public/postCard/footer/comments/MentionsPopover.vue";
import { useUserSearchStore } from "@/stores/userSearch";

const props = defineProps<{
  loggedIn: boolean
  avatarUrl?: string | null
  avatarColor?: string | null
  displayName?: string | null
  initialText?: string
  replyingTo?: string | null
  placeholder?: string
  compact?: boolean
}>();

const emit = defineEmits<{
  (e: "submit", text: string): void
  (e: "cancel"): void
  (e: "login"): void
}>();

const html = ref(props.initialText || "");
const plainText = computed(() => html.value.replace(/<[^>]+>/g, "").trim());
const plainLen = computed(() => plainText.value.length);
const wrapperCls = computed(() => props.compact ? "rounded-xl border border-zinc-300 bg-white p-2 dark:border-zinc-800 dark:bg-zinc-900" : "rounded-xl border border-zinc-300 bg-white p-3 dark:border-zinc-800 dark:bg-zinc-900");
const avatar = computed(() => props.avatarUrl || null);
const color = computed(() => props.avatarColor || null);
const displayName = computed(() => props.displayName || "");
const replySlug = computed(() => props.replyingTo || "");

const edRef = ref<any>(null)
const us = useUserSearchStore()
const mOpen = ref(false)
const mX = ref(0)
const mY = ref(0)
const mActive = ref(0)
const users = computed(() => us.results)

async function onMentionQuery(p:{ q:string; x:number; y:number }) {
  const q = (p.q || '').trim()
  if (q.length < 1) {
    mOpen.value = false
    return
  }
  mX.value = p.x
  mY.value = p.y + 8
  mActive.value = 0
  mOpen.value = true
  await us.search(q)
}

function closeMention() {
  mOpen.value = false
}

function onPick(u:{ slug:string }) {
  requestAnimationFrame(() => {
    // خیلی مهم: اول فوکوس بده که selection برگرده
    edRef.value?.focus?.()
    edRef.value?.insertMention?.(u.slug)
    mOpen.value = false
  })
}


function sendPlain() {
  emitSubmit();
}

function emitSubmit() {
  if (!plainText.value) return;
  emit("submit", plainText.value);
  html.value = "";
}
</script>
