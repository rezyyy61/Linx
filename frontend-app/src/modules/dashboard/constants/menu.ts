export type MenuItem = {
  label: string;
  icon: string;
  toName?: string;
  permission?: string;
};

export const dashboardMenu: MenuItem[] = [
  { label: "Home", icon: "solar:home-2-linear", toName: "dashboard.home" },
  { label: "Posts", icon: "solar:document-linear", toName: "posts.list", permission: "posts.view" },
  { label: "Events", icon: "solar:calendar-linear", toName: "events.list", permission: "events.view" },
  { label: "Campaigns", icon: "solar:leaf-linear", toName: "campaigns.list", permission: "campaigns.view" },
  { label: "Announcements", icon: "mdi:bullhorn-outline", toName: "announcements.list", permission: "announcements.view" },
  { label: "Profile", icon: "solar:user-linear", toName: "profile.edit" }
];
