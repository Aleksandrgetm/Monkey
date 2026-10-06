<script setup lang="ts">
defineProps<{ status?: string | null }>();
const labels: Record<string, string> = {
  active: "Aktīva",
  expiring: "Drīz beigsies",
  expired: "Beigusies",
};
</script>

<template>
  <span v-if="status" class="chip warranty-badge" :class="`warranty-${status}`"
    ><span aria-hidden="true" class="status-dot" />{{
      labels[status] || status
    }}</span
  >
  <span v-else class="muted">Nav termiņa</span>
</template>

<style scoped>
.warranty-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  white-space: nowrap;
  font-size: 12px;
  font-weight: 650;
  border: 1px solid transparent;
}
.warranty-active {
  background: #e7f5ee;
  color: #216545;
  border-color: #cce8d9;
}
.warranty-expiring {
  background: #fff3d6;
  color: #845b0b;
  border-color: #f2dfaf;
}
.warranty-expired {
  background: #fce9e8;
  color: #a13d38;
  border-color: #f1cecb;
}
.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 50%;
  background: currentColor;
}
</style>
