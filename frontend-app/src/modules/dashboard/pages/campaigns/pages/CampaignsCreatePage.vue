<template>
  <div class="max-w-7xl mx-auto px-4 py-6 space-y-6">
    <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
      {{ t('campaign.create.title') }}
    </h1>
    <CampaignForm
      :mode="'create'"
      :saving="saving"
      @cancel="goList"
      @submit="submit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { useRouter } from "vue-router";
import { createCampaign } from "@/modules/dashboard/pages/campaigns/api";
import CampaignForm from "@/modules/dashboard/pages/campaigns/components/CampaignForm.vue";

const { t } = useI18n();
const router = useRouter();
const saving = ref(false);

async function submit(payload: any) {
  saving.value = true;
  try {
    const created = await createCampaign({ status: payload.status, title: payload.title, ...payload });
    router.push({ name: "campaigns.edit", params: { id: created.id } });
  } finally {
    saving.value = false;
  }
}

function goList() {
  router.push({ name: "campaigns.list" });
}
</script>
