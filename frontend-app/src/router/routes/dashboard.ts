import type { RouteRecordRaw } from 'vue-router'

const DashboardLayout = () => import('@/modules/dashboard/layout/DashboardLayout.vue')
const DashboardHomePage = () => import('@/modules/dashboard/pages/DashboardHomePage.vue')

const PostsLayout = () => import('@/modules/dashboard/pages/posts/layout/PostsLayout.vue')
const PostsListPage = () => import('@/modules/dashboard/pages/posts/pages/ListPage.vue')
const PostsCreatePage = () => import('@/modules/dashboard/pages/posts/pages/CreatePost.vue')
const PostsEditPage = () => import('@/modules/dashboard/pages/posts/pages/EditPage.vue')
const PostsDetailPage = () => import('@/modules/dashboard/pages/posts/pages/DetailPage.vue')

const EventsListPage = () => import('@/modules/dashboard/pages/events/EventsListPage.vue')
const EventsCreatePage = () => import('@/modules/dashboard/pages/events/EventsCreatePage.vue')
const EventsEditPage = () => import('@/modules/dashboard/pages/events/EventsEditPage.vue')

const CampaignsListPage = () => import('@/modules/dashboard/pages/campaigns/CampaignsListPage.vue')
const CampaignsCreatePage = () => import('@/modules/dashboard/pages/campaigns/CampaignsCreatePage.vue')
const CampaignsEditPage = () => import('@/modules/dashboard/pages/campaigns/CampaignsEditPage.vue')

const AnnouncementsListPage = () => import('@/modules/dashboard/pages/announcements/AnnouncementsListPage.vue')
const AnnouncementsCreatePage = () => import('@/modules/dashboard/pages/announcements/AnnouncementsCreatePage.vue')
const AnnouncementsEditPage = () => import('@/modules/dashboard/pages/announcements/AnnouncementsEditPage.vue')

const ProfileEditPage = () => import('@/modules/dashboard/pages/profile/ProfileEditPage.vue')

export default {
  path: '/dashboard',
  component: DashboardLayout,
  meta: { layout: 'dashboard', requiresAuth: true },
  children: [
    { path: '', name: 'dashboard.home', component: DashboardHomePage },

    {
      path: 'posts',
      component: PostsLayout,
      meta: { permission: 'posts.view' },
      children: [
        { path: '', name: 'posts.list', component: PostsListPage, meta: { permission: 'posts.view' } },
        { path: 'create', name: 'posts.create', component: PostsCreatePage, meta: { permission: 'posts.create' } },
        { path: ':id/edit', name: 'posts.edit', component: PostsEditPage, meta: { permission: 'posts.edit' } },
        { path: ':id', name: 'posts.detail', component: PostsDetailPage, meta: { permission: 'posts.view' } }
      ]
    },

    { path: 'events', name: 'events.list', component: EventsListPage, meta: { permission: 'events.view' } },
    { path: 'events/create', name: 'events.create', component: EventsCreatePage, meta: { permission: 'events.create' } },
    { path: 'events/:id/edit', name: 'events.edit', component: EventsEditPage, meta: { permission: 'events.edit' } },

    { path: 'campaigns', name: 'campaigns.list', component: CampaignsListPage, meta: { permission: 'campaigns.view' } },
    { path: 'campaigns/create', name: 'campaigns.create', component: CampaignsCreatePage, meta: { permission: 'campaigns.create' } },
    { path: 'campaigns/:id/edit', name: 'campaigns.edit', component: CampaignsEditPage, meta: { permission: 'campaigns.edit' } },

    { path: 'announcements', name: 'announcements.list', component: AnnouncementsListPage, meta: { permission: 'announcements.view' } },
    { path: 'announcements/create', name: 'announcements.create', component: AnnouncementsCreatePage, meta: { permission: 'announcements.create' } },
    { path: 'announcements/:id/edit', name: 'announcements.edit', component: AnnouncementsEditPage, meta: { permission: 'announcements.edit' } },

    { path: 'profile', name: 'profile.edit', component: ProfileEditPage }
  ]
} as RouteRecordRaw
