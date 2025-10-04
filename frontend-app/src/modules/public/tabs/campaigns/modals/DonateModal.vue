<script setup lang="ts">
import { ref, computed, watch } from "vue"
import type { CampaignPublic } from "../api/types"

const props = defineProps<{ open: boolean; campaign: CampaignPublic | null }>()
defineEmits<{(e:"close"):void,(e:"submit",v:{amount:number;currency?:string|null;name?:string|null;email?:string|null}):void}>()

const amount = ref<string>("")
const currency = ref<string>("EUR")
const name = ref<string>("")
const email = ref<string>("")
const disabled = computed(() => !amount.value || Number(amount.value) <= 0)
watch(() => props.open, v => { if (!v) { amount.value=""; name.value=""; email.value="" } })
</script>

<template>
  <div
    v-if="open"
    class="fixed inset-0 z-[70]"
  >
    <div
      class="absolute inset-0 bg-black/50"
      @click="$emit('close')"
    />
    <div class="absolute inset-0 flex items-center justify-center p-4">
      <div class="w-full max-w-md rounded-2xl bg-white dark:bg-neutral-900 p-5 ring-1 ring-black/5 dark:ring-white/10">
        <h3 class="text-lg font-semibold">
          Donate
        </h3>
        <div class="mt-4 space-y-3">
          <input
            v-model="amount"
            type="number"
            min="1"
            placeholder="Amount"
            class="w-full rounded-xl border px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
          >
          <input
            v-model="currency"
            type="text"
            placeholder="Currency"
            class="w-full rounded-xl border px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
          >
          <input
            v-model="name"
            type="text"
            placeholder="Name (optional)"
            class="w-full rounded-xl border px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
          >
          <input
            v-model="email"
            type="email"
            placeholder="Email (optional)"
            class="w-full rounded-xl border px-3 py-2 dark:border-neutral-700 dark:bg-neutral-800"
          >
        </div>
        <div class="mt-5 flex items-center justify-end gap-2">
          <button
            class="rounded-xl border px-3 py-2 text-sm dark:border-neutral-700"
            @click="$emit('close')"
          >
            Cancel
          </button>
          <button
            class="rounded-xl bg-emerald-600 text-white px-3 py-2 text-sm disabled:opacity-50"
            :disabled="disabled"
            @click="$emit('submit',{amount:Number(amount),currency,name,email})"
          >
            Donate
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
