import {
  createRouter,
  createWebHistory,
  type RouteRecordRaw,
  type Router,
} from "vue-router";
import type { Pinia } from "pinia";
import publicRoutes from "./routes/public";
import dashboardRoutes from "./routes/dashboard";

const NotFound = () => import("@/views/NotFound.vue");

const routes: RouteRecordRaw[] = [
  publicRoutes,
  dashboardRoutes,
  { path: "/:pathMatch(.*)*", name: "not-found", component: NotFound },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
});

export function installGuards(pinia: Pinia, rtr: Router = router) {
  rtr.beforeEach(async (to) => {
    const { useAuthStore } = await import("@/stores/auth/auth");
    const auth = useAuthStore(pinia);

    if (!auth.bootstrapDone) {
      try {
        await auth.bootstrap();
      } catch {
        /* ignore */
      }
    }

    if (to.meta.requiresAuth && !auth.isAuthenticated) {
      return { name: "login", query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && auth.isAuthenticated) {
      return { name: "dashboard.home" };
    }
  });
}

export default router;
