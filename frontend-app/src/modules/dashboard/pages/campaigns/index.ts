import type { RouteRecordRaw } from "vue-router"

const CampaignsListPage = () => import("./pages/CampaignsListPage.vue")
const CampaignCreatePage = () => import("./pages/CampaignCreatePage.vue")
const CampaignEditPage = () => import("./pages/CampaignEditPage.vue")

const routes: RouteRecordRaw[] = [
  { path: "/dashboard/campaigns", name: "dashboard.campaigns.list", component: CampaignsListPage },
  { path: "/dashboard/campaigns/create", name: "dashboard.campaigns.create", component: CampaignCreatePage },
  { path: "/dashboard/campaigns/:id/edit", name: "dashboard.campaigns.edit", component: CampaignEditPage, props: true },
]

export default routes
