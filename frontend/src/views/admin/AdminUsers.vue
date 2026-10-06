<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { api, extractError } from "../../services/api";
import type { User, Paginated } from "../../types";
import ConfirmDialog from "../../components/app/ConfirmDialog.vue";
const auth = useAuthStore();
const router = useRouter();
const list = ref<Paginated<User>>();
const loading = ref(true);
const busy = ref(false);
const error = ref("");
const success = ref("");
const editing = ref<User | null>(null);
const deleting = ref<User | null>(null);
const confirm = ref(false);
const query = reactive({
  search: "",
  role: "",
  status: "",
  sort: "created_at",
  direction: "desc",
});
const form = reactive({ name: "", email: "", role: 0, status: 1 });
let requestSequence = 0;
async function load(page = 1) {
  const sequence = ++requestSequence;
  loading.value = true;
  error.value = "";
  try {
    const result = await api<NonNullable<typeof list.value>>(
      `/admin/users?${new URLSearchParams({ ...query, page: String(page) })}`,
    );
    if (sequence === requestSequence) list.value = result;
  } catch (e) {
    if (sequence === requestSequence) error.value = extractError(e);
  } finally {
    if (sequence === requestSequence) loading.value = false;
  }
}
function edit(user: User) {
  editing.value = user;
  Object.assign(form, {
    name: user.name,
    email: user.email,
    role: user.role,
    status: user.status,
  });
}
async function save() {
  if (!editing.value || busy.value) return;
  busy.value = true;
  error.value = "";
  try {
    await api(`/admin/users/${editing.value.id}`, {
      method: "PATCH",
      body: JSON.stringify(form),
    });
    const self = editing.value.id === auth.user?.id;
    editing.value = null;
    if (self) {
      await auth.fetchUser(true);
      if (!auth.user) {
        await router.replace("/login");
        return;
      }
      if (auth.user.role !== 1) {
        await router.replace("/app");
        return;
      }
    }
    await load(list.value?.current_page);
    success.value = "Lietotāja dati saglabāti.";
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = false;
  }
}
async function remove() {
  if (!deleting.value || busy.value) return;
  busy.value = true;
  error.value = "";
  try {
    await api(`/admin/users/${deleting.value.id}`, {
      method: "DELETE",
      body: JSON.stringify({ confirmed: true }),
    });
    confirm.value = false;
    if (deleting.value.id === auth.user?.id) {
      auth.clear();
      await router.replace("/login");
      return;
    }
    await load();
    success.value = "Lietotājs un viņa dati dzēsti.";
  } catch (e) {
    error.value = extractError(e);
    confirm.value = false;
  } finally {
    busy.value = false;
  }
}
onMounted(() => load());
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">ADMINISTRĒŠANA</p>
        <h1>Lietotāji</h1>
        <p class="muted">Konti, piekļuves tiesības un statusi.</p>
      </div>
    </div>
    <p v-if="error && !editing" class="error-message" role="alert">
      {{ error }}
    </p>
    <p v-if="success" class="success-message" role="status">{{ success }}</p>
    <section class="panel">
      <form class="toolbar" @submit.prevent="load()">
        <label class="field search-field"
          >Meklēt lietotāju<input
            v-model="query.search"
            maxlength="255"
            placeholder="Vārds vai e-pasts" /></label
        ><label class="field"
          >Loma<select v-model="query.role">
            <option value="">Visas</option>
            <option value="0">Lietotājs</option>
            <option value="1">Administrators</option>
          </select></label
        ><label class="field"
          >Statuss<select v-model="query.status">
            <option value="">Visi</option>
            <option value="1">Aktīvs</option>
            <option value="0">Bloķēts</option>
          </select></label
        ><label class="field"
          >Kārtot<select v-model="query.sort">
            <option value="created_at">Izveides datums</option>
            <option value="name">Vārds</option>
            <option value="email">E-pasts</option>
          </select></label
        ><label class="field"
          >Secība<select v-model="query.direction">
            <option value="desc">Dilstoši</option>
            <option value="asc">Augoši</option>
          </select></label
        ><button class="action-primary" :disabled="loading">Meklēt</button>
      </form>
      <div v-if="loading" class="loading-state" role="status">
        Ielādē lietotājus…
      </div>
      <div v-else-if="!list?.data.length" class="empty-state">
        <h2>Lietotāji nav atrasti.</h2>
        <p>Maini meklēšanas nosacījumus.</p>
      </div>
      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Lietotājs</th>
              <th>E-pasts</th>
              <th>Loma</th>
              <th>Statuss</th>
              <th>Darbības</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="user in list.data" :key="user.id">
              <td data-label="Lietotājs">
                <strong>{{ user.name }}</strong>
              </td>
              <td data-label="E-pasts">{{ user.email }}</td>
              <td data-label="Loma">
                {{ user.role === 1 ? "Administrators" : "Lietotājs" }}
              </td>
              <td data-label="Statuss">
                <span
                  :class="['chip', user.status === 1 ? 'active' : 'expired']"
                  >{{ user.status === 1 ? "Aktīvs" : "Bloķēts" }}</span
                >
              </td>
              <td data-label="Darbības">
                <div class="row-actions">
                  <button class="action-secondary" @click="edit(user)">
                    Rediģēt</button
                  ><button
                    class="action-danger"
                    @click="
                      deleting = user;
                      confirm = true;
                    "
                  >
                    Dzēst
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="list && list.last_page > 1" class="pagination">
        <button
          :disabled="list.current_page === 1 || loading"
          @click="load(list.current_page - 1)"
        >
          ← Iepriekšējā</button
        ><span>{{ list.current_page }} / {{ list.last_page }}</span
        ><button
          :disabled="list.current_page === list.last_page || loading"
          @click="load(list.current_page + 1)"
        >
          Nākamā →
        </button>
      </div>
    </section>
    <v-dialog
      :model-value="!!editing"
      :persistent="busy"
      max-width="520"
      aria-labelledby="edit-user-title"
      @update:model-value="!$event && (editing = null)"
      ><v-card class="confirm-card"
        ><h2 id="edit-user-title">Rediģēt lietotāju</h2>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <form @submit.prevent="save">
          <label class="field"
            >Lietotājvārds<input
              v-model="form.name"
              required
              minlength="3"
              maxlength="30" /></label
          ><label class="field"
            >E-pasts<input
              v-model="form.email"
              type="email"
              required
              maxlength="30"
          /></label>
          <div class="form-grid">
            <label class="field"
              >Loma<select v-model.number="form.role">
                <option :value="0">Lietotājs</option>
                <option :value="1">Administrators</option>
              </select></label
            ><label class="field"
              >Statuss<select v-model.number="form.status">
                <option :value="1">Aktīvs</option>
                <option :value="0">Bloķēts</option>
              </select></label
            >
          </div>
          <div class="page-actions">
            <button
              type="button"
              class="action-secondary"
              :disabled="busy"
              @click="editing = null"
            >
              Atcelt</button
            ><button class="action-primary" :disabled="busy">Saglabāt</button>
          </div>
        </form></v-card
      ></v-dialog
    ><ConfirmDialog
      v-model="confirm"
      title="Vai dzēst lietotāju?"
      :message="`${deleting?.name || ''}: konts un visi tā faili tiks neatgriezeniski dzēsti.`"
      :busy="busy"
      confirm-label="Dzēst lietotāju"
      @confirm="remove"
    />
  </div>
</template>
