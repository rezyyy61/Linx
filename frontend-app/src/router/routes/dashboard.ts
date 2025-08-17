import type { RouteRecordRaw } from 'vue-router'

const DashboardLayout = () => import('@/layouts/DashboardLayout.vue')
const DashboardHomePage = () => import('@/modules/dashboard/pages/DashboardHomePage.vue')

export default {
    path: '/dashboard',
    component: DashboardLayout,
    meta: { layout: 'dashboard', requiresAuth: true },
    children: [
        { path: '', name: 'dashboard.home', component: DashboardHomePage },
    ],
} as RouteRecordRaw
