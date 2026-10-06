<script setup lang="ts">
import { computed, reactive, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useAuthStore } from "../../stores/auth";
import { api, ApiError, extractError } from "../../services/api";
import HomeBrand from "../../components/home/HomeBrand.vue";
import HomeIcon from "../../components/home/HomeIcon.vue";
const props = defineProps<{ mode: string }>();
const auth = useAuthStore();
const route = useRoute();
const router = useRouter();
const form = reactive({
  name: "",
  email: String(route.query.email || ""),
  password: "",
  password_confirmation: "",
  remember: false,
});
const busy = ref(false);
const error = ref("");
const success = ref("");
const errors = ref<Record<string, string[]>>({});
const visible = ref(false);
const title = computed(
  () =>
    ({
      login: "Prieks tevi atkal redzēt.",
      register: "Izveido savu kontu.",
      "forgot-password": "Atjauno piekļuvi.",
      "reset-password": "Izvēlies jaunu paroli.",
    })[props.mode],
);
const passwordNeeded = computed(() => props.mode !== "forgot-password");
watch(
  () => props.mode,
  () => {
    error.value = "";
    success.value = "";
    errors.value = {};
    form.password = "";
    form.password_confirmation = "";
  },
);
async function submit() {
  if (busy.value) return;
  busy.value = true;
  error.value = "";
  errors.value = {};
  success.value = "";
  try {
    if (props.mode === "login" || props.mode === "register") {
      if (props.mode === "login") await auth.login(form);
      else await auth.register(form);
      const redirect = String(route.query.redirect || "/app");
      await router.replace(
        redirect.startsWith("/") && !redirect.startsWith("//")
          ? redirect
          : "/app",
      );
    } else if (props.mode === "forgot-password") {
      await api("/auth/forgot-password", {
        method: "POST",
        body: JSON.stringify({ email: form.email }),
      });
      success.value =
        "Ja konts ar šo e-pastu pastāv, saņemsi saiti paroles atjaunošanai.";
    } else {
      await api("/auth/reset-password", {
        method: "POST",
        body: JSON.stringify({
          ...form,
          token: String(route.query.token || ""),
        }),
      });
      success.value = "Parole ir nomainīta. Tagad vari pieslēgties.";
      form.password = "";
      form.password_confirmation = "";
    }
  } catch (e) {
    error.value = extractError(e);
    if (e instanceof ApiError) errors.value = e.errors;
  } finally {
    busy.value = false;
  }
}
</script>
<template>
  <main class="auth-page">
    <aside class="auth-story">
      <RouterLink to="/"><HomeBrand /></RouterLink>
      <div>
        <p class="eyebrow">MAZĀK RAIŽU. VAIRĀK KĀRTĪBAS.</p>
        <h1>
          Tavi pirkumi.<br />Tavi dokumenti.<br /><span>Vienmēr ar tevi.</span>
        </h1>
        <p>Saglabā svarīgo un nepalaid garām nevienas garantijas beigas.</p>
        <ul>
          <li><HomeIcon name="folder" />Dokumenti vienuviet</li>
          <li><HomeIcon name="bell" />Savlaicīgi atgādinājumi</li>
          <li><HomeIcon name="search" />Viegli atrast vajadzīgo</li>
        </ul>
      </div>
      <small>SCAN & SAVE · RADĪTS TAVAI IKDIENAI</small>
    </aside>
    <section class="auth-form-section">
      <RouterLink class="auth-back" to="/">← Uz sākumlapu</RouterLink>
      <div class="auth-form-wrap">
        <p class="eyebrow">
          {{ mode === "register" ? "LAIPNI LŪDZAM" : "TAVA DIGITĀLĀ TELPA" }}
        </p>
        <h2>{{ title }}</h2>
        <p class="muted">
          {{
            mode === "register"
              ? "Sāc ar sakārtotiem pirkumu dokumentiem."
              : mode === "login"
                ? "Pieslēdzies, lai piekļūtu saviem dokumentiem."
                : "Ievadi konta e-pastu, lai atjaunotu piekļuvi."
          }}
        </p>
        <div v-if="error" class="error-message" role="alert">{{ error }}</div>
        <div v-if="success" class="success-message" role="status">
          {{ success }}
          <RouterLink v-if="mode === 'reset-password'" to="/login"
            >Pieslēgties →</RouterLink
          >
        </div>
        <form @submit.prevent="submit">
          <label v-if="mode === 'register'" class="field"
            >Lietotājvārds<input
              v-model="form.name"
              required
              minlength="3"
              maxlength="30"
              autocomplete="username"
              :aria-invalid="!!errors.name"
            /><small>3–30 burti vai cipari.</small></label
          ><label class="field"
            >E-pasts<input
              v-model="form.email"
              type="email"
              required
              maxlength="30"
              autocomplete="email"
              :aria-invalid="!!errors.email"
          /></label>
          <div v-if="passwordNeeded" class="field">
            <label for="auth-password">Parole</label>
            <div class="password-input">
              <input
                id="auth-password"
                v-model="form.password"
                :type="visible ? 'text' : 'password'"
                required
                :minlength="mode === 'login' ? undefined : 5"
                :autocomplete="
                  mode === 'login' ? 'current-password' : 'new-password'
                "
                :aria-invalid="!!errors.password"
              /><button
                type="button"
                :aria-label="visible ? 'Paslēpt paroli' : 'Parādīt paroli'"
                :aria-pressed="visible"
                @click="visible = !visible"
              >
                {{ visible ? "Paslēpt" : "Rādīt" }}
              </button>
            </div>
            <small v-if="mode !== 'login'">Vismaz 5 rakstzīmes.</small>
          </div>
          <label
            v-if="mode === 'register' || mode === 'reset-password'"
            class="field"
            >Apstiprini paroli<input
              v-model="form.password_confirmation"
              :type="visible ? 'text' : 'password'"
              required
              minlength="5"
              autocomplete="new-password"
          /></label>
          <div v-if="mode === 'login'" class="auth-options">
            <label
              ><input v-model="form.remember" type="checkbox" /> Atcerēties
              mani</label
            ><RouterLink to="/forgot-password">Aizmirsi paroli?</RouterLink>
          </div>
          <button class="action-primary auth-submit" :disabled="busy">
            {{
              busy
                ? "Lūdzu, uzgaidi…"
                : mode === "login"
                  ? "Pieslēgties"
                  : mode === "register"
                    ? "Reģistrēties"
                    : mode === "forgot-password"
                      ? "Nosūtīt atjaunošanas saiti"
                      : "Saglabāt jauno paroli"
            }}<HomeIcon name="arrow" :size="18" />
          </button>
        </form>
        <p class="auth-switch">
          <template v-if="mode === 'register'"
            >Jau ir konts?
            <RouterLink to="/login">Pieslēgties</RouterLink></template
          ><template v-else-if="mode === 'login'"
            >Vēl nav konta?
            <RouterLink to="/register">Reģistrēties</RouterLink></template
          ><RouterLink v-else to="/login"
            >Atgriezties pie pieslēgšanās</RouterLink
          >
        </p>
      </div>
    </section>
  </main>
</template>
