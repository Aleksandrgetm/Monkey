<script setup lang="ts">
import { onMounted, ref } from "vue";
import { api, downloadFile, extractError } from "../../services/api";
const stats = ref<Record<string, number>>();
const health = ref<{
  database: string;
  scheduler_last_run: string | null;
  mail_mailer: string;
  last_backup_at: string | null;
}>();
const error = ref("");
const loading = ref(true);
const busy = ref(false);
const labels: Record<string, string> = {
  users: "Lietotāji",
  blocked_users: "Bloķēti lietotāji",
  documents: "Dokumenti",
  products: "Produkti",
  categories: "Kategorijas",
  notifications: "Paziņojumi",
  storage_bytes: "Failu apjoms",
  expiring: "Drīz beigsies",
  expired: "Beigušās garantijas",
};
async function load() {
  loading.value = true;
  error.value = "";
  try {
    [stats.value, health.value] = await Promise.all([
      api<Record<string, number>>("/admin/stats"),
      api<NonNullable<typeof health.value>>("/admin/health"),
    ]);
  } catch (e) {
    error.value = extractError(e);
  } finally {
    loading.value = false;
  }
}
async function report() {
  busy.value = true;
  error.value = "";
  try {
    await downloadFile("/admin/report", "scan-save-atskaite.csv");
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">ADMINISTRĒŠANA</p>
        <h1>Sistēmas pārskats</h1>
        <p class="muted">Faktiskie sistēmas dati un darbības stāvoklis.</p>
      </div>
      <button
        class="action-primary"
        :disabled="busy || loading"
        @click="report"
      >
        Lejupielādēt atskaiti
      </button>
    </div>
    <p v-if="error" class="error-message" role="alert">
      {{ error }} <button @click="load">Mēģināt vēlreiz</button>
    </p>
    <div v-if="loading" class="loading-state" role="status">
      Ielādē statistiku…
    </div>
    <template v-else
      ><div class="dashboard-stats admin-metrics">
        <div v-for="(value, key) in stats" :key="key" class="metric-card">
          <span>{{ labels[key] || key }}</span
          ><strong>{{
            key === "storage_bytes"
              ? (value / 1024 / 1024).toFixed(1) + " MB"
              : value
          }}</strong>
        </div>
      </div>
      <section v-if="health" class="panel form-panel">
        <h2>Sistēmas darbība</h2>
        <dl class="detail-grid">
          <div>
            <dt>Datubāze</dt>
            <dd>{{ health.database }}</dd>
          </div>
          <div>
            <dt>Pēdējā atgādinājumu pārbaude</dt>
            <dd>
              {{
                health.scheduler_last_run
                  ? new Date(health.scheduler_last_run).toLocaleString("lv-LV")
                  : "Vēl nav palaista"
              }}
            </dd>
          </div>
          <div>
            <dt>E-pasta transports</dt>
            <dd>{{ health.mail_mailer }}</dd>
          </div>
          <div>
            <dt>Pēdējā rezerves kopija</dt>
            <dd>
              {{
                health.last_backup_at
                  ? new Date(health.last_backup_at).toLocaleString("lv-LV")
                  : "Vēl nav izveidota"
              }}
            </dd>
          </div>
        </dl>
        <p v-if="health.mail_mailer === 'log'" class="muted">
          Lokālais režīms: e-pasta ziņojumi tiek ierakstīti servera žurnālā.
          Piegādei jākonfigurē pasta transports.
        </p>
      </section></template
    >
  </div>
</template>
