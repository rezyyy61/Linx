<!-- /src/modules/dashboard/pages/campaigns/pages/CampaignCreatePage.vue -->
<script setup lang="ts">
import { useRouter } from "vue-router"
import CampaignForm from "../components/CampaignForm.vue"
import { useCampaignEditor } from "../composables/useCampaignEditor"
import { useAuthStore } from "@/stores/auth/auth"

const router = useRouter()
const editor = useCampaignEditor()
const userStore = useAuthStore()

async function onSubmit(v: any) {
  const payload = { ...v, owner_id: userStore.user?.id }
  await editor.create(payload)
  router.push({ name: "dashboard.campaigns.list" })
}

function onCancel() {
  router.back()
}
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-6 space-y-6">
    <h1 class="text-2xl font-semibold text-emerald-600">
      Create campaign
    </h1>
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
      <CampaignForm
        @submit="onSubmit"
        @cancel="onCancel"
      />
    </div>
  </section>
</template>
