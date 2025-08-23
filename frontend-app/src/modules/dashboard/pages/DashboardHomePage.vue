<template>
  <div>
    <PageHeader
      title="Dashboard"
      description="Overview and quick actions"
    />
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white border rounded-xl p-4">
        <div class="text-sm text-gray-500">
          Posts
        </div>
        <div class="text-2xl font-semibold mt-1">
          —
        </div>
      </div>
      <div class="bg-white border rounded-xl p-4">
        <div class="text-sm text-gray-500">
          Events
        </div>
        <div class="text-2xl font-semibold mt-1">
          —
        </div>
      </div>
      <div class="bg-white border rounded-xl p-4">
        <div class="text-sm text-gray-500">
          Campaigns
        </div>
        <div class="text-2xl font-semibold mt-1">
          —
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../components/PageHeader.vue";
</script>
