<template>
  <BaseNotification
    :item="item"
    :theme="indigo"
    @main="open"
    @mark-read="$emit('mark-read',$event)"
    @remove="$emit('remove',$event)"
  />
</template>

<script setup lang="ts">
import BaseNotification from "./BaseNotification.vue"
import type { NotificationVM } from "@/modules/notifications/types"
const indigo = {
  accent: "bg-indigo-400/70 dark:bg-indigo-400/60",
  avatarBg: "bg-indigo-100 dark:bg-indigo-500/30 text-indigo-800 dark:text-indigo-100",
  badge: "border-indigo-300/50 dark:border-indigo-400/40 bg-indigo-100/70 dark:bg-indigo-500/20 text-indigo-800 dark:text-indigo-100",
  badgeSoft: "border-indigo-300/40 dark:border-indigo-400/30 bg-indigo-100/50 dark:bg-indigo-500/10 text-indigo-800 dark:text-indigo-100",
  icon: "mdi mdi-information-outline"
}
const props = defineProps<{ item: NotificationVM }>()
const emit = defineEmits<{ (e:"navigate", to: any):void; (e:"mark-read", id:number):void; (e:"remove", id:number):void }>()
function open(){ if (props.item.href) emit("navigate", props.item.href) }
</script>
