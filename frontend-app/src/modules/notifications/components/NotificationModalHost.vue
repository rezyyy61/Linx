<template>
  <Teleport to="body">
    <component
      :is="cmp"
      v-if="open"
      v-bind="payload"
      @close="close"
      @accepted="onAccepted"
      @rejected="onRejected"
    />
  </Teleport>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent } from "vue"

const props = defineProps<{ name: string | null; payload?: any }>()
const emit = defineEmits<{ (e:"close"):void; (e:"accepted", id:number):void; (e:"rejected", id:number):void }>()

const registry: Record<string, any> = {
  "members-invite-accept": defineAsyncComponent(() => import("@/modules/dashboard/pages/audience/pages/members/components/InviteAcceptDialog.vue"))
}
const cmp = computed(() => (props.name ? registry[props.name] : null))
const open = computed(() => !!props.name)
function close(){ emit("close") }
function onAccepted(id:number){ emit("accepted", id) }
function onRejected(id:number){ emit("rejected", id) }
</script>
