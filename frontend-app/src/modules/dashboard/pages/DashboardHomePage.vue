<!-- src/modules/dashboard/pages/DashboardHomePage.vue -->
<template>
  <div>
    <PageHeader
      title="Dashboard"
      description="Overview and quick actions"
    />
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
      <RouterLink
        :to="{ name:'dashboard.audience.followers' }"
        class="bg-white border rounded-xl p-4 block hover:bg-gray-50 transition"
      >
        <div class="text-sm text-gray-500">
          Audience
        </div>
        <div class="text-2xl font-semibold mt-1">
          —
        </div>
      </RouterLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import PageHeader from "../components/PageHeader.vue"
</script>
