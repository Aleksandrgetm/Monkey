<script setup lang="ts">
import HomeIcon from './HomeIcon.vue'
import ReceiptCard from './ReceiptCard.vue'
defineEmits<{ start: [] }>()
</script>
<template>
<section class="hero shell" aria-labelledby="hero-title">
<div class="hero-copy">
<div class="eyebrow hero-enter">
<span class="tiny-dot" /> MAZĀK PAPĪRU. VAIRĀK MIERA.</div>
<h1 id="hero-title">
<span class="hero-line">Tavi pirkumi.</span>
<span class="hero-line">Tavi dokumenti.</span>
<span class="hero-line blue-text">Vienmēr ar tevi.</span>
</h1>
<p class="hero-description hero-enter">Čeki, garantijas un svarīgie pirkumu dokumenti.<br class="desktop-break" /> Vienā vietā. Tieši tad, kad tos vajag.</p>
<div class="hero-buttons hero-enter">
<v-btn class="button button-blue" variant="flat" @click="$emit('start')">Sākt bez maksas <HomeIcon name="arrow" :size="18" />
</v-btn>
<a class="text-link" href="#ka-tas-darbojas">Uzzināt vairāk <span aria-hidden="true">↗</span>
</a>
</div>
<div class="hero-note hero-enter">
<HomeIcon name="check" :size="15" /> Mazāk meklēšanas <span /> Vairāk pārliecības</div>
</div>
<div class="hero-art" role="img" aria-label="Papīra čeks kļūst par sakārtotu MacBook Air digitālo dokumentu ar garantijas termiņu">
<div class="orbit orbit-one" />
<div class="orbit orbit-two" />
<span class="art-coordinate">NO PAPĪRA UZ PĀRLIECĪBU</span>
<div class="hero-receipt">
<ReceiptCard />
</div>
<div class="transfer-node">
<HomeIcon name="arrow" :size="22" />
</div>
<div class="digital-card">
<div class="digital-top">
<span class="mini-file">
<HomeIcon name="file" :size="18" />
</span>
<span>PIRKUMA DOKUMENTS</span>
<HomeIcon name="check" :size="17" />
</div>
<div class="laptop-visual">
<div class="laptop-screen">
<div class="screen-ribbon ribbon-one"/>
<div class="screen-ribbon ribbon-two"/>
<div class="screen-ribbon ribbon-three"/>
</div>
<div class="laptop-base" />
</div>
<div class="digital-details">
<span class="small-muted">ELEKTRONIKA</span>
<h3>MacBook Air</h3>
<p>Apple · 13″ · Midnight</p>
<div class="digital-meta">
<div>
<span>Pirkuma summa</span>
<strong>1 199,00 €</strong>
</div>
<span class="status status-green">
<span /> Aktīva garantija</span>
</div>
<div class="warranty-meter">
<span />
</div>
<div class="warranty-date">
<HomeIcon name="shield" :size="14" /> Garantija līdz 30.09.2028.</div>
</div>
</div>
<div class="saved-toast">
<span class="saved-check">
<HomeIcon name="check" :size="20" />
</span>
<div>
<strong>Saglabāts. Sakārtots. Tavs.</strong>
<span>Viens papīrs mazāk, viena rūpe mazāk.</span>
</div>
</div>
<div class="art-caption">
<span class="tiny-dot" /> TAVS DIGITĀLAIS DOKUMENTU PLAUKTS</div>
</div>
<div class="hero-bottom">
<span>VISS PIRKUMA STĀSTS. VIENUVIET.</span>
<div>
<HomeIcon name="file" :size="17" /> Čeki <span class="divider" />
<HomeIcon name="shield" :size="17" /> Garantijas <span class="divider" />
<HomeIcon name="folder" :size="17" /> Dokumenti</div>
<a href="#ka-tas-darbojas" aria-label="Ritināt uz nākamo sadaļu">Ritini tālāk <span aria-hidden="true">↓</span>
</a>
</div>
</section>
</template>
