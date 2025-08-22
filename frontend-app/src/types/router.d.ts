declare module "vue-router" {
  interface RouteMeta {
    layout?: "public" | "dashboard";
    requiresAuth?: boolean;
    guestOnly?: boolean;
    requiresVerified?: boolean;
  }
}
export {};
