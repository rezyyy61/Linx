// src/modules/dashboard/dashboard.routes.ts
import type { RouteRecordRaw } from 'vue-router'
import MyMembershipsListPage from "@/modules/dashboard/pages/audience/pages/members/MyMembershipsListPage.vue";

const DashboardLayout = () => import('@/modules/dashboard/layout/DashboardLayout.vue')
const DashboardHomePage = () => import('@/modules/dashboard/pages/DashboardHomePage.vue')

const PostsLayout = () => import('@/modules/dashboard/pages/posts/layout/PostsLayout.vue')
const PostsListPage = () => import('@/modules/dashboard/pages/posts/pages/ListPage.vue')
const PostsCreatePage = () => import('@/modules/dashboard/pages/posts/pages/CreatePost.vue')
const PostsEditPage = () => import('@/modules/dashboard/pages/posts/pages/EditPage.vue')
const PostsDetailPage = () => import('@/modules/dashboard/pages/posts/pages/DetailPage.vue')

const PublicationsListPage   = () => import('@/modules/dashboard/pages/publications/pages/PublicationsListPage.vue')
const PublicationsCreatePage = () => import('@/modules/dashboard/pages/publications/pages/PublicationsCreatePage.vue')
const PublicationsEditPage   = () => import('@/modules/dashboard/pages/publications/pages/PublicationsEditPage.vue')


const EventsListPage = () => import('@/modules/dashboard/pages/events/EventsListPage.vue')
const EventsCreatePage = () => import('@/modules/dashboard/pages/events/EventsCreatePage.vue')
const EventsEditPage = () => import('@/modules/dashboard/pages/events/EventsEditPage.vue')

const CampaignsListPage   = () => import('@/modules/dashboard/pages/campaigns/pages/CampaignsListPage.vue')
const CampaignsCreatePage = () => import('@/modules/dashboard/pages/campaigns/pages/CampaignCreatePage.vue')
const CampaignsEditPage   = () => import('@/modules/dashboard/pages/campaigns/pages/CampaignEditPage.vue')

const AnnouncementsListPage   = () => import('@/modules/dashboard/pages/announcements/pages/AnnouncementsListPage.vue')
const AnnouncementsCreatePage = () => import('@/modules/dashboard/pages/announcements/pages/AnnouncementsCreatePage.vue')
const AnnouncementsEditPage   = () => import('@/modules/dashboard/pages/announcements/pages/AnnouncementsEditPage.vue')

const AudienceLayout = () => import('@/modules/dashboard/pages/audience/AudienceLayout.vue')
const FollowersListPage = () => import('@/modules/dashboard/pages/audience/pages/friends/FriendsListPage.vue')
const MembersListPage   = () => import('@/modules/dashboard/pages/audience/pages/members/MembersListPage.vue')

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
        { path: '',        name: 'dashboard.posts.list',   component: PostsListPage,   meta: { permission: 'posts.view' } },
        { path: 'create',  name: 'dashboard.posts.create', component: PostsCreatePage,  meta: { permission: 'posts.create' } },
        { path: ':id/edit',name: 'dashboard.posts.edit',   component: PostsEditPage,   meta: { permission: 'posts.edit' } },
        { path: ':id',     name: 'dashboard.posts.detail', component: PostsDetailPage, meta: { permission: 'posts.view' } },
      ]
    },

    { path: 'publications',           name: 'dashboard.publications.list',   component: PublicationsListPage,   meta: { permission: 'publications.view' } },
    { path: 'publications/create',    name: 'dashboard.publications.create', component: PublicationsCreatePage, meta: { permission: 'publications.create' } },
    { path: 'publications/:id/edit',  name: 'dashboard.publications.edit',   component: PublicationsEditPage,   meta: { permission: 'publications.edit' } },


    { path: 'events',           name: 'dashboard.events.list',   component: EventsListPage,   meta: { permission: 'events.view' } },
    { path: 'events/create',    name: 'dashboard.events.create', component: EventsCreatePage, meta: { permission: 'events.create' } },
    { path: 'events/:id/edit',  name: 'dashboard.events.edit',   component: EventsEditPage,   meta: { permission: 'events.edit' } },

    { path: 'campaigns',           name: 'dashboard.campaigns.list',   component: CampaignsListPage,   meta: { permission: 'campaigns.view' } },
    { path: 'campaigns/create',    name: 'dashboard.campaigns.create', component: CampaignsCreatePage, meta: { permission: 'campaigns.create' } },
    { path: 'campaigns/:id/edit',  name: 'dashboard.campaigns.edit',   component: CampaignsEditPage,   meta: { permission: 'campaigns.edit' } },

    { path: 'announcements',           name: 'dashboard.announcements.list',   component: AnnouncementsListPage,   meta: { permission: 'announcements.view' } },
    { path: 'announcements/create',    name: 'dashboard.announcements.create', component: AnnouncementsCreatePage, meta: { permission: 'announcements.create' } },
    { path: 'announcements/:id/edit',  name: 'dashboard.announcements.edit',   component: AnnouncementsEditPage,   meta: { permission: 'announcements.edit' } },

    {
      path: 'audience',
      component: AudienceLayout,
      meta: { permission: 'audience.view' },
      children: [
        { path: '',           redirect: { name: 'dashboard.audience.followers' } },
        { path: 'followers',  name: 'dashboard.audience.followers',   component: FollowersListPage,    meta: { permission: 'audience.view' } },
        { path: 'members',    name: 'dashboard.audience.members',     component: MembersListPage,      meta: { permission: 'audience.view' } },
        { path: 'my-memberships', name: 'dashboard.audience.myMemberships', component: MyMembershipsListPage, meta: { permission: 'audience.view' } },
      ]
    },

    { path: 'profile', name: 'dashboard.profile.edit', component: ProfileEditPage }
  ]
} as RouteRecordRaw
