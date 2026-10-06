import { defineStore } from "pinia";
import { ref } from "vue";
import { api, ApiError, resetCsrf } from "../services/api";
import type { User } from "../types";
export const useAuthStore = defineStore("auth", () => {
  const user = ref<User | null>(null);
  const ready = ref(false);
  let pending: Promise<void> | undefined;
  async function fetchUser(force = false) {
    if (ready.value && !force) return;
    pending ??= api<{ user: User }>("/auth/me")
      .then((data) => {
        user.value = data.user;
        ready.value = true;
      })
      .catch((error) => {
        if (error instanceof ApiError && [401, 403].includes(error.status)) {
          user.value = null;
          ready.value = true;
        } else throw error;
      })
      .finally(() => {
        pending = undefined;
      });
    await pending;
  }
  async function login(payload: {
    email: string;
    password: string;
    remember: boolean;
  }) {
    const data = await api<{ user: User }>("/auth/login", {
      method: "POST",
      body: JSON.stringify(payload),
    });
    user.value = data.user;
    ready.value = true;
    resetCsrf();
  }
  async function register(payload: {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
  }) {
    const data = await api<{ user: User }>("/auth/register", {
      method: "POST",
      body: JSON.stringify(payload),
    });
    user.value = data.user;
    ready.value = true;
    resetCsrf();
  }
  async function logout() {
    await api("/auth/logout", { method: "POST" });
    clear();
    resetCsrf();
  }
  function clear() {
    user.value = null;
    ready.value = false;
    resetCsrf();
  }
  return { user, ready, fetchUser, login, register, logout, clear };
});
