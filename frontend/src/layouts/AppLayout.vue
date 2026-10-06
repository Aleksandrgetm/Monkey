<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../stores/auth";
import { extractError } from "../services/api";
import HomeBrand from "../components/home/HomeBrand.vue";
import HomeIcon from "../components/home/HomeIcon.vue";
const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const mobileOpen = ref(false);
const sidebar = ref<HTMLElement | null>(null);
const menuButton = ref<HTMLButtonElement | null>(null);
watch(mobileOpen, async (open) => {
  await nextTick();
  if (open) sidebar.value?.querySelector<HTMLElement>("a, button")?.focus();
  else menuButton.value?.focus();
});
function menuKey(event: KeyboardEvent) {
  if (!mobileOpen.value) return;
  if (event.key === "Escape") {
    event.preventDefault();
    mobileOpen.value = false;
    return;
  }
  if (event.key !== "Tab") return;
  const items = Array.from(
    sidebar.value?.querySelectorAll<HTMLElement>("a, button:not([disabled])") ||
      [],
  );
  const first = items[0];
  const last = items[items.length - 1];
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last?.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first?.focus();
  }
}
const error = ref("");
const busy = ref(false);
const isAdmin = computed(() => !!route.meta.admin);
const links = computed(() =>
  isAdmin.value
    ? [
        ["/admin", "grid", "Pārskats"],
        ["/admin/users", "shield", "Lietotāji"],
        ["/admin/documents", "file", "Dokumenti"],
        ["/admin/settings", "lock", "Iestatījumi"],
        ["/app", "arrow", "Mana darba vieta"],
      ]
    : [
        ["/app", "grid", "Pārskats"],
        ["/app/documents", "folder", "Mani dokumenti"],
        ["/app/receipts", "scan", "Čeki"],
        ["/app/warranties", "shield", "Garantijas"],
        ["/app/products", "laptop", "Produkti"],
        ["/app/notifications", "bell", "Atgādinājumi"],
        ["/app/search", "search", "Meklēšana"],
        ["/app/categories", "folder", "Kategorijas"],
        ["/app/settings", "lock", "Iestatījumi"],
      ],
);
function sessionEnded() {
  auth.clear();
  router.replace({ path: "/login", query: { redirect: route.fullPath } });
}
async function logout() {
  busy.value = true;
  error.value = "";
  try {
    await auth.logout();
    await router.push("/login");
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = false;
  }
}
watch(
  () => route.fullPath,
  () => {
    mobileOpen.value = false;
    error.value = "";
  },
);
onMounted(() => {
  window.addEventListener("scan:unauthenticated", sessionEnded);
  window.addEventListener("keydown", menuKey);
});
onUnmounted(() => {
  window.removeEventListener("scan:unauthenticated", sessionEnded);
  window.removeEventListener("keydown", menuKey);
});
</script>
<template>
  <div class="workspace" :data-appearance="auth.user?.appearance || 'light'">
    <a class="skip-link" href="#workspace-main">Pāriet uz saturu</a>
    <button
      v-if="mobileOpen"
      class="workspace-scrim"
      aria-label="Aizvērt navigāciju"
      @click="mobileOpen = false"
    />
    <aside
      ref="sidebar"
      id="workspace-navigation"
      :class="['workspace-sidebar', { 'is-open': mobileOpen }]"
      @keydown.esc="mobileOpen = false"
    >
      <button
        v-if="mobileOpen"
        class="drawer-close"
        aria-label="Aizvērt navigāciju"
        @click="mobileOpen = false"
      >
        Aizvērt ×</button
      ><RouterLink class="workspace-brand" to="/"><HomeBrand /></RouterLink>
      <p class="sidebar-caption">
        {{ isAdmin ? "ADMINISTRĒŠANA" : "MANA DARBA VIETA" }}
      </p>
      <nav aria-label="Lietotnes navigācija">
        <RouterLink
          v-for="link in links"
          :key="link[0]"
          :to="link[0]!"
          :class="{
            active:
              route.path === link[0] ||
              (link[0] !== '/app' &&
                link[0] !== '/admin' &&
                route.path.startsWith(link[0]! + '/')),
          }"
          ><HomeIcon :name="link[1]!" :size="19" />{{ link[2] }}</RouterLink
        ><RouterLink v-if="auth.user?.role === 1 && !isAdmin" to="/admin"
          ><HomeIcon name="shield" :size="19" />Administrēšana</RouterLink
        >
      </nav>
      <div class="workspace-sidebar-footer">
        <span>Tavi dokumenti. Vienmēr pa rokai.</span
        ><small>© 2026 Scan & Save · LV</small>
      </div>
    </aside>
    <div class="workspace-body" :inert="mobileOpen">
      <header class="workspace-header">
        <button
          ref="menuButton"
          aria-controls="workspace-navigation"
          class="workspace-menu"
          :aria-expanded="mobileOpen"
          aria-label="Atvērt navigāciju"
          @click="mobileOpen = !mobileOpen"
        >
          <HomeIcon :name="mobileOpen ? 'close' : 'menu'" /></button
        ><span class="breadcrumb"
          >{{ isAdmin ? "Administrēšana" : "Mana telpa" }}
          <span>/ {{ route.meta.title }}</span></span
        >
        <div class="workspace-account">
          <RouterLink
            class="icon-button"
            to="/app/notifications"
            aria-label="Atgādinājumi"
            ><HomeIcon name="bell" /></RouterLink
          ><span class="user-initial">{{
            auth.user?.name.charAt(0).toUpperCase()
          }}</span
          ><RouterLink to="/app/settings" class="account-name"
            >{{ auth.user?.name
            }}<small>{{
              auth.user?.role === 1 ? "Administrators" : "Personīgais konts"
            }}</small></RouterLink
          ><button class="logout-button" :disabled="busy" @click="logout">
            Iziet
          </button>
        </div>
      </header>
      <div v-if="error" class="error-message layout-error" role="alert">
        {{ error }}
      </div>
      <main id="workspace-main" class="workspace-main"><RouterView /></main>
      <footer class="workspace-footer">
        Tavi dokumenti. Mazāk rūpju. <span>Scan & Save · LV</span>
      </footer>
    </div>
  </div>
</template>
