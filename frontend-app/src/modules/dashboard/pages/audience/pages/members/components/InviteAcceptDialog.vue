<template>
  <div class="fixed inset-0 z-[70]">
    <div
      class="absolute inset-0 bg-black/50"
      @click="$emit('close')"
    />
    <div class="absolute inset-x-0 top-1/2 -translate-y-1/2 mx-auto w-[90%] max-w-md rounded-2xl border border-zinc-200/70 dark:border-white/10 bg-white dark:bg-zinc-900 p-4 shadow-xl">
      <div class="flex items-start gap-3">
        <div class="w-9 h-9 rounded-xl bg-sky-100 dark:bg-sky-500/20 flex items-center justify-center">
          <i class="mdi mdi-email text-sky-700 dark:text-sky-200" />
        </div>
        <div class="flex-1">
          <h3 class="font-semibold text-zinc-900 dark:text-zinc-50 truncate">
            Membership invitation
          </h3>
          <p
            v-if="actorName"
            class="text-sm text-zinc-600 dark:text-zinc-300 mt-0.5"
          >
            From {{ actorName }}
          </p>
        </div>
        <button
          class="p-1.5 rounded-md hover:bg-zinc-100 dark:hover:bg-white/10"
          @click="$emit('close')"
        >
          <i class="mdi mdi-close" />
        </button>
      </div>

      <form
        class="mt-4 space-y-3"
        @submit.prevent="onSubmit"
      >
        <div>
          <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Email</label>
          <input
            v-model="email"
            type="email"
            class="w-full px-3 py-2 rounded-lg border border-zinc-300/70 dark:border-white/10 bg-white dark:bg-zinc-950/60"
            placeholder="you@example.com"
          >
        </div>

        <div>
          <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Contact info</label>
          <input
            v-model="contact"
            type="text"
            class="w-full px-3 py-2 rounded-lg border border-zinc-300/70 dark:border-white/10 bg-white dark:bg-zinc-950/60"
            placeholder="+1 234 567 890"
          >
        </div>

        <div>
          <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">Note</label>
          <textarea
            v-model="note"
            rows="3"
            class="w-full px-3 py-2 rounded-lg border border-zinc-300/70 dark:border-white/10 bg-white dark:bg-zinc-950/60"
            placeholder="Anything the owner should know"
          />
        </div>

        <div class="flex items-center justify-between pt-2">
          <button
            type="button"
            class="px-3 py-2 rounded-lg border border-rose-300/40 dark:border-rose-400/30 text-rose-700 dark:text-rose-200 hover:bg-rose-50/60 dark:hover:bg-rose-400/10"
            :disabled="submitting"
            @click="onReject"
          >
            Reject
          </button>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="px-3 py-2 rounded-lg border border-zinc-300/40 dark:border-white/15 text-zinc-700 dark:text-zinc-200 hover:bg-zinc-50/60 dark:hover:bg-white/5"
              :disabled="submitting"
              @click="$emit('close')"
            >
              Cancel
            </button>
            <button
              type="submit"
              class="px-3 py-2 rounded-lg border border-emerald-300/40 dark:border-emerald-400/30 text-emerald-700 dark:text-emerald-200 hover:bg-emerald-50/60 dark:hover:bg-emerald-400/10"
              :disabled="submitting"
            >
              Accept
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue"
import { useMembers } from "../composables/useMembers"

const props = defineProps<{ membershipId: number; ownerId?: number | null; actorName?: string | null }>()
const emit = defineEmits<{ (e:"close"):void; (e:"accepted", id:number):void; (e:"rejected", id:number):void }>()

const email = ref("")
const contact = ref("")
const note = ref("")
const submitting = ref(false)

onMounted(async () => {
  try {
    const mod = await import("@/stores/auth/auth")
    const auth = (mod as any).useAuthStore?.()
    const userEmail = auth?.user?.email || ""
    if (!email.value && userEmail) email.value = userEmail
  } catch { /* empty */ }
})

async function saveExtrasIfPossible() {
  const payload: any = { email: email.value || null, contact_info: contact.value || null }
  if (note.value) payload.meta = { note: note.value }
  if (!payload.email && !payload.contact_info && !payload.meta) return
  try {
    const mod = await import("../../../api/members")
    if (typeof (mod as any).updateMembership === "function") {
      await (mod as any).updateMembership(props.membershipId, payload)
    }
  } catch { /* empty */ }
}

async function onSubmit(){
  submitting.value = true
  try {
    await saveExtrasIfPossible()
    if (props.ownerId) {
      const hook = useMembers(Number(props.ownerId))
      await hook.accept(props.membershipId)
    } else {
      const { acceptMembership } = await import("../../../api/members")
      await acceptMembership(props.membershipId)
    }
    emit("accepted", props.membershipId)
    emit("close")
  } finally {
    submitting.value = false
  }
}

async function onReject(){
  submitting.value = true
  try {
    const reason = note.value || undefined
    if (props.ownerId) {
      const hook = useMembers(Number(props.ownerId))
      await hook.reject(props.membershipId, reason)
    } else {
      const { rejectMembership } = await import("../../../api/members")
      await rejectMembership(props.membershipId, reason)
    }
    emit("rejected", props.membershipId)
    emit("close")
  } finally {
    submitting.value = false
  }
}
</script>
