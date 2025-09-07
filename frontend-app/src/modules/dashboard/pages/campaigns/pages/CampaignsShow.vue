<template>
  <div class="p-4 md:p-6">
    <PageToolbar :title="$t('campaign.show.title')">
      <router-link
        to="/dashboard/campaigns"
        class="inline-flex items-center gap-1 rounded-xl px-3 py-2 text-sm border border-slate-200 bg-white hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:hover:bg-slate-800"
      >
        <Icon
          icon="mdi:arrow-left"
          class="w-4 h-4"
        />
        <span>{{ $t('campaign.actions.back') }}</span>
      </router-link>
    </PageToolbar>

    <div
      v-if="loading"
      class="text-slate-500 dark:text-slate-400"
    >
      {{ $t('campaign.loading') }}
    </div>
    <div
      v-else-if="!campaign"
      class="text-red-600 dark:text-red-400"
    >
      {{ $t('campaign.errors.generic') }}
    </div>
    <div v-else>
      <TabsNav
        :base="base"
        class="mb-6"
      />
      <RouterView v-slot="{ Component }">
        <component
          :is="Component"
          :campaign="campaign"
          :base="base"
        />
      </RouterView>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { Icon } from "@iconify/vue";
import PageToolbar from "../components/PageToolbar.vue";
import TabsNav from "@/modules/dashboard/pages/campaigns/components/TabsNav.vue";
import {Campaign} from "@/modules/dashboard/pages/campaigns/types";
import {getCampaign} from "@/modules/dashboard/pages/campaigns/api";


const route = useRoute();
const id = Number(route.params.id);
const campaign = ref<Campaign | null>(null);
const loading = ref(false);
const base = computed(() => `/dashboard/campaigns/${id}`);

async function fetchOne() {
  loading.value = true;
  try {
    campaign.value = await getCampaign(id);
  } finally {
    loading.value = false;
  }
}

onMounted(fetchOne);
</script>
