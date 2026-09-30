<script setup lang="ts">import HomeIcon from './HomeIcon.vue'</script>
<template>
<div class="receipt-paper">
<HomeIcon name="scan" :size="27" />
<strong>PALDIES PAR PIRKUMU!</strong>
<span class="receipt-muted">Elektronikas veikals · Rīga</span>
<div class="receipt-rule" />
<div class="receipt-line">
<span>MacBook Air 13″</span>
<span>1 199,00</span>
</div>
<div class="receipt-line receipt-muted">
<span>1 gab. × 1 199,00 €</span>
</div>
<div class="receipt-rule" />
<div class="receipt-line receipt-total">
<span>KOPĀ</span>
<span>1 199,00 €</span>
</div>
<span class="receipt-muted">30.09.2026 · 14:32</span>
<div class="barcode" />
<span class="receipt-id">S S – 0 0 2 4 8 1</span>
</div>
</template>
