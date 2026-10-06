<script setup lang="ts">
withDefaults(
  defineProps<{
    modelValue: boolean;
    title: string;
    message: string;
    busy?: boolean;
    confirmLabel?: string;
  }>(),
  { confirmLabel: "Apstiprināt" },
);
defineEmits<{ "update:modelValue": [boolean]; confirm: [] }>();
</script>
<template>
  <v-dialog
    :model-value="modelValue"
    max-width="460"
    :persistent="busy"
    aria-labelledby="confirm-heading"
    @update:model-value="$emit('update:modelValue', $event)"
    ><v-card class="confirm-card"
      ><h2 id="confirm-heading">{{ title }}</h2>
      <p>{{ message }}</p>
      <slot />
      <div class="page-actions">
        <button
          class="action-secondary"
          :disabled="busy"
          @click="$emit('update:modelValue', false)"
        >
          Atcelt</button
        ><button
          class="action-danger"
          :disabled="busy"
          @click="$emit('confirm')"
        >
          {{ busy ? "Lūdzu, uzgaidi…" : confirmLabel }}
        </button>
      </div></v-card
    ></v-dialog
  >
</template>
