<script setup lang="ts">
import { reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, extractError } from "../../services/api";
import type { Category, Paginated, Product } from "../../types";
import {
  formatAmount,
  formatDate,
  queryString,
} from "../../composables/useDocumentHelpers";

const route = useRoute();
const router = useRouter();
const filters = reactive({
  search: "",
  category_id: "",
  merchant: "",
  sort: "created_at",
  direction: "desc",
});
const response = ref<Paginated<Product> | null>(null);
const categories = ref<Category[]>([]);
const loading = ref(true);
const error = ref("");
const lookupError = ref("");
let requestId = 0;
async function loadCategories() {
  lookupError.value = "";
  try {
    categories.value = (await api<{ data: Category[] }>("/categories")).data;
  } catch (cause) {
    lookupError.value = extractError(cause);
  }
}
async function load() {
  const token = ++requestId;
  loading.value = true;
  error.value = "";
  for (const key of Object.keys(filters) as (keyof typeof filters)[]) {
    const value = route.query[key];
    filters[key] =
      typeof value === "string"
        ? value
        : key === "sort"
          ? "created_at"
          : key === "direction"
            ? "desc"
            : "";
  }
  try {
    const result = await api<Paginated<Product>>(
      `/products?${queryString({ ...filters, page: Number(route.query.page) || 1 })}`,
    );
    if (token === requestId) response.value = result;
  } catch (cause) {
    if (token === requestId) error.value = extractError(cause);
  } finally {
    if (token === requestId) loading.value = false;
  }
}
function applyFilters() {
  const query = {
    ...Object.fromEntries(
      Object.entries(filters).filter(([, value]) => value !== ""),
    ),
    page: "1",
  };
  if (JSON.stringify(route.query) === JSON.stringify(query)) void load();
  else void router.replace({ query });
}
function resetFilters() {
  if (!Object.keys(route.query).length) void load();
  else void router.replace({ query: {} });
}
function changePage(page: number) {
  void router.replace({ query: { ...route.query, page: String(page) } });
}
watch(() => route.query, load, { immediate: true });
void loadCategories();
</script>

<template>
  <section class="workspace-page">
    <header class="page-head">
      <div>
        <p class="section-kicker">PIRKUMI UN TO STĀSTI</p>
        <h1>Mani produkti</h1>
        <p class="muted">
          Apvieno katra produkta čekus un garantijas vienā vietā.
        </p>
      </div>
      <RouterLink to="/app/products/new" class="action-primary"
        ><i class="mdi mdi-plus" aria-hidden="true" /> Pievienot
        produktu</RouterLink
      >
    </header>
    <p v-if="route.query.deleted" class="success-message" role="status">
      Produkts ir izdzēsts. Tā dokumenti ir saglabāti.
    </p>
    <form class="panel products-filter" @submit.prevent="applyFilters">
      <div class="filter-grid">
        <label class="field product-search"
          >Meklēt produktos<input
            v-model="filters.search"
            type="search"
            maxlength="255"
            placeholder="Produkta nosaukums…" /></label
        ><label class="field"
          >Kategorija<select v-model="filters.category_id">
            <option value="">Visas kategorijas</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="String(category.id)"
            >
              {{ category.name }}
            </option>
          </select></label
        ><label class="field"
          >Veikals<input
            v-model="filters.merchant"
            maxlength="255"
            placeholder="Visi veikali" /></label
        ><label class="field"
          >Kārtot pēc<select v-model="filters.sort">
            <option value="created_at">Pievienošanas datuma</option>
            <option value="name">Nosaukuma</option>
            <option value="amount">Summas</option>
            <option value="purchase_date">Pirkuma datuma</option>
          </select></label
        ><label class="field"
          >Secība<select v-model="filters.direction">
            <option value="desc">Dilstoša</option>
            <option value="asc">Augoša</option>
          </select></label
        >
        <div class="page-actions filter-actions">
          <button
            v-if="filters.search || filters.category_id || filters.merchant"
            class="action-secondary"
            type="button"
            @click="resetFilters"
          >
            Notīrīt</button
          ><button class="action-primary" type="submit">Meklēt</button>
        </div>
      </div>
      <p v-if="lookupError" class="error-message" role="alert">
        {{ lookupError }}
        <button type="button" class="text-link" @click="loadCategories">
          Mēģināt vēlreiz
        </button>
      </p>
    </form>
    <section class="products-results" aria-live="polite" :aria-busy="loading">
      <div v-if="loading" class="panel empty-state">Ielādē produktus…</div>
      <div v-else-if="error" class="panel empty-state">
        <p class="error-message" role="alert">{{ error }}</p>
        <button class="action-secondary" type="button" @click="load">
          Mēģināt vēlreiz
        </button>
      </div>
      <div v-else-if="!response?.data.length" class="panel empty-state">
        <span class="empty-symbol"
          ><i class="mdi mdi-package-variant-closed" aria-hidden="true"
        /></span>
        <h2>
          {{
            filters.search || filters.category_id || filters.merchant
              ? "Produkti nav atrasti"
              : "Katram pirkumam sava vieta"
          }}
        </h2>
        <p>
          {{
            filters.search || filters.category_id || filters.merchant
              ? "Maini filtrus vai meklējamo tekstu."
              : "Pievieno produktu un piesaisti tam dokumentus."
          }}
        </p>
        <button
          v-if="filters.search || filters.category_id || filters.merchant"
          class="action-secondary"
          type="button"
          @click="resetFilters"
        >
          Notīrīt filtrus</button
        ><RouterLink v-else to="/app/products/new" class="action-primary"
          >Pievienot pirmo produktu</RouterLink
        >
      </div>
      <template v-else
        ><div class="results-summary">
          <h2>Tavi produkti</h2>
          <span class="muted">Kopā {{ response.total }}</span>
        </div>
        <div class="product-grid">
          <article
            v-for="product in response.data"
            :key="product.id"
            class="panel product-card"
          >
            <div class="product-card-top">
              <span class="product-icon"
                ><i
                  class="mdi mdi-package-variant-closed"
                  aria-hidden="true" /></span
              ><span class="chip">{{
                product.category?.name || "Bez kategorijas"
              }}</span>
            </div>
            <h3>
              <RouterLink :to="`/app/products/${product.id}`">{{
                product.name
              }}</RouterLink>
            </h3>
            <p class="product-merchant muted">
              {{ product.merchant || "Veikals nav norādīts" }}
            </p>
            <div class="product-info">
              <strong>{{ formatAmount(product.amount) }}</strong
              ><span class="muted">{{
                formatDate(product.purchase_date)
              }}</span>
            </div>
            <div class="product-card-footer">
              <span
                ><i class="mdi mdi-file-document-outline" aria-hidden="true" />
                {{ product.documents_count || 0 }}
                {{
                  product.documents_count === 1 ? "dokuments" : "dokumenti"
                }}</span
              ><RouterLink
                :to="`/app/products/${product.id}`"
                :aria-label="`Atvērt ${product.name}`"
                >Atvērt <i class="mdi mdi-arrow-top-right" aria-hidden="true"
              /></RouterLink>
            </div>
          </article>
        </div>
        <nav
          v-if="response.last_page > 1"
          class="pagination"
          aria-label="Produktu lapas"
        >
          <button
            class="action-secondary"
            :disabled="response.current_page <= 1"
            @click="changePage(response.current_page - 1)"
          >
            Iepriekšējā</button
          ><span>{{ response.current_page }} / {{ response.last_page }}</span
          ><button
            class="action-secondary"
            :disabled="response.current_page >= response.last_page"
            @click="changePage(response.current_page + 1)"
          >
            Nākamā
          </button>
        </nav></template
      >
    </section>
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
.section-kicker {
  font-size: 10px;
  letter-spacing: 0.16em;
  font-weight: 750;
  color: var(--app-accent, #3865ed);
  margin-bottom: 9px;
}
.products-filter {
  padding: 24px;
}
.products-filter .field {
  margin-bottom: 0;
}
.products-filter .page-actions {
  margin-top: 0;
}
.filter-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}
.filter-actions {
  align-self: end;
  justify-content: flex-end;
}
.text-link {
  color: var(--app-accent, #3865ed);
  text-decoration: underline;
}
.results-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin: 0 0 20px;
}
.results-summary h2 {
  font-size: 17px;
}
.results-summary span {
  font-size: 12px;
}
.product-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.product-card {
  padding: 24px;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
  min-width: 0;
}
.product-card:hover {
  border-color: var(--app-accent, #3865ed);
  box-shadow: 0 8px 30px #152a4410;
}
.product-card-top {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 23px;
}
.product-card-top .chip {
  font-size: 10px;
  overflow-wrap: anywhere;
}
.product-icon {
  display: grid;
  place-items: center;
  width: 45px;
  height: 45px;
  flex-shrink: 0;
  border-radius: 13px;
  background: var(--app-soft, #edf2ff);
  color: var(--app-accent, #3865ed);
  font-size: 25px;
}
.product-card h3 {
  font-size: 18px;
  font-weight: 650;
  line-height: 1.4;
  overflow-wrap: anywhere;
}
.product-card h3 a {
  color: inherit;
  text-decoration: none;
}
.product-merchant {
  font-size: 12px;
  margin: 8px 0 22px;
  overflow-wrap: anywhere;
}
.product-info {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}
.product-info strong {
  font-size: 19px;
}
.product-info span {
  font-size: 11px;
}
.product-card-footer {
  border-top: 1px solid var(--app-border, #e6eaf1);
  margin-top: 24px;
  padding-top: 17px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  font-size: 11px;
}
.product-card-footer > span {
  color: var(--app-muted, #708095);
}
.product-card-footer a {
  color: var(--app-accent, #3865ed);
  text-decoration: none;
  font-weight: 650;
  white-space: nowrap;
}
.empty-symbol {
  font-size: 36px;
  color: var(--app-accent, #3865ed);
}
.empty-state .action-primary,
.empty-state .action-secondary {
  margin-top: 14px;
}
.pagination {
  margin-top: 24px;
}
@media (max-width: 1200px) {
  .product-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 700px) {
  .filter-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .products-filter {
    padding: 20px;
  }
  .product-search {
    grid-column: 1/-1;
  }
  .filter-actions {
    grid-column: 1/-1;
  }
}
@media (max-width: 520px) {
  .product-grid {
    grid-template-columns: 1fr;
  }
  .product-card {
    padding: 22px;
  }
}
@media (prefers-reduced-motion: reduce) {
  .product-card {
    transition: none;
  }
}
</style>
