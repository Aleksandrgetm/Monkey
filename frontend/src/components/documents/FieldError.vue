<script setup lang="ts">
defineProps<{ messages?: string[] }>();
</script>
<template>
  <small v-if="messages?.length" class="inline-field-error" role="alert">{{
    messages.join(" ")
  }}</small>
</template>
<style scoped>
.inline-field-error {
  display: block;
  color: #b44440;
  font-size: 12px;
  line-height: 1.5;
  margin-top: 4px;
}
</style>
