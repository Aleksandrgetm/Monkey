<script setup lang="ts">
import type { DocumentRecord } from "../../types";
import {
  documentKinds,
  formatAmount,
  formatDate,
} from "../../composables/useDocumentHelpers";
import WarrantyBadge from "./WarrantyBadge.vue";
defineProps<{ documents: DocumentRecord[] }>();
</script>

<template>
  <div class="table-wrap">
    <table class="data-table document-table">
      <thead>
        <tr>
          <th scope="col">Dokuments</th>
          <th scope="col">Kategorija</th>
          <th scope="col">Summa</th>
          <th scope="col">Pirkuma datums</th>
          <th scope="col">Garantija</th>
          <th scope="col"><span class="visually-hidden">Darbības</span></th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="document in documents" :key="document.id">
          <td data-label="Dokuments">
            <RouterLink
              :to="`/app/documents/${document.id}`"
              class="document-link"
              ><span class="file-symbol" aria-hidden="true"
                ><i
                  :class="
                    document.file_type === 'application/pdf'
                      ? 'mdi mdi-file-pdf-box'
                      : 'mdi mdi-file-image-outline'
                  " /></span
              ><span class="file-title"
                ><strong>{{ document.name || document.file_name }}</strong
                ><small
                  >{{ documentKinds[document.kind]
                  }}<template v-if="document.merchant">
                    · {{ document.merchant }}</template
                  ></small
                ></span
              ></RouterLink
            >
          </td>
          <td data-label="Kategorija">{{ document.category?.name || "—" }}</td>
          <td data-label="Summa" class="amount-cell">
            {{ formatAmount(document.amount) }}
          </td>
          <td data-label="Pirkuma datums">
            {{ formatDate(document.purchase_date) }}
          </td>
          <td data-label="Garantija">
            <WarrantyBadge :status="document.warranty_status" /><small
              v-if="document.warranty_end_date"
              class="expiry-date"
              >Līdz {{ formatDate(document.warranty_end_date) }}</small
            >
          </td>
          <td class="row-action">
            <RouterLink
              :to="`/app/documents/${document.id}`"
              class="row-open"
              :aria-label="`Atvērt ${document.name || document.file_name}`"
              ><span>Atvērt</span
              ><i class="mdi mdi-arrow-top-right" aria-hidden="true"
            /></RouterLink>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<style scoped>
.document-link {
  display: flex;
  align-items: center;
  gap: 12px;
  color: inherit;
  text-decoration: none;
  min-width: 150px;
}
.document-link:hover strong {
  color: var(--app-accent, #3865ed);
}
.file-symbol {
  display: grid;
  place-items: center;
  flex: 0 0 38px;
  height: 42px;
  background: var(--app-soft, #edf1fc);
  border-radius: 10px;
  color: var(--app-accent, #3865ed);
  font-size: 23px;
}
.file-title {
  display: grid;
  gap: 5px;
  min-width: 0;
}
.file-title strong {
  font-weight: 650;
  max-width: 280px;
  overflow-wrap: anywhere;
}
.file-title small,
.expiry-date {
  font-size: 11px;
  color: var(--app-muted, #708095);
}
.expiry-date {
  display: block;
  margin-top: 6px;
}
.amount-cell {
  font-variant-numeric: tabular-nums;
  white-space: nowrap;
}
.row-open {
  display: inline-flex;
  min-height: 44px;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 650;
  color: var(--app-accent, #3865ed);
  text-decoration: none;
}
.row-action {
  text-align: right;
}
.visually-hidden {
  position: absolute;
  width: 1px;
  height: 1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
}
@media (max-width: 700px) {
  .document-table,
  .document-table tbody,
  .document-table tr,
  .document-table td {
    display: block;
    width: 100%;
    max-width: none;
  }
  .document-table thead {
    display: none;
  }
  .document-table tr {
    padding: 18px;
    border-bottom: 1px solid var(--app-border, #e6eaf1);
  }
  .document-table td {
    padding: 8px 0;
    border: 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 14px;
    text-align: right;
  }
  .document-table td::before {
    content: attr(data-label);
    font-size: 12px;
    color: var(--app-muted, #708095);
    text-align: left;
    flex-shrink: 0;
  }
  .document-table td:first-child {
    padding: 0 0 12px;
    text-align: left;
  }
  .document-table td:first-child::before,
  .document-table td:last-child::before {
    display: none;
  }
  .document-table td:nth-child(5) {
    flex-wrap: wrap;
  }
  .document-table td .document-link {
    width: 100%;
    justify-content: flex-start;
    text-align: left;
  }
  .file-title strong {
    max-width: none;
  }
  .document-table td:last-child {
    justify-content: flex-end;
    padding-bottom: 0;
  }
  .expiry-date {
    margin: 0;
  }
  .row-open {
    padding: 5px 0;
  }
}
</style>
