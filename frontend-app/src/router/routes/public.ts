import type { RouteRecordRaw } from "vue-router"
import eventsRoutes from "@/modules/public/tabs/events"
import announcementsRoutes from "@/modules/public/tabs/announcements"
import campaignsRoutes from "@/modules/public/tabs/campaigns"

const PublicLayout = () => import("@/layouts/PublicLayout.vue")
const HomePage = () => import("@/modules/public/pages/HomePage.vue")
const LoginPage = () => import("@/modules/auth/pages/LoginPage.vue")
const RegisterPage = () => import("@/modules/auth/pages/RegisterPage.vue")
const ForgotPasswordPage = () => import("@/modules/auth/pages/ForgotPassword.vue")
const ResetPasswordPage = () => import("@/modules/auth/pages/ResetPassword.vue")
const PostPage = () => import("@/modules/public/postCard/PostPage.vue")

export default {
  path: "/",
  component: PublicLayout,
  meta: { layout: "public" },
  children: [
    { path: "", name: "home", component: HomePage },

    {
      path: "auth/login",
      name: "login",
      component: LoginPage,
      meta: { guestOnly: true },
    },
    {
      path: "auth/register",
      name: "register",
      component: RegisterPage,
      meta: { guestOnly: true },
    },
    {
      path: "auth/forgot-password",
      name: "forgot-password",
      component: ForgotPasswordPage,
      meta: { guestOnly: true },
    },
    {
      path: "auth/reset-password",
      name: "reset-password",
      component: ResetPasswordPage,
      meta: { guestOnly: true },
    },

    {
      path: "p/:id",
      name: "post.show",
      component: PostPage,
      props: (route) => ({
        id: Number(route.params.id),
        comment: route.query.comment ? Number(route.query.comment) : null,
      }),
    },

    ...eventsRoutes,
    ...announcementsRoutes,
    ...campaignsRoutes,
  ],
} as RouteRecordRaw
