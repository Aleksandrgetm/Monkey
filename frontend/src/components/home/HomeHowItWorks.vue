<script setup lang="ts">import HomeIcon from './HomeIcon.vue'
const steps = [{ n:'01', icon:'upload', title:'Saglabā', text:'Pievieno čeku, garantiju vai pirkuma dokumentu. PDF, JPEG vai PNG — izvēlies sev ērtāko.', label:'No papīra uz digitālu' },{ n:'02', icon:'folder', title:'Sakārto', text:'Pirkuma summa, datums un dokumenti kopā. Katram pirkumam sava vieta un kategorija.', label:'Katram pirkumam sava vieta' },{ n:'03', icon:'bell', title:'Atceries', text:'Seko garantijas termiņam un saņem atgādinājumu, pirms tas tuvojas beigām.', label:'Svarīgais īstajā brīdī' }]
</script>
<template>
<section id="ka-tas-darbojas" class="section shell workflow">
<div class="section-heading reveal">
<div>
<p class="eyebrow">MAZS IERADUMS. LIELA ATŠĶIRĪBA.</p>
<h2>Viss svarīgais<br />vienuviet<span class="blue-text">.</span>
</h2>
</div>
<p>No jauna pirkuma līdz mierīgam prātam.<br />Tikai trīs vienkārši soļi.</p>
</div>
<div class="steps">
<div class="step-track" aria-hidden="true">
<div />
</div>
<article v-for="step in steps" :key="step.n" class="step reveal">
<div class="step-top">
<span class="step-number">{{ step.n }}</span>
<HomeIcon :name="step.icon" :size="26" />
</div>
<h3>{{ step.title }}</h3>
<p>{{ step.text }}</p>
<span class="step-label">{{ step.label }} <HomeIcon name="arrow" :size="15" />
</span>
</article>
</div>
</section>
</template>
