<template>
  <div class="p-4 md:p-6 space-y-4">
    <h1 class="text-lg font-semibold text-slate-900 dark:text-slate-100">
      {{ t('campaign.edit.title') }}
    </h1>
    <div
      v-if="loading"
      class="text-slate-500 dark:text-slate-400"
    >
      {{ t('campaign.loading') }}
    </div>
    <div
      v-else-if="!campaign"
      class="text-red-600 dark:text-red-400"
    >
      {{ t('campaign.errors.generic') }}
    </div>
    <CampaignForm
      v-else
      :mode="'edit'"
      :initial="campaign"
      :saving="saving"
      @cancel="goBack"
      @submit="save"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import type { Campaign } from "@/modules/dashboard/pages/campaigns/types";
import { getCampaign, updateCampaign } from "@/modules/dashboard/pages/campaigns/api";
import CampaignForm from "@/modules/dashboard/pages/campaigns/components/CampaignForm.vue";

const { t } = useI18n();
const route = useRoute();
const router = useRouter();

const id = Number(route.params.id);
const campaign = ref<Campaign | null>(null);
const loading = ref(false);
const saving = ref(false);

async function fetchOne() {
  loading.value = true;
  try {
    campaign.value = await getCampaign(id);
  } finally {
    loading.value = false;
  }
}

async function save(payload: any) {
  saving.value = true;
  try {
    await updateCampaign(id, payload);
    await fetchOne();
  } finally {
    saving.value = false;
  }
}

function goBack() {
  router.push({ name: "campaigns.list" });
}

onMounted(fetchOne);
</script>
