<script setup lang="ts">import HomeIcon from './HomeIcon.vue'; import HomeBrand from './HomeBrand.vue'; defineEmits<{start: []}>()</script>
<template>
<section id="drosiba" class="security shell reveal">
<div class="security-icon">
<HomeIcon name="lock" :size="30" />
</div>
<div>
<p class="eyebrow">TAVI DOKUMENTI. TAVA TELPA.</p>
<h2>Svarīgais pelna savu vietu.</h2>
<p>Personīgs konts, kur vienkopus glabāt pirkumu dokumentus.<br class="desktop-break" /> Skaidra kārtība un saikne starp pirkumu, čeku un garantiju.</p>
</div>
<span class="security-tag">
<HomeIcon name="folder" :size="18" /> Pārdomāta dokumentu kārtība</span>
</section>
<section class="final-cta shell reveal">
<span class="cta-cross cta-cross-one" aria-hidden="true">+</span>
<span class="cta-cross cta-cross-two" aria-hidden="true">+</span>
<p class="eyebrow">SAGLABĀ ŠODIEN. NOVĒRTĒ RĪT.</p>
<h2>Tavi dokumenti ir pārāk<br />svarīgi, lai tos pazaudētu.</h2>
<p>Dod katram pirkumam savu digitālo mājvietu.</p>
<v-btn class="button button-blue" variant="flat" @click="$emit('start')">Izveidot kontu <HomeIcon name="arrow" :size="18" />
</v-btn>
</section>
<footer class="shell footer">
<a href="#" aria-label="Scan & Save — sākums">
<HomeBrand />
</a>
<p>Mazāk papīru. Vairāk miera.</p>
<nav aria-label="Kājenes navigācija">
<a href="#ka-tas-darbojas">Kā tas darbojas</a>
<a href="#iespejas">Iespējas</a>
<a href="#drosiba">Drošība</a>
</nav>
<div class="footer-bottom">
<span>© 2026 Scan & Save</span>
<span>Radīts mācību projekta ietvaros · Rīgas Valsts tehnikums</span>
<span>LV</span>
</div>
</footer>
</template>
