<template>
  <BaseNotification
    :item="item"
    :theme="sky"
    @main="accept"
    @mark-read="$emit('mark-read',$event)"
    @remove="$emit('remove',$event)"
  >
    <template #title>
      {{ name }}
    </template>
    <template #body>
      <p class="mt-0.5 text-zinc-700 dark:text-zinc-200/90 truncate">
        {{ bodyText }}
      </p>
    </template>
    <template #actions>
      <button
        class="px-2.5 py-1.5 rounded-lg border text-[11px] border-emerald-300/40 dark:border-emerald-400/30 bg-emerald-50/70 dark:bg-emerald-400/10 text-emerald-700 dark:text-emerald-200 hover:translate-y-[-1px]"
        @click="accept"
      >
        Accept
      </button>
      <button
        class="px-2.5 py-1.5 rounded-lg border text-[11px] border-rose-300/40 dark:border-rose-400/30 bg-rose-50/70 dark:bg-rose-400/10 text-rose-700 dark:text-rose-200 hover:translate-y-[-1px]"
        @click="rejectQuick"
      >
        Reject
      </button>
    </template>
  </BaseNotification>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseNotification from "./BaseNotification.vue"
import type { NotificationVM } from "@/modules/notifications/types"

const sky = { accent:"bg-sky-400/70 dark:bg-sky-400/60", avatarBg:"bg-sky-100 dark:bg-sky-500/30 text-sky-800 dark:text-sky-100", badge:"border-sky-300/50 dark:border-sky-400/40 bg-sky-100/70 dark:bg-sky-500/20 text-sky-800 dark:text-sky-100", badgeSoft:"border-sky-300/40 dark:border-sky-400/30 bg-sky-100/50 dark:bg-sky-500/10 text-sky-800 dark:text-sky-100", icon:"mdi:email" }

const props = defineProps<{ item: NotificationVM }>()
const emit = defineEmits<{ (e:"open-modal", payload:{ name:string; data:any }):void; (e:"mark-read", id:number):void; (e:"remove", id:number):void }>()
const { t } = useI18n()

const name = computed(() => props.item.title || (props.item as any).handle || t("notification.user"))
const bodyText = computed(() => {
  const full = t("notification.member.invited.body", { name: name.value })
  const esc = name.value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
  return full.replace(new RegExp(`^${esc}\\s*`), "").trim()
})

const membershipId = computed(() => (props.item as any).membershipId || (props.item as any).entityId)
const ownerId = computed(() => (props.item as any).ownerId || null)

function accept(){
  emit("mark-read", props.item.id)
  if (membershipId.value) {
    emit("open-modal", {
      name: "members-invite-accept",
      data: {
        membershipId: Number(membershipId.value),
        ownerId: ownerId.value,
        actorName: name.value,
        originNotifId: props.item.id
      }
    })
  }
}
function rejectQuick(){ emit("mark-read", props.item.id) }
</script>
