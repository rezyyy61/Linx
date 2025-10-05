<template>
  <section class="max-w-7xl mx-auto px-4 py-6 space-y-6 grid gap-6">
    <div class="">
      <div class="flex items-start justify-between gap-4">
        <div>
          <h1 class="text-2xl font-semibold text-rose-600 tracking-tight relative">
            <span class="relative z-10">Announcements</span>
            <span class="absolute left-0 bottom-0 w-12 h-1 bg-rose-500 rounded-full" />
          </h1>
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
