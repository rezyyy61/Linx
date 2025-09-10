<template>
  <BaseNotification
    :item="item"
    :theme="rose"
    @main="openProfile"
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
  </BaseNotification>
</template>

<script setup lang="ts">
import { computed } from "vue"
import { useI18n } from "vue-i18n"
import BaseNotification from "./BaseNotification.vue"
import type { NotificationVM } from "@/modules/notifications/types"

const rose = { accent:"bg-rose-400/70 dark:bg-rose-400/60", avatarBg:"bg-rose-100 dark:bg-rose-500/30 text-rose-800 dark:text-rose-100", badge:"border-rose-300/50 dark:border-rose-400/40 bg-rose-100/70 dark:bg-rose-500/20 text-rose-800 dark:text-rose-100", badgeSoft:"border-rose-300/40 dark:border-rose-400/30 bg-rose-100/50 dark:bg-rose-500/10 text-rose-800 dark:text-rose-100", icon:"mdi:account" }

const props = defineProps<{ item: NotificationVM }>()
const emit = defineEmits<{ (e:"navigate", to:any):void; (e:"mark-read", id:number):void; (e:"remove", id:number):void }>()
const { t } = useI18n()

const name = computed(() => props.item.title || props.item.handle || t("notification.user"))
const bodyText = computed(() => {
  const full = t("notification.follow.accepted.body", { name: name.value })
  const esc = name.value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&")
  return full.replace(new RegExp(`^${esc}\\s*`), "").trim()
})

function openProfile(){ if (props.item.handle) emit("navigate", { name:"profile", params:{ username: props.item.handle } }) }
</script>
