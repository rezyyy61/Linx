<template>
  <div
    v-if="loaded"
    class="p-4 md:p-6 space-y-4"
  >
    <h1 class="text-lg font-semibold">
      {{ t("publication.edit.title") || "Edit publication" }}
    </h1>
    <PublicationForm
      :initial="item"
      :mode="'edit'"
      :saving="saving"
      @submitted="onSubmitted"
      @cancel="goBack"
    />
  </div>
  <div
    v-else
    class="p-4"
  >
    {{ t("publication.loading") || "Loading..." }}
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import PublicationForm from "../components/PublicationForm.vue";
import { getPublication } from "../api";
import type { Publication } from "../types";

const { t } = useI18n();
const route = useRoute(); const router = useRouter();
const id = Number(route.params.id);

const item = ref<Publication | null>(null);
const loaded = ref(false); const saving = ref(false);

async function load(){ item.value = await getPublication(id); loaded.value = true; }

function onSubmitted(_p: any) {
  router.push("/dashboard/publications");
}
function goBack(){ router.back(); }

onMounted(load);
</script>
