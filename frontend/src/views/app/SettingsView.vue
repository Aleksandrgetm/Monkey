<script setup lang="ts">
import { onMounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { api, downloadFile, extractError, resetCsrf } from "../../services/api";
import type { User } from "../../types";
import ConfirmDialog from "../../components/app/ConfirmDialog.vue";
const auth = useAuthStore();
const router = useRouter();
const loading = ref(true);
const busy = ref("");
const error = ref("");
const success = ref("");
const confirm = ref(false);
const profile = reactive({ name: "", email: "", current_password: "" });
const password = reactive({
  current_password: "",
  password: "",
  password_confirmation: "",
});
const preferences = reactive({
  email_notifications: true,
  in_app_notifications: true,
  reminder_days: 30,
  appearance: "light",
});
const deletePassword = ref("");
const loaded = ref(false);
const inheritReminder = ref(true);
async function load() {
  loading.value = true;
  error.value = "";
  try {
    const data = await api<{ user: User }>("/profile");
    auth.user = data.user;
    Object.assign(profile, { name: data.user.name, email: data.user.email });
    Object.assign(preferences, {
      email_notifications: data.user.email_notifications,
      in_app_notifications: data.user.in_app_notifications,
      reminder_days: data.user.reminder_days,
      appearance: data.user.appearance,
    });
    inheritReminder.value = data.user.reminder_days_override == null;
    loaded.value = true;
  } catch (e) {
    error.value = extractError(e);
  } finally {
    loading.value = false;
  }
}
async function save(
  type: "profile" | "password" | "preferences" | "appearance",
) {
  if (busy.value) return;
  busy.value = type;
  error.value = "";
  success.value = "";
  try {
    if (type === "password") {
      await api("/profile/password", {
        method: "PUT",
        body: JSON.stringify(password),
      });
      resetCsrf();
      Object.assign(password, {
        current_password: "",
        password: "",
        password_confirmation: "",
      });
    } else {
      const response = await api<{ user: User }>(
        type === "profile" ? "/profile" : "/profile/preferences",
        {
          method: "PATCH",
          body: JSON.stringify(
            type === "profile"
              ? profile
              : type === "appearance"
                ? { appearance: preferences.appearance }
                : {
                    email_notifications: preferences.email_notifications,
                    in_app_notifications: preferences.in_app_notifications,
                    reminder_days: inheritReminder.value
                      ? null
                      : preferences.reminder_days,
                  },
          ),
        },
      );
      auth.user = response.user;
      profile.current_password = "";
    }
    success.value =
      type === "password"
        ? "Parole veiksmīgi nomainīta."
        : "Izmaiņas saglabātas.";
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = "";
  }
}
async function remove() {
  busy.value = "delete";
  error.value = "";
  try {
    await api("/profile", {
      method: "DELETE",
      body: JSON.stringify({
        current_password: deletePassword.value,
        confirmed: true,
      }),
    });
    auth.clear();
    await router.replace("/");
  } catch (e) {
    error.value = extractError(e);
    confirm.value = false;
  } finally {
    busy.value = "";
  }
}
async function exportData() {
  busy.value = "export";
  error.value = "";
  try {
    await downloadFile("/profile/export", "scan-save-mani-dati.json");
    success.value = "Datu eksports lejupielādēts.";
  } catch (e) {
    error.value = extractError(e);
  } finally {
    busy.value = "";
  }
}
onMounted(load);
</script>
<template>
  <div class="workspace-page">
    <div class="page-head">
      <div>
        <p class="eyebrow">TAVI DOKUMENTI. TAVA TELPA.</p>
        <h1>Iestatījumi</h1>
        <p class="muted">Pārvaldi savu kontu un paziņojumus.</p>
      </div>
    </div>
    <p v-if="error" class="error-message" role="alert">
      {{ error }} <button v-if="!loaded" @click="load">Mēģināt vēlreiz</button>
    </p>
    <p v-if="success" class="success-message" role="status">{{ success }}</p>
    <div v-if="loading" class="loading-state" role="status">
      Ielādē iestatījumus…
    </div>
    <div v-else-if="loaded" class="settings-layout">
      <nav class="panel settings-nav" aria-label="Iestatījumu sadaļas">
        <a href="#profile">Profils</a><a href="#password">Drošība</a
        ><a href="#notifications">Paziņojumi</a><a href="#appearance">Izskats</a
        ><a href="#export">Datu eksportēšana</a
        ><a href="#delete" class="danger-text">Konta dzēšana</a>
      </nav>
      <div class="settings-panels">
        <section id="profile" class="panel form-panel">
          <h2>Personīgā informācija</h2>
          <p class="muted">Informācija, kas saistīta ar tavu kontu.</p>
          <form @submit.prevent="save('profile')">
            <div class="form-grid">
              <label class="field"
                >Lietotājvārds<input
                  v-model="profile.name"
                  required
                  minlength="3"
                  maxlength="30"
                  autocomplete="username" /></label
              ><label class="field"
                >E-pasts<input
                  v-model="profile.email"
                  required
                  type="email"
                  maxlength="30"
                  autocomplete="email"
              /></label>
            </div>
            <label v-if="profile.email !== auth.user?.email" class="field"
              >Pašreizējā parole, lai mainītu e-pastu<input
                v-model="profile.current_password"
                type="password"
                required
                autocomplete="current-password" /></label
            ><button class="action-primary" :disabled="!!busy">
              {{ busy === "profile" ? "Saglabā…" : "Saglabāt izmaiņas" }}
            </button>
          </form>
        </section>
        <section id="password" class="panel form-panel">
          <h2>Paroles maiņa</h2>
          <p class="muted">Izmanto paroli, kuru nelieto citur.</p>
          <form @submit.prevent="save('password')">
            <label class="field"
              >Pašreizējā parole<input
                v-model="password.current_password"
                type="password"
                required
                autocomplete="current-password"
            /></label>
            <div class="form-grid">
              <label class="field"
                >Jaunā parole<input
                  v-model="password.password"
                  type="password"
                  required
                  minlength="5"
                  autocomplete="new-password" /></label
              ><label class="field"
                >Apstiprini paroli<input
                  v-model="password.password_confirmation"
                  type="password"
                  required
                  minlength="5"
                  autocomplete="new-password"
              /></label>
            </div>
            <button class="action-primary" :disabled="!!busy">
              Mainīt paroli
            </button>
          </form>
        </section>
        <section id="notifications" class="panel form-panel">
          <h2>Paziņojumi</h2>
          <p class="muted">
            Izvēlies, kur un kad saņemt garantiju atgādinājumus.
          </p>
          <form @submit.prevent="save('preferences')">
            <label class="checkbox-field"
              ><input
                v-model="preferences.in_app_notifications"
                type="checkbox"
              />
              Paziņojumi lietotnē</label
            ><label class="checkbox-field"
              ><input
                v-model="preferences.email_notifications"
                type="checkbox"
              />
              Paziņojumi e-pastā</label
            ><label class="checkbox-field"
              ><input v-model="inheritReminder" type="checkbox" /> Izmantot
              sistēmas noklusējuma periodu</label
            ><label v-if="!inheritReminder" class="field"
              >Atgādināt pirms termiņa beigām (dienas)<input
                v-model.number="preferences.reminder_days"
                type="number"
                min="0"
                max="365"
                required /></label
            ><button class="action-primary" :disabled="!!busy">
              Saglabāt paziņojumus
            </button>
          </form>
        </section>
        <section id="appearance" class="panel form-panel">
          <h2>Izskats</h2>
          <form @submit.prevent="save('appearance')">
            <label class="field"
              >Lietotnes tēma<select v-model="preferences.appearance">
                <option value="light">Gaiša</option>
                <option value="dark">Tumša</option>
                <option value="system">Atbilstoši ierīcei</option>
              </select></label
            ><button class="action-primary" :disabled="!!busy">
              Saglabāt izskatu
            </button>
          </form>
        </section>
        <section id="export" class="panel form-panel">
          <h2>Datu eksportēšana</h2>
          <p class="muted">
            Lejupielādē sava profila, produktu, kategoriju un dokumentu
            informāciju JSON formātā. Pašus failus vari lejupielādēt dokumentu
            skatā.
          </p>
          <button
            class="action-secondary"
            :disabled="!!busy"
            @click="exportData"
          >
            Lejupielādēt manus datus
          </button>
        </section>
        <section id="delete" class="panel form-panel danger-panel">
          <h2>Konta dzēšana</h2>
          <p class="muted">
            Tavs konts, dokumenti, faili, produkti un paziņojumi tiks
            neatgriezeniski dzēsti.
          </p>
          <label class="field"
            >Pašreizējā parole<input
              v-model="deletePassword"
              type="password"
              autocomplete="current-password" /></label
          ><button
            class="action-danger"
            :disabled="!!busy || !deletePassword"
            @click="confirm = true"
          >
            Dzēst kontu
          </button>
        </section>
      </div>
    </div>
    <ConfirmDialog
      v-model="confirm"
      title="Vai dzēst kontu?"
      message="Visi konta dati un augšupielādētie faili tiks neatgriezeniski dzēsti. Šo darbību nevar atsaukt."
      confirm-label="Jā, dzēst kontu"
      :busy="busy === 'delete'"
      @confirm="remove"
    />
  </div>
</template>
