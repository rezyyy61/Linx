import type { RouteRecordRaw } from "vue-router"

const EventsListPage = () => import("./pages/EventsListPage.vue")
const EventDetailsPage = () => import("./pages/EventDetailsPage.vue")

const eventsRoutes: RouteRecordRaw[] = [
  {
    path: "events",
    name: "events.list",
    component: EventsListPage,
    meta: { public: true, title: "Events" },
  },
  {
    path: "events/:slug",
    name: "events.details",
    component: EventDetailsPage,
    meta: { public: true },
    props: true,
  },
]

export default eventsRoutes
