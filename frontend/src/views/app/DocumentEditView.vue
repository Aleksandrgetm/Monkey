<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { api, ApiError, extractError } from "../../services/api";
import type { Category, DocumentRecord, Product } from "../../types";
import {
  formatBytes,
  loadProductOptions,
} from "../../composables/useDocumentHelpers";
import FieldError from "../../components/documents/FieldError.vue";

const route = useRoute();
const router = useRouter();
const editing = computed(() => Boolean(route.params.id));
const categories = ref<Category[]>([]);
const products = ref<Product[]>([]);
const original = ref<DocumentRecord | null>(null);
const form = reactive({
  name: "",
  category_id: "",
  product_id: "",
  kind: "other",
  merchant: "",
  amount: "",
  purchase_date: "",
  warranty_end_date: "",
  note: "",
});
const file = ref<File | null>(null);
const loading = ref(true);
const busy = ref(false);
const loadError = ref("");
const error = ref("");
const fileError = ref("");
const errors = ref<Record<string, string[]>>({});
const categoryName = ref("");
const categoryBusy = ref(false);
const categoryError = ref("");
const categoryOpen = ref(false);
const success = ref("");
const backLocation = computed(() =>
  editing.value
    ? `/app/documents/${route.params.id}`
    : form.kind === "receipt"
      ? "/app/receipts"
      : form.kind === "warranty"
        ? "/app/warranties"
        : "/app/documents",
);
let loadId = 0;

async function load() {
  const token = ++loadId;
  loading.value = true;
  loadError.value = "";
  error.value = "";
  file.value = null;
  fileError.value = "";
  errors.value = {};
  try {
    const [categoryResponse, productResponse, document] = await Promise.all([
      api<{ data: Category[] }>("/categories"),
      loadProductOptions(),
      editing.value
        ? api<DocumentRecord>(`/documents/${route.params.id}`)
        : Promise.resolve(null),
    ]);
    if (token !== loadId) return;
    categories.value = categoryResponse.data;
    products.value = productResponse;
    original.value = document;
    Object.assign(form, {
      name: "",
      category_id: "",
      product_id: "",
      kind: "other",
      merchant: "",
      amount: "",
      purchase_date: "",
      warranty_end_date: "",
      note: "",
    });
    if (document) {
      for (const key of Object.keys(form) as (keyof typeof form)[])
        form[key] = document[key] == null ? "" : String(document[key]);
    } else {
      if (route.query.kind === "receipt" || route.query.kind === "warranty")
        form.kind = route.query.kind;
      if (
        typeof route.query.product_id === "string" &&
        products.value.some(
          (product) => product.id === Number(route.query.product_id),
        )
      ) {
        form.product_id = route.query.product_id;
        prefillProduct();
      }
    }
  } catch (cause) {
    if (token === loadId) loadError.value = extractError(cause);
  } finally {
    if (token === loadId) loading.value = false;
  }
}

function prefillProduct() {
  const product = products.value.find(
    (item) => item.id === Number(form.product_id),
  );
  if (!product) return;
  if (!form.name) form.name = product.name;
  if (!form.category_id) form.category_id = String(product.category_id);
  for (const key of [
    "merchant",
    "amount",
    "purchase_date",
    "warranty_end_date",
  ] as const) {
    if (!form[key] && product[key] != null) form[key] = String(product[key]);
  }
}

function selectFile(event: Event) {
  const input = event.target as HTMLInputElement;
  const selected = input.files?.[0];
  fileError.value = "";
  file.value = null;
  if (!selected) return;
  if (!["application/pdf", "image/jpeg", "image/png"].includes(selected.type))
    fileError.value = "Izvēlies PDF, JPEG vai PNG failu.";
  else if (selected.size >= 10 * 1024 * 1024)
    fileError.value = "Failam jābūt mazākam par 10 MB.";
  else if (selected.size === 0)
    fileError.value = "Fails ir tukšs. Izvēlies citu failu.";
  else if (Array.from(selected.name).length > 255)
    fileError.value = "Faila nosaukums nedrīkst pārsniegt 255 rakstzīmes.";
  if (fileError.value) input.value = "";
  else file.value = selected;
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
  errors.value = {};
  error.value = "";
  if (!editing.value && !file.value) {
    fileError.value = "Pievieno dokumenta failu.";
    return;
  }
  if (fileError.value) return;
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
  const body = new FormData();
  for (const [key, value] of Object.entries(form)) body.append(key, value);
  if (file.value) body.append("file", file.value);
  if (editing.value) body.append("_method", "PATCH");
  busy.value = true;
  try {
    const saved = await api<DocumentRecord>(
      editing.value ? `/documents/${route.params.id}` : "/documents",
      { method: "POST", body },
    );
    await router.push({
      path: `/app/documents/${saved.id}`,
      query: { saved: editing.value ? "updated" : "created" },
    });
  } catch (cause) {
    error.value = extractError(cause);
    if (cause instanceof ApiError) errors.value = cause.errors || {};
  } finally {
    busy.value = false;
  }
}

watch(() => [route.params.id, route.query.kind, route.query.product_id], load, {
  immediate: true,
});
</script>

<template>
  <section class="workspace-page document-editor">
    <RouterLink :to="backLocation" class="back-link"
      ><i class="mdi mdi-arrow-left" aria-hidden="true" /> Atpakaļ</RouterLink
    >
    <header class="page-head">
      <div>
        <h1>
          {{
            editing
              ? "Rediģēt dokumentu"
              : form.kind === "warranty"
                ? "Pievienot garantiju"
                : form.kind === "receipt"
                  ? "Pievienot čeku"
                  : "Pievienot dokumentu"
          }}
        </h1>
        <p class="muted">
          Saglabā failu un pirkuma informāciju savā privātajā mapē.
        </p>
      </div>
    </header>
    <div v-if="loading" class="panel empty-state" role="status">
      Ielādē formu…
    </div>
    <div v-else-if="loadError" class="panel empty-state">
      <p class="error-message" role="alert">{{ loadError }}</p>
      <button type="button" class="action-secondary" @click="load">
        Mēģināt vēlreiz
      </button>
    </div>
    <form v-else class="editor-grid" :aria-busy="busy" @submit.prevent="submit">
      <div class="editor-main">
        <section class="panel editor-panel">
          <div class="panel-heading">
            <span class="step-number">01</span>
            <div>
              <h2>Dokumenta fails</h2>
              <p class="muted">PDF, JPEG vai PNG · mazāks par 10 MB</p>
            </div>
          </div>
          <div class="upload-box">
            <i class="mdi mdi-cloud-upload-outline" aria-hidden="true" /><label
              for="document-file"
              >{{
                editing
                  ? "Nomainīt failu (nav obligāti)"
                  : "Izvēlies dokumenta failu *"
              }}</label
            ><input
              id="document-file"
              :aria-invalid="Boolean(fileError || errors.file)"
              type="file"
              accept="application/pdf,image/jpeg,image/png,.pdf,.jpg,.jpeg,.png"
              :required="!editing"
              :disabled="busy"
              @change="selectFile"
            />
            <p v-if="file" class="selected-file">
              {{ file.name }}
              <span class="muted">{{ formatBytes(file.size) }}</span>
            </p>
            <p v-else-if="original" class="muted">
              Pašreizējais fails: {{ original.file_name }} ·
              {{ formatBytes(original.file_size) }}
            </p>
            <p v-else class="muted">
              Nofofotografē čeku vai augšupielādē saglabātu failu.
            </p>
          </div>
          <p v-if="fileError" class="error-message" role="alert">
            {{ fileError }}
          </p>
          <FieldError :messages="errors.file" />
        </section>
        <section class="panel editor-panel">
          <div class="panel-heading">
            <span class="step-number">02</span>
            <div>
              <h2>Dokumenta informācija</h2>
              <p class="muted">Lauki ar * ir obligāti.</p>
            </div>
          </div>
          <div class="form-grid">
            <label class="field full-field"
              >Nosaukums<input
                v-model="form.name"
                :aria-invalid="Boolean(errors.name)"
                maxlength="255"
                placeholder="Piemēram, kafijas automāta čeks"
                :disabled="busy"
              /><FieldError :messages="errors.name" /><small class="muted"
                >Ja atstāsi tukšu, tiks parādīts faila nosaukums.</small
              ></label
            >
            <label class="field"
              >Dokumenta veids<select
                v-model="form.kind"
                :aria-invalid="Boolean(errors.kind)"
                :disabled="busy"
              >
                <option value="receipt">Čeks</option>
                <option value="warranty">Garantija</option>
                <option value="other">Cits dokuments</option></select
              ><FieldError :messages="errors.kind"
            /></label>
            <div class="field">
              <label for="document-category">Kategorija *</label
              ><select
                id="document-category"
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
            <div
              v-if="categoryOpen || !categories.length"
              class="inline-category full-field"
            >
              <label class="field"
                >Jaunas kategorijas nosaukums<input
                  v-model="categoryName"
                  maxlength="100"
                  :disabled="busy || categoryBusy"
                  placeholder="Piemēram, Elektronika"
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
            <label class="field full-field"
              >Saistītais produkts<select
                v-model="form.product_id"
                :aria-invalid="Boolean(errors.product_id)"
                :disabled="busy"
                @change="prefillProduct"
              >
                <option value="">Bez saistīta produkta</option>
                <option
                  v-for="product in products"
                  :key="product.id"
                  :value="String(product.id)"
                >
                  {{ product.name }}
                </option></select
              ><FieldError :messages="errors.product_id" /><small class="muted"
                >Produkta dati aizpildīs tikai tukšos laukus. Vari tos
                mainīt.</small
              ></label
            >
            <label class="field"
              >Veikals<input
                v-model="form.merchant"
                :aria-invalid="Boolean(errors.merchant)"
                maxlength="255"
                placeholder="Veikala nosaukums"
                :disabled="busy" /><FieldError :messages="errors.merchant"
            /></label>
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
            <label class="field"
              >Garantija derīga līdz<input
                v-model="form.warranty_end_date"
                :aria-invalid="Boolean(errors.warranty_end_date)"
                type="date"
                :min="form.purchase_date || undefined"
                :disabled="busy" /><FieldError
                :messages="errors.warranty_end_date"
            /></label>
            <label class="field full-field"
              >Piezīme<textarea
                v-model="form.note"
                :aria-invalid="Boolean(errors.note)"
                rows="4"
                maxlength="300"
                placeholder="Papildu informācija, ko vēlies paturēt prātā…"
                :disabled="busy"
              /><FieldError :messages="errors.note" /><small
                class="character-count muted"
                >{{ form.note.length }} / 300</small
              ></label
            >
          </div>
        </section>
        <p v-if="error" class="error-message" role="alert">{{ error }}</p>
        <div class="editor-actions">
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
                  : "Saglabāt dokumentu"
            }}
          </button>
        </div>
      </div>
      <aside class="panel upload-tip">
        <span class="tip-icon"
          ><i class="mdi mdi-shield-check-outline" aria-hidden="true"
        /></span>
        <h2>Vieta svarīgajam</h2>
        <p>Tavi faili ir privāti un pieejami pēc pieslēgšanās.</p>
        <hr />
        <h3>Lai vieglāk atrastu</h3>
        <p>Izvēlies kategoriju un pievieno atpazīstamu nosaukumu.</p>
        <h3>Nepalaid garām termiņu</h3>
        <p>
          Norādi garantijas beigu datumu, lai sistēma varētu atgādināt par tās
          beigām.
        </p>
      </aside>
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
.editor-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 270px;
  gap: 24px;
  align-items: start;
}
.editor-main {
  display: grid;
  gap: 24px;
  min-width: 0;
}
.editor-panel {
  padding: 28px;
}
.panel-heading {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 13px;
  margin-bottom: 24px;
  padding: 0;
}
.panel-heading h2 {
  font-size: 18px;
  margin-bottom: 4px;
}
.panel-heading p {
  font-size: 12px;
}
.step-number {
  display: grid;
  place-items: center;
  width: 35px;
  height: 35px;
  flex-shrink: 0;
  border-radius: 10px;
  background: var(--app-soft, #eef2fc);
  color: var(--app-accent, #3865ed);
  font-size: 12px;
  font-weight: 750;
}
.upload-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 13px;
  padding: 30px 20px;
  border: 1px dashed var(--app-border, #ccd6eb);
  border-radius: 14px;
  background: var(--app-soft, #f8faff);
  text-align: center;
  overflow-wrap: anywhere;
}
.upload-box > i {
  font-size: 38px;
  color: var(--app-accent, #3865ed);
}
.upload-box label {
  font-size: 14px;
  font-weight: 650;
}
.upload-box input {
  max-width: 100%;
  font-size: 12px;
}
.upload-box input::file-selector-button {
  background: var(--app-surface, #fff);
  border: 1px solid var(--app-border, #d9e0ed);
  border-radius: 8px;
  color: var(--app-text, #182b44);
  padding: 10px 14px;
  margin-right: 10px;
  cursor: pointer;
}
.upload-box p {
  font-size: 12px;
}
.selected-file {
  font-weight: 650;
}
.selected-file span {
  margin-left: 8px;
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
.editor-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
}
.upload-tip {
  padding: 26px;
  position: sticky;
  top: 100px;
}
.tip-icon {
  display: grid;
  place-items: center;
  width: 47px;
  height: 47px;
  border-radius: 13px;
  background: var(--app-soft, #edf2ff);
  color: var(--app-accent, #3865ed);
  font-size: 25px;
  margin-bottom: 19px;
}
.upload-tip h2 {
  font-size: 18px;
  margin-bottom: 10px;
}
.upload-tip p {
  font-size: 13px;
  line-height: 1.75;
  color: var(--app-muted, #708095);
}
.upload-tip h3 {
  font-size: 13px;
  margin: 20px 0 7px;
}
.upload-tip hr {
  border: 0;
  border-top: 1px solid var(--app-border, #e6eaf1);
  margin-top: 23px;
}
@media (max-width: 1100px) {
  .editor-grid {
    grid-template-columns: 1fr;
  }
  .upload-tip {
    position: static;
    display: none;
  }
}
@media (max-width: 600px) {
  .editor-panel {
    padding: 20px;
  }
  .upload-box {
    padding: 25px 14px;
  }
  .editor-actions {
    flex-wrap: wrap;
  }
  .editor-actions > * {
    flex: 1;
    text-align: center;
  }
}
</style>
