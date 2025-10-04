<script setup lang="ts">
import {defineAsyncComponent} from "vue";

const List = defineAsyncComponent(() => import("./CampaignsListPage.vue"))
</script>

<template>
  <section class="grid gap-6">
    <List />
  </section>
</template>
