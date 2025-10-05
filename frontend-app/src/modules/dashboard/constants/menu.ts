export type MenuItem = {
  label: string;
  icon: string;
  toName?: string;
  permission?: string;
};

export const dashboardMenu: MenuItem[] = [
  {
    label: "Home",
    icon: "solar:home-2-linear",
    toName: "dashboard.home",
  },
  {
    label: "Posts",
    icon: "solar:document-linear",
    toName: "dashboard.posts.list",
    permission: "posts.view",
  },
  {
    label: "Publications",
    icon: "mdi:newspaper-variant-outline",
    toName: "dashboard.publications.list",
    permission: "publications.view" },
  {
    label: "Events",
    icon: "solar:calendar-linear",
    toName: "dashboard.events.list",
    permission: "events.view",
  },
  {
    label: "Campaigns",
    icon: "solar:leaf-linear",
    toName: "dashboard.campaigns.list",
    permission: "campaigns.view",
  },
  {
    label: "Announcements",
    icon: "mdi:bullhorn-outline",
    toName: "dashboard.announcements.list",
    permission: "announcements.view",
  },
  {
    label: "Profile",
    icon: "solar:user-linear",
    toName: "dashboard.profile.edit",
  },
  {
    label: "Audience",
    icon: "mdi:account-group",
    toName: "dashboard.audience.followers",
    permission: "audience.view",
  },
];
