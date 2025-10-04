<script setup lang="ts">
import { ref } from "vue"
import { Icon } from "@iconify/vue"
import type { CampaignPublic } from "../api/types"

const props = defineProps<{ campaign: CampaignPublic }>()
const emit = defineEmits<{(e:"share"):void;(e:"saved",v:boolean):void}>()
const copied = ref(false)
const saved = ref(false)

async function copyLink() {
  try {
    const u = new URL(window.location.origin + `/campaigns/${props.campaign.slug}`)
    await navigator.clipboard.writeText(u.toString())
    copied.value = true
    setTimeout(() => (copied.value = false), 1200)
  } catch { /* empty */ }
}
function onShare() { emit("share") }
function toggleSave() { saved.value = !saved.value; emit("saved", saved.value) }
</script>

<template>
  <div class="flex items-center justify-between">
    <div class="inline-flex items-center gap-2">
      <button
        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800"
        @click="onShare"
      >
        <Icon
          icon="mdi:share-variant"
          class="w-4 h-4"
        />
        <span>Share</span>
      </button>
      <button
        class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800"
        @click="copyLink"
      >
        <Icon
          :icon="copied ? 'mdi:check' : 'mdi:link-variant'"
          class="w-4 h-4"
        />
        <span>{{ copied ? 'Copied' : 'Copy' }}</span>
      </button>
    </div>
    <button
      class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs dark:border-neutral-700 hover:bg-neutral-50 dark:hover:bg-neutral-800"
      :class="saved ? 'text-emerald-600' : ''"
      @click="toggleSave"
    >
      <Icon
        :icon="saved ? 'mdi:bookmark' : 'mdi:bookmark-outline'"
        class="w-4 h-4"
      />
      <span>{{ saved ? 'Saved' : 'Save' }}</span>
    </button>
  </div>
</template>
