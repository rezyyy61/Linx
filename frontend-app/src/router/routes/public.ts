import type { RouteRecordRaw } from 'vue-router'

const PublicLayout  = () => import('@/layouts/PublicLayout.vue')
const HomePage      = () => import('@/modules/public/pages/HomePage.vue')
const LoginPage     = () => import('@/modules/auth/pages/LoginPage.vue')
const RegisterPage  = () => import('@/modules/auth/pages/RegisterPage.vue')

export default {
    path: '/',
    component: PublicLayout,
    meta: { layout: 'public' },
    children: [
        { path: '',               name: 'home',     component: HomePage },
        { path: 'auth/login',    name: 'login',    component: LoginPage,    meta: { guestOnly: true } },
        { path: 'auth/register', name: 'register', component: RegisterPage, meta: { guestOnly: true } },
    ],
} as RouteRecordRaw
