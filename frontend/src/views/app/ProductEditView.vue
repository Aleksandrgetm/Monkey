<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, ApiError, extractError } from "../../services/api";
import type { Category, Product } from "../../types";
import FieldError from "../../components/documents/FieldError.vue";

const route = useRoute();
const router = useRouter();
const editing = computed(() => Boolean(route.params.id));
const categories = ref<Category[]>([]);
const form = reactive({
  name: "",
  category_id: "",
  merchant: "",
  amount: "",
  purchase_date: "",
  warranty_end_date: "",
  note: "",
});
const loading = ref(true);
const busy = ref(false);
const loadError = ref("");
const error = ref("");
const errors = ref<Record<string, string[]>>({});
const categoryName = ref("");
const categoryBusy = ref(false);
const categoryError = ref("");
const categoryOpen = ref(false);
const success = ref("");
const backLocation = computed(() =>
  editing.value ? `/app/products/${route.params.id}` : "/app/products",
);
let requestId = 0;
async function load() {
  const token = ++requestId;
  loading.value = true;
  loadError.value = "";
  error.value = "";
  errors.value = {};
  try {
    const [categoryResponse, product] = await Promise.all([
      api<{ data: Category[] }>("/categories"),
      editing.value
        ? api<Product>(`/products/${route.params.id}`)
        : Promise.resolve(null),
    ]);
    if (token !== requestId) return;
    categories.value = categoryResponse.data;
    Object.assign(form, {
      name: "",
      category_id: "",
      merchant: "",
      amount: "",
      purchase_date: "",
      warranty_end_date: "",
      note: "",
    });
    if (product)
      for (const key of Object.keys(form) as (keyof typeof form)[])
        form[key] = product[key] == null ? "" : String(product[key]);
  } catch (cause) {
    if (token === requestId) loadError.value = extractError(cause);
  } finally {
    if (token === requestId) loading.value = false;
  }
}
async function createCategory() {
  if (busy.value || categoryBusy.value || !categoryName.value.trim()) return;
  categoryBusy.value = true;
  categoryError.value = "";
  try {
    const category = await api<Category>("/categories", {
      method: "POST",
      body: JSON.stringify({ name: categoryName.value.trim() }),
    });
    categories.value.push(category);
    form.category_id = String(category.id);
    categoryName.value = "";
    categoryOpen.value = false;
    success.value = "Kategorija ir izveidota.";
  } catch (cause) {
    categoryError.value = extractError(cause);
  } finally {
    categoryBusy.value = false;
  }
}
async function submit() {
  if (busy.value) return;
  error.value = "";
  errors.value = {};
  if (
    form.purchase_date &&
    form.warranty_end_date &&
    form.warranty_end_date < form.purchase_date
  ) {
    errors.value.warranty_end_date = [
      "Garantijas termiņš nevar būt pirms pirkuma datuma.",
    ];
    return;
  }
  busy.value = true;
  try {
    const payload = Object.fromEntries(
      Object.entries(form).map(([key, value]) => [
        key,
        value === "" ? null : value,
      ]),
    );
    const product = await api<Product>(
      editing.value ? `/products/${route.params.id}` : "/products",
      {
        method: editing.value ? "PATCH" : "POST",
        body: JSON.stringify(payload),
      },
    );
    await router.push({
      path: `/app/products/${product.id}`,
      query: { saved: editing.value ? "updated" : "created" },
    });
  } catch (cause) {
    error.value = extractError(cause);
    if (cause instanceof ApiError) errors.value = cause.errors || {};
  } finally {
    busy.value = false;
  }
}
watch(() => route.params.id, load, { immediate: true });
</script>

<template>
  <section class="workspace-page product-editor">
    <RouterLink :to="backLocation" class="back-link"
      ><i class="mdi mdi-arrow-left" aria-hidden="true" /> Atpakaļ</RouterLink
    >
    <header class="page-head">
      <div>
        <h1>{{ editing ? "Rediģēt produktu" : "Pievienot produktu" }}</h1>
        <p class="muted">Viena vieta pirkuma informācijai un tā dokumentiem.</p>
      </div>
    </header>
    <div v-if="loading" class="panel empty-state" role="status">
      Ielādē formu…
    </div>
    <div v-else-if="loadError" class="panel empty-state">
      <p class="error-message" role="alert">{{ loadError }}</p>
      <button class="action-secondary" type="button" @click="load">
        Mēģināt vēlreiz
      </button>
    </div>
    <form
      v-else
      class="panel product-form"
      :aria-busy="busy"
      @submit.prevent="submit"
    >
      <div class="form-heading">
        <span class="product-icon"
          ><i class="mdi mdi-package-variant-closed" aria-hidden="true"
        /></span>
        <div>
          <h2>Produkta informācija</h2>
          <p class="muted">Lauki ar * ir obligāti.</p>
        </div>
      </div>
      <div class="form-grid">
        <label class="field full-field"
          >Produkta nosaukums *<input
            v-model="form.name"
            :aria-invalid="Boolean(errors.name)"
            required
            maxlength="255"
            placeholder="Piemēram, DeLonghi kafijas automāts"
            :disabled="busy" /><FieldError :messages="errors.name"
        /></label>
        <div class="field">
          <label for="product-category">Kategorija *</label
          ><select
            id="product-category"
            v-model="form.category_id"
            :aria-invalid="Boolean(errors.category_id)"
            required
            :disabled="busy"
          >
            <option value="" disabled>Izvēlies kategoriju</option>
            <option
              v-for="category in categories"
              :key="category.id"
              :value="String(category.id)"
            >
              {{ category.name }}
            </option></select
          ><FieldError :messages="errors.category_id" /><button
            class="text-button"
            type="button"
            :disabled="busy"
            @click="categoryOpen = !categoryOpen"
          >
            {{ categoryOpen ? "Aizvērt" : "+ Izveidot kategoriju" }}
          </button>
        </div>
        <label class="field"
          >Veikals<input
            v-model="form.merchant"
            :aria-invalid="Boolean(errors.merchant)"
            maxlength="255"
            placeholder="Veikala nosaukums"
            :disabled="busy" /><FieldError :messages="errors.merchant"
        /></label>
        <div
          v-if="categoryOpen || !categories.length"
          class="inline-category full-field"
        >
          <label class="field"
            >Jaunas kategorijas nosaukums<input
              v-model="categoryName"
              maxlength="100"
              :disabled="busy || categoryBusy"
              placeholder="Piemēram, Sadzīves tehnika"
              @keydown.enter.prevent="createCategory" /></label
          ><button
            class="action-secondary"
            type="button"
            :disabled="busy || categoryBusy || !categoryName.trim()"
            @click="createCategory"
          >
            {{ categoryBusy ? "Veido…" : "Izveidot" }}
          </button>
          <p v-if="categoryError" class="error-message" role="alert">
            {{ categoryError }}
          </p>
        </div>
        <p v-if="success" class="success-message full-field" role="status">
          {{ success }}
        </p>
        <label class="field"
          >Summa (€)<input
            v-model="form.amount"
            :aria-invalid="Boolean(errors.amount)"
            type="number"
            min="0"
            max="99999.99"
            step="0.01"
            inputmode="decimal"
            placeholder="0,00"
            :disabled="busy"
          /><FieldError :messages="errors.amount" /><small class="muted"
            >Līdz 99 999,99 €.</small
          ></label
        >
        <label class="field"
          >Pirkuma datums<input
            v-model="form.purchase_date"
            :aria-invalid="Boolean(errors.purchase_date)"
            type="date"
            :max="form.warranty_end_date || undefined"
            :disabled="busy" /><FieldError :messages="errors.purchase_date"
        /></label>
        <label class="field full-field"
          >Garantija derīga līdz<input
            v-model="form.warranty_end_date"
            :aria-invalid="Boolean(errors.warranty_end_date)"
            type="date"
            :min="form.purchase_date || undefined"
            :disabled="busy"
          /><FieldError :messages="errors.warranty_end_date" /><small
            class="muted"
            >Šis datums palīdz aizpildīt jaunu dokumentu. Atgādinājumus nosaka
            dokumenta garantijas termiņš.</small
          ></label
        >
        <label class="field full-field"
          >Piezīme<textarea
            v-model="form.note"
            :aria-invalid="Boolean(errors.note)"
            maxlength="300"
            rows="4"
            placeholder="Papildu informācija par produktu…"
            :disabled="busy"
          /><FieldError :messages="errors.note" /><small
            class="muted character-count"
            >{{ form.note.length }} / 300</small
          ></label
        >
      </div>
      <p v-if="error" class="error-message" role="alert">{{ error }}</p>
      <div class="form-footer">
        <p class="muted">
          Čekus un garantijas varēsi pievienot pēc saglabāšanas.
        </p>
        <div class="page-actions">
          <RouterLink
            :to="backLocation"
            class="action-secondary"
            :aria-disabled="busy"
            @click="busy && $event.preventDefault()"
            >Atcelt</RouterLink
          ><button
            class="action-primary"
            type="submit"
            :disabled="busy || categoryBusy"
          >
            {{
              busy
                ? "Saglabā…"
                : editing
                  ? "Saglabāt izmaiņas"
                  : "Saglabāt produktu"
            }}
          </button>
        </div>
      </div>
    </form>
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
.product-form {
  max-width: 880px;
  width: 100%;
  padding: 30px;
}
.form-heading {
  display: flex;
  align-items: center;
  gap: 15px;
  margin-bottom: 28px;
}
.form-heading h2 {
  font-size: 18px;
  margin-bottom: 5px;
}
.form-heading p {
  font-size: 12px;
}
.product-icon {
  width: 46px;
  height: 46px;
  display: grid;
  place-items: center;
  border-radius: 13px;
  background: var(--app-soft, #edf2ff);
  color: var(--app-accent, #3865ed);
  font-size: 25px;
}
.full-field {
  grid-column: 1/-1;
}
.text-button {
  font-size: 12px;
  text-align: left;
  color: var(--app-accent, #3865ed);
  width: max-content;
  margin-top: 4px;
}
.inline-category {
  display: flex;
  align-items: flex-end;
  gap: 12px;
  flex-wrap: wrap;
  background: var(--app-soft, #f4f7ff);
  border-radius: 12px;
  padding: 16px;
}
.inline-category .field {
  flex: 1;
  min-width: 150px;
  margin-bottom: 0;
}
.inline-category .error-message {
  flex-basis: 100%;
}
.character-count {
  text-align: right;
}
.form-footer {
  border-top: 1px solid var(--app-border, #e6eaf1);
  margin-top: 28px;
  padding-top: 24px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 24px;
}
.form-footer > p {
  max-width: 270px;
  font-size: 12px;
  line-height: 1.7;
}
.form-footer .page-actions {
  flex-shrink: 0;
  margin-top: 0;
}
@media (max-width: 700px) {
  .product-form {
    padding: 22px;
  }
  .form-footer {
    align-items: stretch;
    flex-direction: column;
  }
  .form-footer > p {
    max-width: none;
  }
  .form-footer .page-actions {
    justify-content: flex-end;
  }
}
@media (max-width: 420px) {
  .form-footer .page-actions > * {
    flex: 1;
    text-align: center;
  }
}
</style>
