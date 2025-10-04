import type { RouteRecordRaw } from "vue-router"

const routes: RouteRecordRaw[] = [
  {
    path: "/campaigns",
    name: "campaigns.list",
    component: () => import("./pages/CampaignsListPage.vue"),
  },
  {
    path: "/campaigns/:slug",
    name: "campaigns.details",
    props: true,
    component: () => import("./pages/CampaignDetailsPage.vue"),
  },
]

export default routes
