// /home/rezyyy/PhpstormProjects/Linx/frontend-app/src/modules/public/tabs/publication/index.ts
import type { RouteRecordRaw } from "vue-router"

const PublicationsPage = () => import("./pages/PublicationsTabPage.vue")
const PublicationDetailsPage = () => import("./pages/PublicationDetailsPage.vue")

export default [
  {
    path: "publications",
    name: "public.publications.list",
    component: PublicationsPage,
  },
  {
    path: "publications/:slug",
    name: "public.publication.detail",
    component: PublicationDetailsPage,
    props: (route) => ({ slug: String(route.params.slug || "") }),
  },
] as RouteRecordRaw[]
