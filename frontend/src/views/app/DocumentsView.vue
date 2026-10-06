<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, extractError } from "../../services/api";
import type { Category, DocumentRecord, Paginated } from "../../types";
import { queryString } from "../../composables/useDocumentHelpers";
import DocumentTable from "../../components/documents/DocumentTable.vue";

const route = useRoute();
const router = useRouter();
const section = computed(() => String(route.meta.section || "documents"));
const headings: Record<string, { title: string; description: string }> = {
  documents: {
    title: "Mani dokumenti",
    description:
      "Visi svarīgie faili vienuviet. Sakārtoti un vienmēr pa rokai.",
  },
  receipts: {
    title: "Mani čeki",
    description: "Pirkumu pierādījumi, kuri nepazūd un neizbalē.",
  },
  warranties: {
    title: "Manas garantijas",
    description: "Seko termiņiem un saglabā garantijas dokumentus.",
  },
  search: {
    title: "Atrast dokumentu",
    description: "Meklē pēc nosaukuma, faila nosaukuma, veikala vai piezīmes.",
  },
};
const heading = computed(() => headings[section.value] || headings.documents!);
const fixedKind = computed(() =>
  section.value === "receipts"
    ? "receipt"
    : section.value === "warranties"
      ? "warranty"
      : "",
);
const filters = reactive({
  search: "",
  category_id: "",
  merchant: "",
  kind: "",
  warranty_status: "",
  date_from: "",
  date_to: "",
  sort: "created_at",
  direction: "desc",
});
const categories = ref<Category[]>([]);
const response = ref<Paginated<DocumentRecord> | null>(null);
const loading = ref(true);
const error = ref("");
const lookupError = ref("");
let requestId = 0;
const currentPage = computed(() => response.value?.current_page || 1);
const newLocation = computed(() => ({
  path: "/app/documents/new",
  query: fixedKind.value ? { kind: fixedKind.value } : {},
}));
const hasFilters = computed(() =>
  Object.entries(filters).some(
    ([key, value]) => !["sort", "direction"].includes(key) && value !== "",
  ),
);

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
    const result = await api<Paginated<DocumentRecord>>(
      `/documents?${queryString({ ...filters, kind: fixedKind.value || filters.kind, page: Number(route.query.page) || 1 })}`,
    );
    if (token === requestId) response.value = result;
  } catch (cause) {
    if (token === requestId) error.value = extractError(cause);
  } finally {
    if (token === requestId) loading.value = false;
  }
}
function applyFilters() {
  const query = Object.fromEntries(
    Object.entries(filters).filter(([, value]) => value !== ""),
  );
  if (fixedKind.value) delete query.kind;
  const nextQuery = { ...query, page: "1" };
  if (JSON.stringify(route.query) === JSON.stringify(nextQuery)) void load();
  else void router.replace({ path: route.path, query: nextQuery });
}
function resetFilters() {
  if (!Object.keys(route.query).length) void load();
  else void router.replace({ path: route.path, query: {} });
}
function changePage(page: number) {
  void router.replace({ query: { ...route.query, page: String(page) } });
}
watch(() => [route.path, route.query], load, { immediate: true });
void loadCategories();
</script>

<template>
  <section class="workspace-page">
    <header class="page-head">
      <div>
        <p class="section-kicker">TAVA DIGITĀLĀ MAPE</p>
        <h1>{{ heading.title }}</h1>
        <p class="muted">{{ heading.description }}</p>
      </div>
      <RouterLink :to="newLocation" class="action-primary"
        ><i class="mdi mdi-plus" aria-hidden="true" />{{
          section === "warranties"
            ? "Pievienot garantiju"
            : section === "receipts"
              ? "Pievienot čeku"
              : "Pievienot dokumentu"
        }}</RouterLink
      >
    </header>
    <p v-if="route.query.deleted" class="success-message" role="status">
      Dokuments ir izdzēsts.
    </p>
    <form class="panel filters-panel" @submit.prevent="applyFilters">
      <div class="search-line">
        <label class="field search-field" for="document-search"
          ><span>Meklēt dokumentos</span
          ><span class="search-input"
            ><i class="mdi mdi-magnify" aria-hidden="true" /><input
              id="document-search"
              v-model="filters.search"
              type="search"
              maxlength="255"
              placeholder="Nosaukums, veikals vai piezīme…" /></span></label
        ><button type="submit" class="action-primary">Meklēt</button>
      </div>
      <div class="document-filters">
        <label class="field"
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
        >
        <label class="field"
          >Veikals<input
            v-model="filters.merchant"
            maxlength="255"
            placeholder="Visi veikali"
        /></label>
        <label v-if="!fixedKind" class="field"
          >Dokumenta veids<select v-model="filters.kind">
            <option value="">Visi veidi</option>
            <option value="receipt">Čeks</option>
            <option value="warranty">Garantija</option>
            <option value="other">Cits dokuments</option>
          </select></label
        >
        <label class="field"
          >Garantijas statuss<select v-model="filters.warranty_status">
            <option value="">Visi statusi</option>
            <option value="active">Aktīva</option>
            <option value="expiring">Drīz beigsies</option>
            <option value="expired">Beigusies</option>
          </select></label
        >
        <label class="field"
          >Pirkums no<input
            v-model="filters.date_from"
            type="date"
            :max="filters.date_to || undefined"
        /></label>
        <label class="field"
          >Pirkums līdz<input
            v-model="filters.date_to"
            type="date"
            :min="filters.date_from || undefined"
        /></label>
      </div>
      <div class="filter-footer">
        <div class="sort-fields">
          <label class="field"
            >Kārtot pēc<select v-model="filters.sort">
              <option value="created_at">Pievienošanas datuma</option>
              <option value="name">Nosaukuma</option>
              <option value="amount">Summas</option>
              <option value="purchase_date">Pirkuma datuma</option>
              <option value="warranty_end_date">Garantijas termiņa</option>
            </select></label
          ><label class="field"
            >Secība<select v-model="filters.direction">
              <option value="desc">Dilstoša</option>
              <option value="asc">Augoša</option>
            </select></label
          >
        </div>
        <div class="page-actions">
          <button
            v-if="hasFilters"
            class="action-secondary"
            type="button"
            @click="resetFilters"
          >
            Notīrīt</button
          ><button class="action-secondary" type="submit">
            Lietot filtrus
          </button>
        </div>
      </div>
      <p v-if="lookupError" class="error-message" role="alert">
        {{ lookupError }}
        <button class="text-button" type="button" @click="loadCategories">
          Ielādēt kategorijas vēlreiz
        </button>
      </p>
    </form>
    <section
      class="panel results-panel"
      aria-live="polite"
      :aria-busy="loading"
    >
      <div class="results-header">
        <h2>
          {{
            section === "search" ? "Meklēšanas rezultāti" : "Dokumentu saraksts"
          }}
        </h2>
        <span v-if="response && !loading" class="count-label"
          >{{ response.total }}
          {{ response.total === 1 ? "dokuments" : "dokumenti" }}</span
        >
      </div>
      <div v-if="loading" class="empty-state">
        <i class="mdi mdi-loading loading-icon" aria-hidden="true" />
        <p>Ielādē dokumentus…</p>
      </div>
      <div v-else-if="error" class="empty-state">
        <p class="error-message" role="alert">{{ error }}</p>
        <button class="action-secondary" type="button" @click="load">
          Mēģināt vēlreiz
        </button>
      </div>
      <div v-else-if="!response?.data.length" class="empty-state">
        <span class="empty-symbol"
          ><i
            :class="
              hasFilters
                ? 'mdi mdi-file-search-outline'
                : 'mdi mdi-folder-open-outline'
            "
            aria-hidden="true"
        /></span>
        <h3>
          {{
            hasFilters ? "Nekas netika atrasts" : "Šeit sāksies tava kārtība"
          }}
        </h3>
        <p>
          {{
            hasFilters
              ? "Maini meklējamo tekstu vai filtrus un mēģini vēlreiz."
              : "Pievieno pirmo dokumentu, lai tas vienmēr būtu pa rokai."
          }}
        </p>
        <button
          v-if="hasFilters"
          type="button"
          class="action-secondary"
          @click="resetFilters"
        >
          Notīrīt filtrus</button
        ><RouterLink v-else :to="newLocation" class="action-primary"
          >Pievienot dokumentu</RouterLink
        >
      </div>
      <DocumentTable v-else :documents="response.data" />
      <nav
        v-if="response && response.last_page > 1 && !loading && !error"
        class="pagination"
        aria-label="Dokumentu lapas"
      >
        <button
          class="action-secondary"
          :disabled="currentPage <= 1"
          @click="changePage(currentPage - 1)"
        >
          Iepriekšējā</button
        ><span>{{ currentPage }} / {{ response.last_page }}</span
        ><button
          class="action-secondary"
          :disabled="currentPage >= response.last_page"
          @click="changePage(currentPage + 1)"
        >
          Nākamā
        </button>
      </nav>
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
.filters-panel {
  padding: 24px;
}
.filters-panel .field {
  margin-bottom: 0;
}
.filters-panel .page-actions {
  margin-top: 0;
}
.search-line {
  display: flex;
  align-items: flex-end;
  gap: 14px;
}
.search-field {
  flex: 1;
}
.search-input {
  position: relative;
  display: block;
}
.search-input i {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 22px;
  color: var(--app-muted, #708095);
}
.search-input input {
  padding-left: 43px;
  width: 100%;
}
.document-filters {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
  margin-top: 20px;
}
.filter-footer {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 16px;
  margin-top: 20px;
  padding-top: 20px;
  border-top: 1px solid var(--app-border, #e6eaf1);
}
.sort-fields {
  display: flex;
  gap: 14px;
}
.results-panel {
  padding: 0;
  overflow: hidden;
}
.results-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 24px;
}
.results-header h2 {
  font-size: 17px;
}
.count-label {
  font-size: 12px;
  color: var(--app-muted, #708095);
}
.empty-symbol {
  font-size: 34px;
  color: var(--app-accent, #3865ed);
}
.text-button {
  color: var(--app-accent, #3865ed);
  text-decoration: underline;
}
.loading-icon {
  font-size: 28px;
}
.pagination {
  padding: 20px;
}
.empty-state .action-primary,
.empty-state .action-secondary {
  margin-top: 12px;
}
@media (max-width: 700px) {
  .filters-panel {
    padding: 18px;
  }
  .document-filters {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
  .filter-footer {
    align-items: stretch;
    flex-direction: column;
  }
  .sort-fields > * {
    min-width: 0;
    flex: 1;
  }
  .filter-footer .page-actions {
    justify-content: flex-end;
  }
  .search-line {
    gap: 8px;
  }
  .search-line > .action-primary {
    padding-left: 14px;
    padding-right: 14px;
  }
  .results-header {
    padding: 20px;
  }
  .count-label {
    white-space: nowrap;
  }
}
@media (max-width: 380px) {
  .document-filters {
    grid-template-columns: 1fr;
  }
}
</style>
