<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { api, ApiError, extractError } from "../../services/api";
import type { Category } from "../../types";
import DeleteConfirmation from "../../components/documents/DeleteConfirmation.vue";
import FieldError from "../../components/documents/FieldError.vue";

const categories = ref<Category[]>([]);
const loading = ref(true);
const error = ref("");
const success = ref("");
const search = ref("");
const showForm = ref(false);
const editTarget = ref<Category | null>(null);
const categoryName = ref("");
const saving = ref(false);
const formError = ref("");
const fieldErrors = ref<Record<string, string[]>>({});
const showDelete = ref(false);
const deleteTarget = ref<Category | null>(null);
const deleting = ref(false);
const deleteError = ref("");
const filtered = computed(() =>
  categories.value
    .filter((category) =>
      category.name
        .toLocaleLowerCase("lv")
        .includes(search.value.toLocaleLowerCase("lv")),
    )
    .sort((a, b) => a.name.localeCompare(b.name, "lv")),
);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    categories.value = (await api<{ data: Category[] }>("/categories")).data;
  } catch (cause) {
    error.value = extractError(cause);
  } finally {
    loading.value = false;
  }
}
function openForm(category: Category | null = null) {
  editTarget.value = category;
  categoryName.value = category?.name || "";
  formError.value = "";
  fieldErrors.value = {};
  showForm.value = true;
}
async function save() {
  if (saving.value) return;
  saving.value = true;
  formError.value = "";
  fieldErrors.value = {};
  success.value = "";
  try {
    const updated = await api<Category>(
      editTarget.value ? `/categories/${editTarget.value.id}` : "/categories",
      {
        method: editTarget.value ? "PATCH" : "POST",
        body: JSON.stringify({ name: categoryName.value.trim() }),
      },
    );
    const index = categories.value.findIndex(
      (category) => category.id === updated.id,
    );
    if (index === -1) categories.value.push(updated);
    else categories.value[index] = { ...categories.value[index], ...updated };
    success.value = editTarget.value
      ? "Kategorijas nosaukums ir mainīts."
      : "Kategorija ir izveidota.";
    showForm.value = false;
  } catch (cause) {
    formError.value = extractError(cause);
    if (cause instanceof ApiError) fieldErrors.value = cause.errors || {};
  } finally {
    saving.value = false;
  }
}
function openDelete(category: Category) {
  deleteTarget.value = category;
  deleteError.value = "";
  showDelete.value = true;
}
async function remove() {
  if (deleting.value || !deleteTarget.value) return;
  deleting.value = true;
  deleteError.value = "";
  success.value = "";
  try {
    await api(`/categories/${deleteTarget.value.id}`, {
      method: "DELETE",
      body: JSON.stringify({ confirmed: true }),
    });
    categories.value = categories.value.filter(
      (category) => category.id !== deleteTarget.value?.id,
    );
    success.value = "Kategorija ir izdzēsta.";
    showDelete.value = false;
  } catch (cause) {
    deleteError.value = extractError(cause);
  } finally {
    deleting.value = false;
  }
}
onMounted(load);
</script>

<template>
  <section class="workspace-page">
    <header class="page-head">
      <div>
        <p class="section-kicker">SAVA VIETA KATRAI LIETAI</p>
        <h1>Kategorijas</h1>
        <p class="muted">
          Sakārto dokumentus un produktus sev saprotamās grupās.
        </p>
      </div>
      <button class="action-primary" type="button" @click="openForm()">
        <i class="mdi mdi-plus" aria-hidden="true" /> Jauna kategorija
      </button>
    </header>
    <p v-if="success" class="success-message" role="status">{{ success }}</p>
    <div v-if="loading" class="panel empty-state" role="status">
      Ielādē kategorijas…
    </div>
    <div v-else-if="error" class="panel empty-state">
      <p class="error-message" role="alert">{{ error }}</p>
      <button class="action-secondary" type="button" @click="load">
        Mēģināt vēlreiz
      </button>
    </div>
    <div v-else-if="!categories.length" class="panel empty-state">
      <span class="empty-symbol"
        ><i class="mdi mdi-folder-plus-outline" aria-hidden="true"
      /></span>
      <h2>Izveido savu pirmo kategoriju</h2>
      <p>
        Piemēram, “Elektronika”, “Mājai” vai “Apģērbs”.<br />Pievienojot
        dokumentu, varēsi izvēlēties tam piemērotāko.
      </p>
      <button class="action-primary" type="button" @click="openForm()">
        Izveidot kategoriju
      </button>
    </div>
    <template v-else
      ><div class="category-toolbar">
        <span class="muted"
          >{{ categories.length }}
          {{ categories.length === 1 ? "kategorija" : "kategorijas" }}</span
        ><label class="field category-search"
          ><span class="visually-hidden">Meklēt kategoriju</span
          ><input
            v-model="search"
            type="search"
            placeholder="Meklēt kategoriju…"
        /></label>
      </div>
      <div v-if="!filtered.length" class="panel empty-state">
        <h2>Kategorijas nav atrastas</h2>
        <p>Izmēģini citu nosaukumu.</p>
        <button class="action-secondary" type="button" @click="search = ''">
          Notīrīt meklēšanu
        </button>
      </div>
      <div v-else class="category-grid">
        <article
          v-for="category in filtered"
          :key="category.id"
          class="panel category-card"
        >
          <div class="category-card-heading">
            <span class="category-icon"
              ><i class="mdi mdi-folder-outline" aria-hidden="true"
            /></span>
            <div class="category-actions">
              <button
                type="button"
                class="icon-button"
                :aria-label="`Pārdēvēt kategoriju ${category.name}`"
                :title="`Pārdēvēt ${category.name}`"
                @click="openForm(category)"
              >
                <i class="mdi mdi-pencil-outline" aria-hidden="true" /></button
              ><button
                type="button"
                class="icon-button delete-button"
                :aria-label="`Dzēst kategoriju ${category.name}`"
                :disabled="
                  Boolean(category.documents_count || category.products_count)
                "
                :title="
                  category.documents_count || category.products_count
                    ? 'Vispirms pārvieto dokumentus un produktus uz citu kategoriju.'
                    : 'Dzēst kategoriju'
                "
                @click="openDelete(category)"
              >
                <i class="mdi mdi-trash-can-outline" aria-hidden="true" />
              </button>
            </div>
          </div>
          <h2>{{ category.name }}</h2>
          <div class="category-counts">
            <RouterLink
              :to="{
                path: '/app/documents',
                query: { category_id: category.id },
              }"
              ><i class="mdi mdi-file-document-outline" aria-hidden="true" />
              {{ category.documents_count || 0 }}
              {{ category.documents_count === 1 ? "dokuments" : "dokumenti" }}
              <i class="mdi mdi-chevron-right" aria-hidden="true" /></RouterLink
            ><RouterLink
              :to="{
                path: '/app/products',
                query: { category_id: category.id },
              }"
              ><i class="mdi mdi-package-variant-closed" aria-hidden="true" />
              {{ category.products_count || 0 }}
              {{ category.products_count === 1 ? "produkts" : "produkti" }}
              <i class="mdi mdi-chevron-right" aria-hidden="true"
            /></RouterLink>
          </div>
          <p
            v-if="category.documents_count || category.products_count"
            class="category-note muted"
          >
            Lai dzēstu kategoriju, vispirms pārvieto tās saturu.
          </p>
          <p v-else class="category-note muted">Gatava pirmajam dokumentam.</p>
        </article>
      </div>
    </template>
    <v-dialog
      v-model="showForm"
      :persistent="saving"
      max-width="460"
      aria-labelledby="category-form-title"
      ><form class="category-form" @submit.prevent="save">
        <span class="category-icon"
          ><i class="mdi mdi-folder-outline" aria-hidden="true"
        /></span>
        <h2 id="category-form-title">
          {{ editTarget ? "Pārdēvēt kategoriju" : "Jauna kategorija" }}
        </h2>
        <p class="muted">Izvēlies īsu un saprotamu nosaukumu.</p>
        <label class="field"
          >Kategorijas nosaukums *<input
            v-model="categoryName"
            :aria-invalid="Boolean(fieldErrors.name)"
            required
            maxlength="100"
            autofocus
            :disabled="saving"
            placeholder="Piemēram, Elektronika" /><FieldError
            :messages="fieldErrors.name"
        /></label>
        <p v-if="formError" class="error-message" role="alert">
          {{ formError }}
        </p>
        <div class="page-actions">
          <button
            class="action-secondary"
            type="button"
            :disabled="saving"
            @click="showForm = false"
          >
            Atcelt</button
          ><button
            class="action-primary"
            type="submit"
            :disabled="saving || !categoryName.trim()"
          >
            {{ saving ? "Saglabā…" : "Saglabāt" }}
          </button>
        </div>
      </form></v-dialog
    >
    <DeleteConfirmation
      v-model="showDelete"
      title="Dzēst kategoriju?"
      :message="`Kategorija “${deleteTarget?.name || ''}” tiks neatgriezeniski dzēsta.`"
      :busy="deleting"
      :error="deleteError"
      @confirm="remove"
    />
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
.category-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
}
.category-toolbar > span {
  font-size: 13px;
}
.category-search {
  max-width: 280px;
  flex: 1;
  margin-bottom: 0;
}
.category-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 20px;
}
.category-card {
  padding: 24px;
}
.category-card-heading {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 23px;
}
.category-icon {
  display: grid;
  place-items: center;
  width: 47px;
  height: 47px;
  flex-shrink: 0;
  border-radius: 13px;
  color: var(--app-accent, #3865ed);
  background: var(--app-soft, #edf2ff);
  font-size: 26px;
}
.category-actions {
  display: flex;
  gap: 4px;
}
.icon-button {
  display: grid;
  place-items: center;
  width: 44px;
  height: 44px;
  border-radius: 9px;
  color: var(--app-muted, #708095);
  font-size: 19px;
}
.icon-button:hover:not(:disabled) {
  background: var(--app-soft, #edf2ff);
  color: var(--app-accent, #3865ed);
}
.delete-button:hover:not(:disabled) {
  background: #fce9e8;
  color: #b44440;
}
.icon-button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.category-card h2 {
  font-size: 18px;
  margin-bottom: 18px;
  overflow-wrap: anywhere;
}
.category-counts {
  display: grid;
  gap: 8px;
}
.category-counts a {
  display: flex;
  align-items: center;
  gap: 8px;
  text-decoration: none;
  color: var(--app-muted, #708095);
  font-size: 12px;
  padding: 5px 0;
}
.category-counts a:hover {
  color: var(--app-accent, #3865ed);
}
.category-counts a .mdi-chevron-right {
  margin-left: auto;
}
.category-note {
  font-size: 10px;
  line-height: 1.7;
  border-top: 1px solid var(--app-border, #e6eaf1);
  padding-top: 15px;
  margin-top: 16px;
}
.empty-symbol {
  font-size: 38px;
  color: var(--app-accent, #3865ed);
}
.empty-state p {
  line-height: 1.8;
}
.empty-state .action-primary {
  margin-top: 14px;
}
.category-form {
  max-height: 85dvh;
  overflow-y: auto;
  padding: 30px;
  border-radius: 20px;
  background: var(--app-surface, #fff);
  color: var(--app-text, #14253d);
}
.category-form h2 {
  font-size: 23px;
  margin: 20px 0 9px;
}
.category-form > p {
  font-size: 13px;
  line-height: 1.7;
  margin-bottom: 23px;
}
.category-form > .field {
  margin-bottom: 24px;
}
.category-form > .page-actions {
  justify-content: flex-end;
}
.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}
@media (max-width: 1150px) {
  .category-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
@media (max-width: 580px) {
  .category-grid {
    grid-template-columns: 1fr;
  }
  .category-card {
    padding: 22px;
  }
  .category-toolbar {
    gap: 12px;
  }
  .category-toolbar > span {
    white-space: nowrap;
  }
  .category-form {
    padding: 25px;
  }
}
</style>
