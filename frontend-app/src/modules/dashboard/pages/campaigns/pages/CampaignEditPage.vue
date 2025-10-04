<script setup lang="ts">
import { onMounted, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import CampaignForm from "../components/CampaignForm.vue"
import { useCampaignEditor } from "../composables/useCampaignEditor"

const route = useRoute()
const router = useRouter()
const id = Number(route.params.id)
const editor = useCampaignEditor()

onMounted(() => editor.load(id))

const loaded = computed(() => Boolean(editor.item.value?.id))

async function onSubmit(v:any){
  await editor.update(id, v)
  router.push({ name:"dashboard.campaigns.list" })
}
</script>

<template>
  <section class="max-w-5xl mx-auto px-4 py-6 space-y-6">
    <h1 class="text-2xl font-semibold text-emerald-600">
      Edit campaign
    </h1>
    <div class="rounded-2xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-5">
      <CampaignForm
        v-if="loaded"
        :key="editor.item.value!.id"
        :initial="editor.item.value"
        @submit="onSubmit"
        @cancel="router.back()"
      />
      <div
        v-else
        class="h-24 rounded-xl bg-gray-100 dark:bg-gray-800 animate-pulse"
      />
    </div>
  </section>
</template>
