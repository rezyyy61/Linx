<!-- src/modules/public/pages/HomePage.vue -->
<script setup lang="ts">
import { ref, shallowRef, onMounted, watch } from "vue"
import { useAuthStore } from "@/stores/auth/auth"
import HeroSection from "@/modules/public/components/HeroSection.vue"
import FeedSubnav from "@/modules/public/components/FeedSubnav.vue"

type TabKey =
    | "feed" | "parties" | "books" | "media"
    | "campaigns" | "profiles" | "events" | "announcements"

const auth = useAuthStore()
onMounted(() => { if (!auth.bootstrapDone) auth.bootstrap() })

const current = ref<TabKey>((new URLSearchParams(location.search).get("tab") as TabKey) || "feed")

const loaders: Record<TabKey, () => Promise<any>> = {
  feed:         () => import("@/modules/public/tabs/feed/FeedPage.vue"),
  parties:      () => import("@/modules/public/tabs/parties/PartiesPage.vue"),
  books:        () => import("@/modules/public/tabs/books/BookPage.vue"),
  media:        () => import("@/modules/public/tabs/media/MediaPage.vue"),
  campaigns:    () => import("@/modules/public/tabs/campaigns/CampaignsPage.vue"),
  profiles:     () => import("@/modules/public/tabs/profiles/ProfilePage.vue"),
  events:       () => import("@/modules/public/tabs/events/EventPage.vue"),
  announcements:() => import("@/modules/public/tabs/announcements/Page.vue"),
}

const CurrentComp = shallowRef<any>(null)

async function loadTab(key: TabKey) {
  const m = await loaders[key]()
  CurrentComp.value = m.default ?? m
}
loadTab(current.value)

watch(current, v => {
  history.replaceState(null, "", `?tab=${encodeURIComponent(v)}`)
  loadTab(v)
})
</script>

<template>
  <section class="grid gap-8">
    <HeroSection />
    <FeedSubnav v-model="current" />
    <component :is="CurrentComp" />
  </section>
</template>
