<script setup lang="ts">import HomeIcon from './HomeIcon.vue'; import ReceiptCard from './ReceiptCard.vue'</script>
<template>
<section class="story section">
<div class="shell story-grid">
<div class="story-copy reveal">
<p class="eyebrow">PAPĪRS IZBALĒ. SVARĪGAIS PALIEK.</p>
<h2>Čeks nepazudīs.<br />
<span>Garantija netiks<br />aizmirsta.</span>
</h2>
<p>Atvilktnes, kabatas, kurpju kastes. Taviem dokumentiem ir labāka vieta.</p>
<p>Saglabā pirkuma stāstu digitāli — no pirmās dienas līdz garantijas pēdējai.</p>
<a class="text-link" href="#atgadinajumi">Parūpējies par nākotnes sevi <HomeIcon name="arrow" :size="18" />
</a>
</div>
<div class="story-art" aria-label="Čeks pārtop saglabātā dokumentā">
<div class="story-receipt">
<ReceiptCard />
</div>
<div class="story-connector" />
<div class="story-file">
<span class="story-file-icon">
<HomeIcon name="file" :size="38" />
</span>
<span class="small-muted">VIENS PIRKUMS. VISS KOPĀ.</span>
<h3>MacBook Air</h3>
<div>
<HomeIcon name="check" :size="16" /> Pirkuma čeks</div>
<div>
<HomeIcon name="check" :size="16" /> Garantijas dokuments</div>
<div>
<HomeIcon name="check" :size="16" /> Atgādinājums par termiņu</div>
<span class="story-file-bottom">
<HomeIcon name="shield" :size="17" /> Gatavs brīdim, kad vajadzēs.</span>
</div>
<span class="story-note">Mazāk papīra. Vairāk pārliecības.</span>
</div>
</div>
</section>
</template>
