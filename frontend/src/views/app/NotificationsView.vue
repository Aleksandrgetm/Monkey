<script setup lang="ts">
import { onMounted, ref } from "vue";
import { api, extractError } from "../../services/api";
import type { Paginated, Reminder } from "../../types";
import HomeIcon from "../../components/home/HomeIcon.vue";
const list = ref<Paginated<Reminder>>();
const loading = ref(true);
const error = ref("");
const success = ref("");
const kind = ref("");
const status = ref("");
const busy = ref(false);
let requestSequence = 0;
async function load(page = 1) {
  const sequence = ++requestSequence;
  loading.value = true;
  error.value = "";
  try {
    const result = await api<NonNullable<typeof list.value>>(
      `/notifications?${new URLSearchParams({ page: String(page), kind: kind.value, status: status.value })}`,
    );
    if (sequence === requestSequence) list.value = result;
  } catch (e) {
    if (sequence === requestSequence) error.value = extractError(e);
  } finally {
    if (sequence === requestSequence) loading.value = false;
  }
}
async function read(id?: number) {
  if (busy.value) return;
  busy.value = true;
  error.value = "";
  try {
    if (id)
      await api(`/notifications/${id}`, {
        method: "PATCH",
        body: JSON.stringify({ status: 1 }),
      });
    else await api("/notifications/read-all", { method: "POST" });
    await load();
    success.value = "Paziņojumi atzīmēti kā izlasīti.";
  } catch (e) {
    error.value = extractError(e);
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
        <p class="eyebrow">SVARĪGAIS ĪSTAJĀ BRĪDĪ</p>
        <h1>Atgādinājumi</h1>
        <p class="muted">
          Jaunumi par taviem dokumentiem un garantiju termiņiem.
        </p>
      </div>
      <RouterLink class="action-secondary" to="/app/settings#notifications"
        >Paziņojumu iestatījumi</RouterLink
      >
    </div>
    <div class="panel">
      <div class="toolbar">
        <label class="field"
          >Paziņojumu veids<select v-model="kind" @change="load()">
            <option value="">Visi paziņojumi</option>
            <option value="warranty">Garantijas</option>
            <option value="system">Sistēmas</option>
          </select></label
        ><label class="field"
          >Statuss<select v-model="status" @change="load()">
            <option value="">Visi statusi</option>
            <option value="0">Jauni</option>
            <option value="1">Izlasīti</option>
          </select></label
        ><button
          class="action-secondary"
          :disabled="busy || !list?.total"
          @click="read()"
        >
          Atzīmēt visus kā izlasītus
        </button>
      </div>
      <p v-if="error" class="error-message" role="alert">
        {{ error }} <button @click="load()">Mēģināt vēlreiz</button>
      </p>
      <p v-if="success" class="success-message" role="status">{{ success }}</p>
      <div v-if="loading" class="loading-state" role="status">
        Ielādē paziņojumus…
      </div>
      <div v-else-if="!list?.data.length && !error" class="empty-state">
        <HomeIcon name="bell" :size="40" />
        <h2>Šobrīd viss mierīgi.</h2>
        <p>Šeit parādīsies tavi dokumentu un garantiju paziņojumi.</p>
      </div>
      <div v-else class="reminder-list">
        <article
          v-for="item in list?.data"
          :key="item.id"
          :class="['reminder-row', { unread: item.status === 0 }]"
        >
          <span class="reminder-icon"
            ><HomeIcon :name="item.kind === 'warranty' ? 'bell' : 'file'"
          /></span>
          <div>
            <span v-if="item.status === 0" class="chip expiring">Jauns</span>
            <h2>{{ item.message }}</h2>
            <p class="muted">
              {{ new Date(item.notification_date).toLocaleDateString("lv-LV") }}
            </p>
            <RouterLink
              v-if="item.document"
              :to="`/app/documents/${item.document.id}`"
              >{{ item.document.name || item.document.file_name }} →</RouterLink
            >
          </div>
          <button
            v-if="item.status === 0"
            class="action-secondary"
            :disabled="busy"
            @click="read(item.id)"
          >
            Atzīmēt kā izlasītu</button
          ><span v-else class="muted">Izlasīts</span>
        </article>
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
    </div>
  </div>
</template>
