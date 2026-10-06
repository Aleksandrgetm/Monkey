<script setup lang="ts">
import { ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, extractError } from "../../services/api";
import type { Product } from "../../types";
import { formatAmount, formatDate } from "../../composables/useDocumentHelpers";
import DocumentTable from "../../components/documents/DocumentTable.vue";
import DeleteConfirmation from "../../components/documents/DeleteConfirmation.vue";

const route = useRoute();
const router = useRouter();
const product = ref<Product | null>(null);
const loading = ref(true);
const error = ref("");
const confirmDelete = ref(false);
const deleting = ref(false);
const deleteError = ref("");
let requestId = 0;
async function load() {
  const token = ++requestId;
  loading.value = true;
  error.value = "";
  try {
    const result = await api<Product>(`/products/${route.params.id}`);
    if (token === requestId) product.value = result;
  } catch (cause) {
    if (token === requestId) error.value = extractError(cause);
  } finally {
    if (token === requestId) loading.value = false;
  }
}
async function remove() {
  if (deleting.value || !product.value) return;
  deleting.value = true;
  deleteError.value = "";
  try {
    await api(`/products/${product.value.id}`, {
      method: "DELETE",
      body: JSON.stringify({ confirmed: true }),
    });
    confirmDelete.value = false;
    await router.push({ path: "/app/products", query: { deleted: "1" } });
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
    <RouterLink to="/app/products" class="back-link"
      ><i class="mdi mdi-arrow-left" aria-hidden="true" /> Atpakaļ uz
      produktiem</RouterLink
    >
    <div v-if="loading" class="panel empty-state" role="status">
      Ielādē produktu…
    </div>
    <div v-else-if="error" class="panel empty-state">
      <p class="error-message" role="alert">{{ error }}</p>
      <button class="action-secondary" type="button" @click="load">
        Mēģināt vēlreiz
      </button>
    </div>
    <template v-else-if="product">
      <header class="page-head">
        <div>
          <span class="chip">{{ product.category?.name || "Produkts" }}</span>
          <h1 class="product-title">{{ product.name }}</h1>
          <p class="muted">Pirkuma informācija un visi saistītie dokumenti.</p>
        </div>
        <div class="page-actions">
          <RouterLink
            :to="{
              path: '/app/documents/new',
              query: { product_id: product.id },
            }"
            class="action-primary"
            ><i class="mdi mdi-plus" aria-hidden="true" /> Pievienot
            dokumentu</RouterLink
          ><RouterLink
            :to="`/app/products/${product.id}/edit`"
            class="action-secondary"
            ><i class="mdi mdi-pencil-outline" aria-hidden="true" />
            Rediģēt</RouterLink
          >
        </div>
      </header>
      <p v-if="route.query.saved" class="success-message" role="status">
        {{
          route.query.saved === "created"
            ? "Produkts ir pievienots. Tagad vari piesaistīt dokumentus."
            : "Izmaiņas ir saglabātas."
        }}
      </p>
      <section class="panel product-overview">
        <div class="product-overview-icon">
          <i class="mdi mdi-package-variant-closed" aria-hidden="true" />
        </div>
        <dl>
          <div>
            <dt>Veikals</dt>
            <dd>{{ product.merchant || "Nav norādīts" }}</dd>
          </div>
          <div>
            <dt>Summa</dt>
            <dd class="product-amount">{{ formatAmount(product.amount) }}</dd>
          </div>
          <div>
            <dt>Pirkuma datums</dt>
            <dd>{{ formatDate(product.purchase_date) }}</dd>
          </div>
          <div>
            <dt>Norādītais garantijas termiņš</dt>
            <dd>{{ formatDate(product.warranty_end_date) }}</dd>
          </div>
        </dl>
        <div v-if="product.note" class="product-note">
          <h2>Piezīme</h2>
          <p>{{ product.note }}</p>
        </div>
      </section>
      <section class="panel linked-documents">
        <div class="linked-documents-heading">
          <div>
            <h2>
              Saistītie dokumenti
              <span>{{ product.documents?.length || 0 }}</span>
            </h2>
            <p class="muted">
              Katra dokumenta garantijas statuss tiek aprēķināts atsevišķi.
            </p>
          </div>
          <div class="page-actions">
            <RouterLink
              :to="{
                path: '/app/documents/new',
                query: { product_id: product.id, kind: 'receipt' },
              }"
              class="action-secondary"
              >+ Čeks</RouterLink
            ><RouterLink
              :to="{
                path: '/app/documents/new',
                query: { product_id: product.id, kind: 'warranty' },
              }"
              class="action-secondary"
              >+ Garantija</RouterLink
            >
          </div>
        </div>
        <DocumentTable
          v-if="product.documents?.length"
          :documents="product.documents"
        />
        <div v-else class="empty-state">
          <i
            class="mdi mdi-file-multiple-outline empty-symbol"
            aria-hidden="true"
          />
          <h3>Pievieno pirmo dokumentu</h3>
          <p>Šeit būs šī produkta čeki, garantijas un citi faili.</p>
          <RouterLink
            :to="{
              path: '/app/documents/new',
              query: { product_id: product.id },
            }"
            class="action-primary"
            >Pievienot dokumentu</RouterLink
          >
          <p class="muted small-copy">
            Jau saglabātu dokumentu vari piesaistīt tā rediģēšanas lapā.
          </p>
        </div>
      </section>
      <div class="product-danger">
        <p class="muted">Produkta dzēšana saglabās tā dokumentus.</p>
        <button
          type="button"
          class="delete-link"
          @click="
            deleteError = '';
            confirmDelete = true;
          "
        >
          <i class="mdi mdi-trash-can-outline" aria-hidden="true" /> Dzēst
          produktu
        </button>
      </div>
      <DeleteConfirmation
        v-model="confirmDelete"
        title="Dzēst produktu?"
        :message="`Produkts “${product.name}” tiks neatgriezeniski dzēsts. Tā dokumenti paliks tavā dokumentu sarakstā.`"
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
.product-title {
  margin-top: 12px;
  overflow-wrap: anywhere;
}
.product-overview {
  padding: 28px;
  display: flex;
  align-items: center;
  gap: 30px;
  flex-wrap: wrap;
}
.product-overview-icon {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 78px;
  height: 78px;
  background: var(--app-soft, #edf2ff);
  border-radius: 20px;
  color: var(--app-accent, #3865ed);
  font-size: 39px;
}
.product-overview dl {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 24px;
  flex: 1;
  margin: 0;
  min-width: 0;
}
.product-overview dt {
  font-size: 11px;
  color: var(--app-muted, #708095);
  line-height: 1.6;
}
.product-overview dd {
  font-size: 14px;
  font-weight: 600;
  margin: 8px 0 0;
  overflow-wrap: anywhere;
}
.product-overview .product-amount {
  font-size: 19px;
}
.product-note {
  width: 100%;
  border-top: 1px solid var(--app-border, #e6eaf1);
  padding-top: 20px;
}
.product-note h2 {
  font-size: 13px;
  margin-bottom: 10px;
}
.product-note p {
  font-size: 13px;
  line-height: 1.8;
  white-space: pre-wrap;
  overflow-wrap: anywhere;
}
.linked-documents {
  padding: 0;
  overflow: hidden;
}
.linked-documents-heading {
  padding: 25px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.linked-documents-heading h2 {
  font-size: 18px;
}
.linked-documents-heading h2 span {
  color: var(--app-muted, #708095);
  font-size: 13px;
  margin-left: 8px;
}
.linked-documents-heading p {
  font-size: 12px;
  margin-top: 8px;
  line-height: 1.7;
}
.linked-documents-heading > .page-actions {
  flex-shrink: 0;
  margin-top: 0;
}
.empty-symbol {
  font-size: 35px;
  color: var(--app-accent, #3865ed);
}
.small-copy {
  font-size: 12px;
  margin-top: 18px;
}
.empty-state .action-primary {
  margin-top: 14px;
}
.product-danger {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 0 4px;
}
.product-danger p {
  font-size: 12px;
}
.delete-link {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #b44440;
  font-size: 13px;
  padding: 12px;
  white-space: nowrap;
}
@media (max-width: 1050px) {
  .product-overview dl {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .linked-documents-heading {
    align-items: flex-start;
    flex-direction: column;
  }
}
@media (max-width: 600px) {
  .product-overview {
    padding: 22px;
    gap: 22px;
    align-items: flex-start;
  }
  .product-overview-icon {
    width: 52px;
    height: 52px;
    font-size: 29px;
    border-radius: 14px;
  }
  .product-overview dl {
    flex-basis: 100%;
    gap: 20px;
  }
  .product-danger {
    align-items: flex-start;
    flex-direction: column;
    gap: 7px;
  }
  .product-danger .delete-link {
    padding-left: 0;
  }
  .linked-documents-heading {
    padding: 20px;
  }
}
</style>
