<template>
  <section class="grid gap-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 md:p-8 shadow-md dark:border-zinc-800 dark:bg-zinc-900">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h2 class="text-xl md:text-2xl font-semibold text-gray-900 dark:text-white">
            Announcements
          </h2>
          <p class="mt-1 text-sm md:text-base text-gray-600 dark:text-zinc-300">
            Latest public updates and statements
          </p>
        </div>
      </div>

      <AnnouncementFilters
        class="mt-6"
        :model-value="filters"
        :loading="loading"
        @update:model-value="v => { filters = v; reload() }"
      />

      <div class="mt-4">
        <AnnouncementList
          :items="items"
          :loading="loading"
          :has-more="hasMore"
          @load-more="loadMore"
        />
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, reactive, ref, watch } from "vue";
import AnnouncementFilters from "../components/AnnouncementFilters.vue";
import AnnouncementList from "../components/AnnouncementList.vue";
import { usePublicAnnouncements, type AnnouncementPublicFilters } from "../composables/usePublicAnnouncements";

const { list, fetchPage, reset, hasMore, loading } = usePublicAnnouncements();

const items = ref(list.value);
watch(list, v => (items.value = v));

let filters = reactive<AnnouncementPublicFilters>({
  q: "",
  onlyPinned: false,
  sort: "latest"
});

function reload(){
  reset(filters);
  fetchPage();
}

function loadMore(){
  if (loading.value || !hasMore.value) return;
  fetchPage();
}

onMounted(() => {
  reset(filters);
  fetchPage();
});
</script>
