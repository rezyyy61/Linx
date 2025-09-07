<template>
  <div class="p-4 md:p-6 space-y-4">
    <h1 class="text-lg font-semibold">
      {{ t("announcement.create.title") }}
    </h1>
    <AnnouncementForm
      :mode="'create'"
      :saving="saving"
      @submit="onSubmit"
      @cancel="goBack"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import AnnouncementForm from "../components/AnnouncementForm.vue";
import { createAnnouncement } from "../api";
import { useRouter } from "vue-router";

const { t } = useI18n();
const router = useRouter();
const saving = ref(false);

async function onSubmit(payload: any){
  saving.value = true;
  try {
    const a = await createAnnouncement(payload);
    router.push({ name: "announcements.edit", params: { id: a.id } });
  } finally { saving.value = false; }
}
function goBack(){ router.back(); }
</script>
