<!-- src/modules/public/pages/HomePage.vue -->
<script setup lang="ts">
import { ref, shallowRef, onMounted, watch } from "vue";
import { useAuthStore } from "@/stores/auth/auth";
import FeedSubnav from "@/modules/public/components/FeedSubnav.vue";
import RightDock from "@/components/rightDock/RightDock.vue";

type TabKey =
  | "feed"
  | "parties"
  | "books"
  | "media"
  | "campaigns"
  | "profiles"
  | "events"
  | "announcements";

const auth = useAuthStore();
onMounted(() => {
  if (!auth.bootstrapDone) auth.bootstrap();
});

const current = ref<TabKey>(
  (new URLSearchParams(location.search).get("tab") as TabKey) || "feed",
);

const loaders: Record<TabKey, () => Promise<any>> = {
  feed: () => import("@/modules/public/tabs/feed/FeedPage.vue"),
  parties: () => import("@/modules/public/tabs/parties/PartiesPage.vue"),
  books: () => import("@/modules/public/tabs/books/BookPage.vue"),
  media: () => import("@/modules/public/tabs/media/MediaPage.vue"),
  campaigns: () => import("@/modules/public/tabs/campaigns/CampaignsPage.vue"),
  profiles: () => import("@/modules/public/tabs/profiles/ProfilePage.vue"),
  events: () => import("@/modules/public/tabs/events/EventPage.vue"),
  announcements: () =>
    import("@/modules/public/tabs/announcements/AnnouncementsPage.vue"),
};

const CurrentComp = shallowRef<any>(null);

async function loadTab(key: TabKey) {
  const m = await loaders[key]();
  CurrentComp.value = m.default ?? m;
}
loadTab(current.value);

watch(current, (v) => {
  history.replaceState(null, "", `?tab=${encodeURIComponent(v)}`);
  loadTab(v);
});
</script>

<template>
  <section
    class="min-h-dvh bg-gray-50 text-gray-900 dark:bg-zinc-900 dark:text-zinc-100"
    dir="auto"
  >
    <div class="mx-auto grid gap-8 px-4 sm:px-6 lg:px-8">
      <!--      <HeroSection />-->

      <FeedSubnav
        v-model="current"
        class="border-b mt-6 border-gray-200 dark:border-zinc-700"
      />

      <div class="rounded-xl border border-gray-200 bg-white p-0 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <component :is="CurrentComp" />
      </div>
    </div>

    <RightDock />
  </section>
</template>
