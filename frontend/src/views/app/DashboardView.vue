<script setup lang="ts">
import { onMounted, ref } from "vue";
import { api, extractError } from "../../services/api";
import { useAuthStore } from "../../stores/auth";
import type { Dashboard } from "../../types";
import HomeIcon from "../../components/home/HomeIcon.vue";
const auth = useAuthStore();
const data = ref<Dashboard>();
const loading = ref(true);
const error = ref("");
async function load() {
  loading.value = true;
  error.value = "";
  try {
    data.value = await api<Dashboard>("/dashboard");
  } catch (e) {
    error.value = extractError(e);
  } finally {
    loading.value = false;
  }
}
const date = (v: string | null) =>
  v ? new Intl.DateTimeFormat("lv-LV").format(new Date(v + "T12:00:00")) : "—";
const status = {
  active: "Aktīva",
  expiring: "Drīz beigsies",
  expired: "Beigusies",
};
onMounted(load);
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">TAVA IKDIENA, SAKĀRTOTA</p>
        <h1>Sveiks, {{ auth.user?.name }}!</h1>
        <p class="muted">
          Tavi pirkumi un dokumenti — pārskatāmi un vienuviet.
        </p>
      </div>
      <RouterLink class="action-primary" to="/app/documents/new"
        >+ Pievienot dokumentu</RouterLink
      >
    </div>
    <div v-if="loading" class="loading-state" role="status">
      Ielādē pārskatu…
    </div>
    <div v-else-if="error" class="error-message" role="alert">
      {{ error }} <button @click="load">Mēģināt vēlreiz</button>
    </div>
    <template v-else-if="data"
      ><div class="dashboard-stats">
        <RouterLink
          v-for="stat in [
            {
              label: 'Kopā dokumenti',
              value: data.documents,
              icon: 'file',
              to: 'documents',
            },
            {
              label: 'Čeki',
              value: data.receipts,
              icon: 'scan',
              to: 'receipts',
            },
            {
              label: 'Garantijas',
              value: data.warranties,
              icon: 'shield',
              to: 'warranties',
            },
            {
              label: 'Produkti',
              value: data.products,
              icon: 'laptop',
              to: 'products',
            },
          ]"
          :key="stat.to"
          :to="`/app/${stat.to}`"
          class="metric-card"
          ><HomeIcon :name="stat.icon" /><span>{{ stat.label }}</span
          ><strong>{{ stat.value }}</strong
          ><small>Apskatīt →</small></RouterLink
        >
      </div>
      <RouterLink
        v-if="data.expiring"
        to="/app/documents?warranty_status=expiring"
        class="dashboard-notice"
        ><HomeIcon name="bell" /><span
          ><strong>{{ data.expiring }} garantijas drīz beigsies.</strong>
          Pārskati termiņus un saistītos dokumentus.</span
        ><HomeIcon name="arrow"
      /></RouterLink>
      <section class="panel">
        <div class="panel-heading">
          <h2>Nesen pievienotie dokumenti</h2>
          <RouterLink to="/app/documents">Visi dokumenti →</RouterLink>
        </div>
        <div v-if="!data.recent.length" class="empty-state">
          <HomeIcon name="folder" :size="40" />
          <h2>Tava dokumentu vieta ir gatava.</h2>
          <p>Izveido kategoriju un pievieno pirmo čeku vai garantiju.</p>
          <RouterLink class="action-primary" to="/app/categories"
            >Izveidot kategoriju</RouterLink
          >
        </div>
        <div v-else class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Dokuments</th>
                <th>Pirkuma datums</th>
                <th>Summa</th>
                <th>Garantija</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="doc in data.recent" :key="doc.id">
                <td data-label="Dokuments">
                  <RouterLink
                    class="document-link"
                    :to="`/app/documents/${doc.id}`"
                    ><HomeIcon name="file" />{{
                      doc.name || doc.file_name
                    }}</RouterLink
                  >
                </td>
                <td data-label="Datums">{{ date(doc.purchase_date) }}</td>
                <td data-label="Summa">
                  {{
                    doc.amount
                      ? Number(doc.amount).toLocaleString("lv-LV", {
                          style: "currency",
                          currency: "EUR",
                        })
                      : "—"
                  }}
                </td>
                <td data-label="Garantija">
                  <span
                    v-if="doc.warranty_status"
                    :class="['chip', doc.warranty_status]"
                    >{{ status[doc.warranty_status] }}</span
                  ><span v-else>—</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>
      <RouterLink v-if="data.unread" to="/app/notifications" class="subtle-link"
        >{{ data.unread }} nelasīti paziņojumi →</RouterLink
      ></template
    >
  </div>
</template>
