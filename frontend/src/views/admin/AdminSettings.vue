<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { api, extractError } from "../../services/api";
const loaded = ref(false);
const form = reactive({ registration_enabled: true, reminder_days: 30 });
const loading = ref(true);
const busy = ref(false);
const error = ref("");
const success = ref("");
async function load() {
  loading.value = true;
  error.value = "";
  try {
    Object.assign(form, await api("/admin/settings"));
    loaded.value = true;
  } catch (e) {
    error.value = extractError(e);
  } finally {
    loading.value = false;
  }
}
async function save() {
  if (busy.value) return;
  busy.value = true;
  error.value = "";
  success.value = "";
  try {
    Object.assign(
      form,
      await api("/admin/settings", {
        method: "PATCH",
        body: JSON.stringify(form),
      }),
    );
    success.value = "Sistēmas iestatījumi saglabāti.";
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = false;
  }
}
onMounted(load);
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">ADMINISTRĒŠANA</p>
        <h1>Sistēmas iestatījumi</h1>
        <p class="muted">Reģistrācija un garantiju termiņu noklusējumi.</p>
      </div>
    </div>
    <p v-if="error" class="error-message" role="alert">
      {{ error }} <button @click="load">Mēģināt vēlreiz</button>
    </p>
    <p v-if="success" class="success-message" role="status">{{ success }}</p>
    <div v-if="loading" class="loading-state" role="status">
      Ielādē iestatījumus…
    </div>
    <form
      v-else-if="loaded"
      class="panel form-panel narrow-panel"
      @submit.prevent="save"
    >
      <label class="checkbox-field"
        ><input v-model="form.registration_enabled" type="checkbox" /> Atļaut
        jaunu kontu reģistrāciju</label
      ><label class="field"
        >Garantija drīz beigsies (dienu skaits)<input
          v-model.number="form.reminder_days"
          type="number"
          min="0"
          max="365"
          required
      /></label>
      <p class="muted">
        Nosaka sistēmas statusu “Drīz beigsies” un atgādinājuma periodu jauniem
        kontiem. Esošie lietotāji var izvēlēties savu paziņojumu periodu.
      </p>
      <button class="action-primary" :disabled="busy">
        {{ busy ? "Saglabā…" : "Saglabāt iestatījumus" }}
      </button>
    </form>
  </div>
</template>
