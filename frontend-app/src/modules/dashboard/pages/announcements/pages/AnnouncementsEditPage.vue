<template>
  <div
    v-if="loaded"
    class="p-4 md:p-6 space-y-4"
  >
    <h1 class="text-lg font-semibold">
      {{ t("announcement.edit.title") }}
    </h1>
    <AnnouncementForm
      :initial="item"
      :mode="'edit'"
      :saving="saving"
      @submit="onSubmit"
      @cancel="goBack"
    />
  </div>
  <div
    v-else
    class="p-4"
  >
    {{ t("announcement.loading") }}
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import AnnouncementForm from "../components/AnnouncementForm.vue";
import { getAnnouncement, updateAnnouncement } from "../api";
import type { Announcement } from "../types";

const { t } = useI18n();
const route = useRoute(); const router = useRouter();
const id = Number(route.params.id);

const item = ref<Announcement | null>(null);
const loaded = ref(false); const saving = ref(false);

async function load(){ item.value = await getAnnouncement(id); loaded.value = true; }
async function onSubmit(payload:any){ saving.value = true; try { await updateAnnouncement(id, payload); await load(); } finally { saving.value = false; } }
function goBack(){ router.back(); }

onMounted(load);
</script>
