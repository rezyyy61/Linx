import type { RouteRecordRaw } from "vue-router"

const AnnouncementDetailsPage = () =>
  import("@/modules/public/tabs/announcements/pages/AnnouncementDetailsPage.vue")

export default [
  {
    path: "announcements/:slug",
    name: "announcements.details",
    component: AnnouncementDetailsPage,
    props: true,
  },
] as RouteRecordRaw[]
