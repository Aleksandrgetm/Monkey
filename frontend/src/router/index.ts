import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../stores/auth";
const router = createRouter({
  history: createWebHistory(),
  scrollBehavior(to, _from, saved) {
    if (saved) return saved;
    if (to.hash)
      return {
        el: to.hash,
        top: 95,
        behavior: window.matchMedia("(prefers-reduced-motion: reduce)").matches
          ? "instant"
          : "smooth",
      };
    return { top: 0 };
  },
  routes: [
    { path: "/", component: () => import("../views/HomeView.vue") },
    ...["login", "register", "forgot-password", "reset-password"].map(
      (mode) => ({
        path: `/${mode}`,
        component: () => import("../views/auth/AuthView.vue"),
        props: { mode },
        meta: { guest: true },
      }),
    ),
    {
      path: "/app",
      component: () => import("../layouts/AppLayout.vue"),
      meta: { auth: true },
      children: [
        {
          path: "",
          component: () => import("../views/app/DashboardView.vue"),
          meta: { title: "Pārskats" },
        },
        ...["documents", "receipts", "warranties", "search"].map((section) => ({
          path: section,
          component: () => import("../views/app/DocumentsView.vue"),
          meta: {
            section,
            title: {
              documents: "Mani dokumenti",
              receipts: "Čeki",
              warranties: "Garantijas",
              search: "Meklēšana",
            }[section],
          },
        })),
        {
          path: "documents/new",
          component: () => import("../views/app/DocumentEditView.vue"),
          meta: { title: "Pievienot dokumentu" },
        },
        {
          path: "documents/:id/edit",
          component: () => import("../views/app/DocumentEditView.vue"),
          meta: { title: "Rediģēt dokumentu" },
        },
        {
          path: "documents/:id",
          component: () => import("../views/app/DocumentDetailView.vue"),
          meta: { title: "Dokuments" },
        },
        {
          path: "products",
          component: () => import("../views/app/ProductsView.vue"),
          meta: { title: "Produkti" },
        },
        {
          path: "products/new",
          component: () => import("../views/app/ProductEditView.vue"),
          meta: { title: "Pievienot produktu" },
        },
        {
          path: "products/:id/edit",
          component: () => import("../views/app/ProductEditView.vue"),
          meta: { title: "Rediģēt produktu" },
        },
        {
          path: "products/:id",
          component: () => import("../views/app/ProductDetailView.vue"),
          meta: { title: "Produkts" },
        },
        {
          path: "categories",
          component: () => import("../views/app/CategoriesView.vue"),
          meta: { title: "Kategorijas" },
        },
        {
          path: "notifications",
          component: () => import("../views/app/NotificationsView.vue"),
          meta: { title: "Atgādinājumi" },
        },
        {
          path: "settings",
          component: () => import("../views/app/SettingsView.vue"),
          meta: { title: "Iestatījumi" },
        },
      ],
    },
    {
      path: "/admin",
      component: () => import("../layouts/AppLayout.vue"),
      meta: { auth: true, admin: true },
      children: [
        {
          path: "",
          component: () => import("../views/admin/AdminOverview.vue"),
          meta: { title: "Sistēmas pārskats" },
        },
        {
          path: "users",
          component: () => import("../views/admin/AdminUsers.vue"),
          meta: { title: "Lietotāji" },
        },
        {
          path: "documents",
          component: () => import("../views/admin/AdminDocuments.vue"),
          meta: { title: "Dokumentu pārvaldība" },
        },
        {
          path: "settings",
          component: () => import("../views/admin/AdminSettings.vue"),
          meta: { title: "Sistēmas iestatījumi" },
        },
      ],
    },
    {
      path: "/connection-error",
      component: () => import("../views/ConnectionError.vue"),
    },
    {
      path: "/:pathMatch(.*)*",
      component: () => import("../views/NotFoundView.vue"),
    },
  ],
});
router.beforeEach(async (to) => {
  if (!to.meta.auth && !to.meta.guest) return;
  const auth = useAuthStore();
  try {
    await auth.fetchUser(true);
  } catch {
    return { path: "/connection-error", query: { next: to.fullPath } };
  }
  if (to.meta.auth && !auth.user)
    return { path: "/login", query: { redirect: to.fullPath } };
  if (to.meta.admin && auth.user?.role !== 1) return "/app";
  if (to.meta.guest && auth.user) return "/app";
});
router.afterEach((to) => {
  document.title = `${to.meta.title || "Tavi dokumenti. Vienmēr ar tevi."} · Scan & Save`;
});
export default router;
