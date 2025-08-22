import type { RouteRecordRaw } from "vue-router";

const PublicLayout = () => import("@/layouts/PublicLayout.vue");
const HomePage = () => import("@/modules/public/pages/HomePage.vue");
const LoginPage = () => import("@/modules/auth/pages/LoginPage.vue");
const RegisterPage = () => import("@/modules/auth/pages/RegisterPage.vue");
const ForgotPasswordPage = () =>
  import("@/modules/auth/pages/ForgotPassword.vue");
const ResetPasswordPage = () =>
  import("@/modules/auth/pages/ResetPassword.vue");

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
  ],
} as RouteRecordRaw;
