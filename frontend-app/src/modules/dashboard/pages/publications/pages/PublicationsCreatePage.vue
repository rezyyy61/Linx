<template>
  <div class="p-4 md:p-6 space-y-4">
    <h1 class="text-lg font-semibold">
      {{ t("publication.create.title") || "Create publication" }}
    </h1>
    <PublicationForm
      :mode="'create'"
      :saving="saving"
      @submitted="onSubmitted"
      @cancel="goBack"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { useI18n } from "vue-i18n";
import PublicationForm from "../components/PublicationForm.vue";
import { useRouter } from "vue-router";

const { t } = useI18n();
const router = useRouter();
const saving = ref(false);

function onSubmitted(_p: any) {
  router.push("/dashboard/publications");
}
function goBack(){ router.back(); }
</script>
