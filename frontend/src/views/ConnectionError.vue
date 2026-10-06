<script setup lang="ts">
import { useRoute, useRouter } from "vue-router";
const route = useRoute();
const router = useRouter();
function retry() {
  const next = String(route.query.next || "/app");
  router.replace(
    next.startsWith("/") && !next.startsWith("//") ? next : "/app",
  );
}
</script>
<template>
  <main class="standalone-state">
    <p class="eyebrow">SCAN & SAVE</p>
    <h1>Neizdevās sasniegt serveri.</h1>
    <p>Pārbaudi savienojumu un mēģini vēlreiz. Tavi dati nav mainīti.</p>
    <button class="action-primary" @click="retry">Mēģināt vēlreiz</button
    ><RouterLink class="action-secondary" to="/">Uz sākumlapu</RouterLink>
  </main>
</template>
