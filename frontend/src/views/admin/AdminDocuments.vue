<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { api, downloadFile, extractError } from "../../services/api";
import type { DocumentRecord, Paginated } from "../../types";
import ConfirmDialog from "../../components/app/ConfirmDialog.vue";
const list = ref<Paginated<DocumentRecord>>();
const loading = ref(true);
const busy = ref(false);
const error = ref("");
const success = ref("");
const editing = ref<DocumentRecord | null>(null);
const deleting = ref<DocumentRecord | null>(null);
const confirm = ref(false);
const query = reactive({
  search: "",
  user_id: "",
  kind: "",
  sort: "created_at",
  direction: "desc",
});
const form = reactive({
  name: "",
  kind: "other",
  merchant: "",
  amount: "",
  purchase_date: "",
  warranty_end_date: "",
  note: "",
});
let requestSequence = 0;
async function load(page = 1) {
  const sequence = ++requestSequence;
  loading.value = true;
  error.value = "";
  try {
    const result = await api<NonNullable<typeof list.value>>(
      `/admin/documents?${new URLSearchParams({ ...query, page: String(page) })}`,
    );
    if (sequence === requestSequence) list.value = result;
  } catch (e) {
    if (sequence === requestSequence) error.value = extractError(e);
  } finally {
    if (sequence === requestSequence) loading.value = false;
  }
}
function edit(doc: DocumentRecord) {
  error.value = "";
  editing.value = doc;
  Object.assign(form, {
    name: doc.name || "",
    kind: doc.kind,
    merchant: doc.merchant || "",
    amount: doc.amount || "",
    purchase_date: doc.purchase_date || "",
    warranty_end_date: doc.warranty_end_date || "",
    note: doc.note || "",
  });
}
async function save() {
  if (!editing.value) return;
  busy.value = true;
  error.value = "";
  try {
    await api(`/admin/documents/${editing.value.id}`, {
      method: "PATCH",
      body: JSON.stringify(form),
    });
    editing.value = null;
    await load(list.value?.current_page);
    success.value = "Dokumenta dati saglabāti.";
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = false;
  }
}
async function remove() {
  if (!deleting.value) return;
  busy.value = true;
  error.value = "";
  try {
    await api(`/admin/documents/${deleting.value.id}`, {
      method: "DELETE",
      body: JSON.stringify({ confirmed: true }),
    });
    confirm.value = false;
    await load();
    success.value = "Dokuments un fails dzēsti.";
  } catch (e) {
    error.value = extractError(e);
    confirm.value = false;
  } finally {
    busy.value = false;
  }
}
async function download(doc: DocumentRecord) {
  error.value = "";
  try {
    await downloadFile(`/admin/documents/${doc.id}/download`, doc.file_name);
  } catch (e) {
    error.value = extractError(e);
  }
}
onMounted(() => load());
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">ADMINISTRĒŠANA</p>
        <h1>Dokumentu pārvaldība</h1>
        <p class="muted">Pārskati un labo sistēmas dokumentu informāciju.</p>
      </div>
    </div>
    <p v-if="error && !editing" class="error-message" role="alert">
      {{ error }}
    </p>
    <p v-if="success" class="success-message" role="status">{{ success }}</p>
    <section class="panel">
      <form class="toolbar" @submit.prevent="load()">
        <label class="field search-field"
          >Meklēt dokumentu<input
            v-model="query.search"
            maxlength="255"
            placeholder="Dokumenta nosaukums" /></label
        ><label class="field"
          >Īpašnieka ID<input
            v-model="query.user_id"
            type="number"
            min="1" /></label
        ><label class="field"
          >Veids<select v-model="query.kind">
            <option value="">Visi veidi</option>
            <option value="receipt">Čeks</option>
            <option value="warranty">Garantija</option>
            <option value="other">Cits</option>
          </select></label
        ><label class="field"
          >Kārtot<select v-model="query.sort">
            <option value="created_at">Izveides datums</option>
            <option value="name">Nosaukums</option>
            <option value="amount">Summa</option>
          </select></label
        ><label class="field"
          >Secība<select v-model="query.direction">
            <option value="desc">Dilstoši</option>
            <option value="asc">Augoši</option>
          </select></label
        ><button class="action-primary" :disabled="loading">Meklēt</button>
      </form>
      <div v-if="loading" class="loading-state" role="status">
        Ielādē dokumentus…
      </div>
      <div v-else-if="!list?.data.length" class="empty-state">
        <h2>Dokumenti nav atrasti.</h2>
        <p>Maini filtrus vai meklēšanas nosacījumus.</p>
      </div>
      <div v-else class="table-wrap">
        <table class="data-table">
          <thead>
            <tr>
              <th>Dokuments</th>
              <th>Īpašnieks</th>
              <th>Veids</th>
              <th>Darbības</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="doc in list.data" :key="doc.id">
              <td data-label="Dokuments">
                <strong>{{ doc.name || doc.file_name }}</strong
                ><small class="block muted">{{ doc.file_name }}</small>
              </td>
              <td data-label="Īpašnieks">
                {{ doc.owner?.name || `#${doc.user_id}` }}
              </td>
              <td data-label="Veids">
                {{
                  { receipt: "Čeks", warranty: "Garantija", other: "Cits" }[
                    doc.kind
                  ]
                }}
              </td>
              <td data-label="Darbības">
                <div class="row-actions">
                  <button class="action-secondary" @click="download(doc)">
                    Fails</button
                  ><button class="action-secondary" @click="edit(doc)">
                    Rediģēt</button
                  ><button
                    class="action-danger"
                    @click="
                      deleting = doc;
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
      max-width="640"
      aria-labelledby="edit-document-title"
      @update:model-value="!$event && (editing = null)"
      ><v-card class="confirm-card"
        ><h2 id="edit-document-title">Rediģēt dokumentu</h2>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <form @submit.prevent="save">
          <label class="field"
            >Nosaukums<input v-model="form.name" maxlength="255"
          /></label>
          <div class="form-grid">
            <label class="field"
              >Veids<select v-model="form.kind">
                <option value="receipt">Čeks</option>
                <option value="warranty">Garantija</option>
                <option value="other">Cits</option>
              </select></label
            ><label class="field"
              >Veikals<input v-model="form.merchant" maxlength="255" /></label
            ><label class="field"
              >Summa (€)<input
                v-model="form.amount"
                type="number"
                min="0"
                max="99999.99"
                step=".01" /></label
            ><label class="field"
              >Pirkuma datums<input
                v-model="form.purchase_date"
                type="date" /></label
            ><label class="field"
              >Garantijas beigas<input
                v-model="form.warranty_end_date"
                type="date"
            /></label>
          </div>
          <label class="field"
            >Piezīme<textarea v-model="form.note" maxlength="300" rows="3" />
          </label>
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
      title="Vai dzēst dokumentu?"
      :message="`${deleting?.name || deleting?.file_name || ''} un tā fails tiks neatgriezeniski dzēsti.`"
      :busy="busy"
      confirm-label="Dzēst dokumentu"
      @confirm="remove"
    />
  </div>
</template>
