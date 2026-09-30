<script setup lang="ts">
import HomeIcon from './HomeIcon.vue'
import HomeBrand from './HomeBrand.vue'
const products = [ {name:'MacBook Air 13″', icon:'laptop', category:'Elektronika', date:'30.09.2026.', price:'1 199,00 €', status:'Aktīva', color:'green'}, {name:'iPhone 15',icon:'phone',category:'Elektronika',date:'12.08.2026.',price:'799,00 €',status:'Aktīva',color:'green'}, {name:'Samsung The Frame',icon:'tv',category:'Mājai',date:'30.10.2024.',price:'899,00 €',status:'Drīz beigsies',color:'amber'}, {name:'Sony WH-1000XM5',icon:'headphones',category:'Elektronika',date:'18.05.2024.',price:'329,00 €',status:'Beigusies',color:'gray'} ]
</script>
<template>
<section id="iespejas" class="showcase section">
<div class="shell">
<div class="section-heading reveal">
<div>
<p class="eyebrow">PĀRSKATĀMI. PĀRDOMĀTI. PA ROKAI.</p>
<h2>Mazāk haosa.<br />Vairāk pārskata.</h2>
</div>
<p>Visi tavi pirkumi un to dokumenti.<br />Viena skaidra vieta, kur visu atrast.</p>
</div>
<div class="app-window reveal" role="img" aria-label="Lietotnes priekšskatījums: četri pirkumi ar datumiem, summām un garantiju statusiem">
<aside class="app-sidebar">
<HomeBrand />
<span class="sidebar-label">TAVA TELPA</span>
<div class="sidebar-item selected">
<HomeIcon name="grid" :size="17" /> Pārskats</div>
<div v-for="item in [['file','Mani dokumenti'],['scan','Čeki'],['shield','Garantijas'],['folder','Produkti'],['bell','Atgādinājumi']]" :key="item[1]" class="sidebar-item">
<HomeIcon :name="item[0]!" :size="17" />{{ item[1] }}</div>
<div class="sidebar-bottom">
<span class="avatar">A</span>
<div>Aleksandrs<small>Personīgais konts</small>
</div>
</div>
</aside>
<div class="app-content">
<div class="app-toolbar">
<span>Mana telpa <span>/ Pārskats</span>
</span>
<span class="preview-label">LIETOTNES PRIEKŠSKATĪJUMS</span>
</div>
<div class="app-greeting">
<div>
<h3>Sveiks, Aleksandr! <span class="blue-text">✳</span>
</h3>
<p>Tavi pirkumi, sakārtoti un pa rokai.</p>
</div>
<span class="mock-add">+ Pievienot dokumentu</span>
</div>
<div class="app-stats">
<div>
<HomeIcon name="file" />
<span>Dokumenti</span>
<strong>24<small>viss vienuviet</small>
</strong>
</div>
<div>
<HomeIcon name="shield" />
<span>Aktīvas garantijas</span>
<strong>18<small class="green-text">viss kārtībā</small>
</strong>
</div>
<div>
<HomeIcon name="clock" />
<span>Drīz beigsies</span>
<strong>02<small class="amber-text">laiks pārbaudīt</small>
</strong>
</div>
</div>
<div class="app-table">
<div class="table-heading">
<h4>Pēdējie pirkumi</h4>
<span>Visi dokumenti ↗</span>
</div>
<div class="table-row table-labels">
<span>PRODUKTS</span>
<span>PIRKUMA DATUMS</span>
<span>SUMMA</span>
<span>GARANTIJA</span>
</div>
<div v-for="p in products" :key="p.name" class="table-row">
<div class="product-cell">
<span class="product-thumb">
<HomeIcon :name="p.icon" :size="25" />
</span>
<div>
<strong>{{ p.name }}</strong>
<small>{{ p.category }}</small>
</div>
</div>
<span class="table-date">{{ p.date }}</span>
<span class="table-price">{{ p.price }}</span>
<span :class="['status', `status-${p.color}`]">{{ p.status }}</span>
</div>
</div>
<div class="app-bottom">
<HomeIcon name="check" :size="14" /> Katra detaļa savā vietā.</div>
</div>
</div>
<p class="showcase-caption">Tā izskatās ikdiena ar mazāk papīriem. <span>Ilustratīvs lietotnes priekšskatījums.</span>
</p>
</div>
</section>
</template>
