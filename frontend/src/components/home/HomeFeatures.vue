<script setup lang="ts">import HomeIcon from './HomeIcon.vue'</script>
<template>
<section class="section shell features">
<div id="atgadinajumi" class="feature-panel reminder-panel reveal">
<p class="eyebrow">ATGĀDINĀJUMI</p>
<h2>Tu dzīvo.<br />Mēs atgādinām.</h2>
<p>Visi termiņi nav jāpatur galvā. Saņem atgādinājumu, kad garantija tuvojas beigām.</p>
<div class="notification">
<span class="notification-icon">
<HomeIcon name="bell" :size="23" />
</span>
<div>
<div class="notification-title">SCAN & SAVE <span>Tagad</span>
</div>
<strong>Laiks ieskatīties garantijā.</strong>
<p>MacBook Air garantija beigsies pēc <b>30 dienām.</b>
</p>
<span class="notification-link">Viss vajadzīgais jau vienuviet ↗</span>
</div>
</div>
<div class="reminder-timeline">
<span>Šodien</span>
<div>
<i />
</div>
<span>30 dienas</span>
</div>
</div>
<div class="feature-panel search-panel reveal">
<p class="eyebrow">MEKLĒŠANA UN KATEGORIJAS</p>
<h2>Atrodi sekundēs.<br />Nevis atvilktnēs.</h2>
<p>Pēc nosaukuma, kategorijas vai datuma. Īstais dokuments tieši tad, kad tas vajadzīgs.</p>
<div class="search-demo" role="img" aria-label="Meklēšanas piemērs: MacBook Air, kategorija Elektronika, aktīva garantija">
<div class="mock-search">
<HomeIcon name="search" :size="19" />
<span>MacBook</span>
<span class="search-shortcut">⌘ K</span>
</div>
<div class="filter-chips">
<span class="active">Elektronika</span>
<span>Aktīva garantija</span>
<span>2026</span>
</div>
<div class="search-result">
<span class="product-thumb">
<HomeIcon name="laptop" :size="29" />
</span>
<div>
<strong>MacBook Air 13″</strong>
<small>Pirkuma čeks · 30.09.2026.</small>
</div>
<HomeIcon name="arrow" :size="18" />
</div>
<div class="search-result-count">
<span class="tiny-dot" /> 1 dokuments. Tieši tas, ko meklēji.</div>
</div>
</div>
</section>
</template>
