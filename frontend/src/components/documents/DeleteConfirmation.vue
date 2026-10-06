<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    message: string;
    busy?: boolean;
    error?: string;
  }>(),
  { busy: false, error: "" },
);
defineEmits<{ "update:modelValue": [value: boolean]; confirm: [] }>();
</script>

<template>
  <v-dialog
    :model-value="modelValue"
    :persistent="busy"
    max-width="460"
    aria-labelledby="delete-confirmation-title"
    @update:model-value="$emit('update:modelValue', $event)"
  >
    <section class="confirm-card">
      <span class="confirm-icon" aria-hidden="true"
        ><i class="mdi mdi-delete-outline"
      /></span>
      <h2 id="delete-confirmation-title">{{ title }}</h2>
      <p>{{ message }}</p>
      <p v-if="error" class="error-message" role="alert">{{ error }}</p>
      <div class="confirm-actions">
        <button
          class="action-secondary"
          type="button"
          :disabled="busy"
          @click="$emit('update:modelValue', false)"
        >
          Atcelt
        </button>
        <button
          class="action-danger"
          type="button"
          :disabled="busy"
          @click="$emit('confirm')"
        >
          {{ busy ? "Dzēš…" : "Dzēst" }}
        </button>
      </div>
    </section>
  </v-dialog>
</template>

<style scoped>
.confirm-card {
  padding: 30px;
  border-radius: 20px;
  background: var(--app-surface, #fff);
  color: var(--app-text, #14253d);
}
.confirm-card h2 {
  font-size: 23px;
  line-height: 1.3;
  margin: 18px 0 10px;
}
.confirm-card p {
  line-height: 1.65;
  color: var(--app-muted, #647187);
}
.confirm-icon {
  display: grid;
  place-items: center;
  width: 48px;
  height: 48px;
  background: #fce9e8;
  color: #ac3e39;
  border-radius: 14px;
  font-size: 24px;
}
.confirm-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 25px;
  flex-wrap: wrap;
}
</style>
