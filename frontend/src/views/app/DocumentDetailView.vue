<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, downloadFile, extractError } from "../../services/api";
import type { DocumentRecord } from "../../types";
import {
  documentKinds,
  formatAmount,
  formatBytes,
  formatDate,
} from "../../composables/useDocumentHelpers";
import WarrantyBadge from "../../components/documents/WarrantyBadge.vue";
import DeleteConfirmation from "../../components/documents/DeleteConfirmation.vue";

const route = useRoute();
const router = useRouter();
const document = ref<DocumentRecord | null>(null);
const loading = ref(true);
const error = ref("");
const confirmDelete = ref(false);
const deleting = ref(false);
const deleteError = ref("");
const previewFailed = ref(false);
const downloading = ref(false);
const downloadError = ref("");
const fileUrl = computed(() => `/api/documents/${document.value?.id}/file`);
const backLocation = computed(() =>
  document.value?.kind === "receipt"
    ? "/app/receipts"
    : document.value?.kind === "warranty"
      ? "/app/warranties"
      : "/app/documents",
);
let loadId = 0;
async function load() {
  const token = ++loadId;
  loading.value = true;
  error.value = "";
  downloadError.value = "";
  previewFailed.value = false;
  try {
    const result = await api<DocumentRecord>(`/documents/${route.params.id}`);
    if (token === loadId) document.value = result;
  } catch (cause) {
    if (token === loadId) error.value = extractError(cause);
  } finally {
    if (token === loadId) loading.value = false;
  }
}
async function download() {
  if (downloading.value || !document.value) return;
  downloading.value = true;
  downloadError.value = "";
  try {
    await downloadFile(
      `/documents/${document.value.id}/download`,
      document.value.file_name,
    );
  } catch (cause) {
    downloadError.value = extractError(cause);
  } finally {
    downloading.value = false;
  }
}
async function remove() {
  if (deleting.value || !document.value) return;
  deleting.value = true;
  deleteError.value = "";
  try {
    await api(`/documents/${document.value.id}`, {
      method: "DELETE",
      body: JSON.stringify({ confirmed: true }),
    });
    confirmDelete.value = false;
    await router.push({ path: backLocation.value, query: { deleted: "1" } });
  } catch (cause) {
    deleteError.value = extractError(cause);
  } finally {
    deleting.value = false;
  }
}
watch(() => route.params.id, load, { immediate: true });
</script>

<template>
  <section class="workspace-page">
    <RouterLink :to="backLocation" class="back-link"
      ><i class="mdi mdi-arrow-left" aria-hidden="true" /> Atpakaļ uz
      dokumentiem</RouterLink
    >
    <div v-if="loading" class="panel empty-state" role="status">
      Ielādē dokumentu…
    </div>
    <div v-else-if="error" class="panel empty-state">
      <p class="error-message" role="alert">{{ error }}</p>
      <button type="button" class="action-secondary" @click="load">
        Mēģināt vēlreiz
      </button>
    </div>
    <template v-else-if="document">
      <header class="page-head">
        <div class="document-heading">
          <span class="chip">{{ documentKinds[document.kind] }}</span>
          <h1>{{ document.name || document.file_name }}</h1>
          <p class="muted">Pievienots {{ formatDate(document.created_at) }}</p>
        </div>
        <div class="page-actions">
          <button
            type="button"
            class="action-primary"
            :disabled="downloading"
            @click="download"
          >
            <i class="mdi mdi-download" aria-hidden="true" />
            {{ downloading ? "Lejupielādē…" : "Lejupielādēt" }}</button
          ><RouterLink
            :to="`/app/documents/${document.id}/edit`"
            class="action-secondary"
            ><i class="mdi mdi-pencil-outline" aria-hidden="true" />
            Rediģēt</RouterLink
          >
        </div>
      </header>
      <p v-if="downloadError" class="error-message" role="alert">
        {{ downloadError }}
      </p>
      <p v-if="route.query.saved" class="success-message" role="status">
        {{
          route.query.saved === "created"
            ? "Dokuments ir pievienots."
            : "Izmaiņas ir saglabātas."
        }}
      </p>
      <div class="document-detail-grid">
        <section class="panel preview-panel">
          <div class="preview-header">
            <h2>Faila priekšskatījums</h2>
            <a :href="fileUrl" target="_blank" rel="noopener" class="text-link"
              >Atvērt <i class="mdi mdi-open-in-new" aria-hidden="true"
            /></a>
          </div>
          <div class="file-preview">
            <img
              v-if="document.file_type.startsWith('image/') && !previewFailed"
              :src="fileUrl"
              :alt="document.name || document.file_name"
              @error="previewFailed = true"
            /><iframe
              v-else-if="document.file_type === 'application/pdf'"
              :src="fileUrl"
              :title="`PDF: ${document.name || document.file_name}`"
            />
            <div v-else class="empty-state">
              <i class="mdi mdi-file-document-outline" aria-hidden="true" />
              <p>Priekšskatījums nav pieejams.</p>
              <a
                :href="fileUrl"
                target="_blank"
                rel="noopener"
                class="action-secondary"
                >Atvērt failu</a
              >
            </div>
          </div>
          <div class="file-footer">
            <span>{{ document.file_name }}</span
            ><span
              >{{ formatBytes(document.file_size) }} ·
              {{
                document.file_type === "application/pdf"
                  ? "PDF"
                  : document.file_type === "image/png"
                    ? "PNG"
                    : "JPEG"
              }}</span
            >
          </div>
        </section>
        <div class="metadata-column">
          <section class="panel details-panel">
            <h2>Pirkuma informācija</h2>
            <dl>
              <div>
                <dt>Kategorija</dt>
                <dd>{{ document.category?.name || "—" }}</dd>
              </div>
              <div>
                <dt>Veikals</dt>
                <dd>{{ document.merchant || "Nav norādīts" }}</dd>
              </div>
              <div>
                <dt>Summa</dt>
                <dd class="document-amount">
                  {{ formatAmount(document.amount) }}
                </dd>
              </div>
              <div>
                <dt>Pirkuma datums</dt>
                <dd>{{ formatDate(document.purchase_date) }}</dd>
              </div>
              <div>
                <dt>Produkts</dt>
                <dd>
                  <RouterLink
                    v-if="document.product"
                    :to="`/app/products/${document.product.id}`"
                    class="text-link"
                    >{{ document.product.name }}</RouterLink
                  ><span v-else>Nav piesaistīts</span>
                </dd>
              </div>
            </dl>
          </section>
          <section class="panel details-panel warranty-panel">
            <div class="warranty-title">
              <i class="mdi mdi-shield-check-outline" aria-hidden="true" />
              <h2>Garantija</h2>
            </div>
            <WarrantyBadge :status="document.warranty_status" />
            <dl>
              <div>
                <dt>Derīga līdz</dt>
                <dd>{{ formatDate(document.warranty_end_date) }}</dd>
              </div>
            </dl>
            <p class="muted small-copy">
              {{
                document.warranty_end_date
                  ? "Statusu sistēma aprēķina pēc garantijas beigu datuma."
                  : "Pievieno beigu datumu, lai saņemtu atgādinājumus."
              }}
            </p>
          </section>
          <section v-if="document.note" class="panel details-panel">
            <h2>Piezīme</h2>
            <p class="document-note">{{ document.note }}</p>
          </section>
          <button
            class="delete-link"
            type="button"
            @click="
              deleteError = '';
              confirmDelete = true;
            "
          >
            <i class="mdi mdi-trash-can-outline" aria-hidden="true" /> Dzēst
            dokumentu
          </button>
        </div>
      </div>
      <DeleteConfirmation
        v-model="confirmDelete"
        title="Dzēst dokumentu?"
        :message="`Dokuments “${document.name || document.file_name}”, tā fails un saistītie paziņojumi tiks neatgriezeniski dzēsti.`"
        :busy="deleting"
        :error="deleteError"
        @confirm="remove"
      />
    </template>
  </section>
</template>

<style scoped>
.workspace-page {
  display: grid;
  gap: 24px;
}
.workspace-page > .page-head {
  margin-bottom: 0;
}
.workspace-page > .success-message,
.workspace-page > .error-message {
  margin-bottom: 0;
}
.back-link {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: var(--app-muted, #708095);
  text-decoration: none;
  width: max-content;
}
.document-heading {
  min-width: 0;
}
.document-heading h1 {
  overflow-wrap: anywhere;
}
.document-heading > .chip {
  margin-bottom: 12px;
}
.document-detail-grid {
  display: grid;
  grid-template-columns: minmax(0, 1.55fr) minmax(270px, 1fr);
  gap: 24px;
  align-items: start;
}
.preview-panel {
  padding: 0;
  overflow: hidden;
}
.preview-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 22px;
}
.preview-header h2,
.details-panel h2 {
  font-size: 17px;
}
.text-link {
  color: var(--app-accent, #3865ed);
  font-size: 13px;
  text-decoration: none;
  font-weight: 600;
}
.file-preview {
  background: var(--app-soft, #eff2f7);
  min-height: 460px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
}
.file-preview img {
  max-width: 100%;
  max-height: 760px;
  object-fit: contain;
  box-shadow: 0 5px 30px #12254315;
}
.file-preview iframe {
  width: 100%;
  height: 680px;
  border: 0;
  border-radius: 6px;
  background: white;
}
.file-footer {
  display: flex;
  justify-content: space-between;
  gap: 15px;
  padding: 18px 22px;
  color: var(--app-muted, #708095);
  font-size: 11px;
  overflow-wrap: anywhere;
}
.file-footer > span:last-child {
  flex-shrink: 0;
}
.metadata-column {
  display: grid;
  gap: 20px;
  min-width: 0;
}
.details-panel {
  padding: 26px;
}
.details-panel h2 {
  margin-bottom: 20px;
}
.details-panel dl {
  margin: 0;
}
.details-panel dl > div {
  padding: 12px 0;
  display: flex;
  justify-content: space-between;
  gap: 14px;
  border-bottom: 1px solid var(--app-border, #e6eaf1);
  font-size: 13px;
}
.details-panel dl > div:last-child {
  border-bottom: 0;
}
.details-panel dt {
  color: var(--app-muted, #708095);
  flex-shrink: 0;
}
.details-panel dd {
  margin: 0;
  text-align: right;
  overflow-wrap: anywhere;
  min-width: 0;
}
.document-amount {
  font-weight: 750;
  font-variant-numeric: tabular-nums;
}
.warranty-title {
  display: flex;
  gap: 9px;
  align-items: center;
  margin-bottom: 20px;
}
.warranty-title h2 {
  margin: 0;
}
.warranty-title i {
  color: var(--app-accent, #3865ed);
  font-size: 23px;
}
.warranty-panel dl {
  margin-top: 10px;
}
.small-copy {
  font-size: 12px;
  line-height: 1.7;
  margin-top: 8px;
}
.document-note {
  white-space: pre-wrap;
  font-size: 13px;
  line-height: 1.8;
  overflow-wrap: anywhere;
}
.delete-link {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: #b44440;
  font-size: 13px;
  padding: 13px;
}
.delete-link:hover {
  background: #b444400a;
  border-radius: 10px;
}
@media (max-width: 1100px) {
  .document-detail-grid {
    grid-template-columns: 1fr;
  }
  .metadata-column {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    align-items: start;
  }
  .file-preview {
    min-height: 320px;
  }
  .delete-link {
    grid-column: 1/-1;
  }
}
@media (max-width: 600px) {
  .metadata-column {
    grid-template-columns: 1fr;
  }
  .file-preview {
    padding: 12px;
  }
  .file-preview iframe {
    height: 480px;
  }
  .file-footer {
    flex-direction: column;
    gap: 6px;
  }
  .details-panel {
    padding: 21px;
  }
  .preview-header {
    padding: 20px;
  }
}
</style>
