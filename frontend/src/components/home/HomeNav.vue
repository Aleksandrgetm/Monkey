<script setup lang="ts">
import { ref } from 'vue'
import HomeBrand from './HomeBrand.vue'
import HomeIcon from './HomeIcon.vue'
defineEmits<{ start: [] }>()
const open = ref(false)
</script>
<template>
<header class="site-header">
<div class="shell nav-inner">
<a href="#" aria-label="Scan & Save — sākums">
<HomeBrand />
</a>
<nav class="desktop-nav" aria-label="Galvenā navigācija">
<a href="#ka-tas-darbojas">Kā tas darbojas</a>
<a href="#iespejas">Iespējas</a>
<a href="#drosiba">Drošība</a>
</nav>
<div class="nav-actions">
<button class="login-link" @click="$emit('start')">Pieslēgties</button>
<v-btn class="button button-dark nav-cta" variant="flat" @click="$emit('start')">Izveidot kontu <HomeIcon name="arrow" :size="16" />
</v-btn>
<button class="menu-toggle" :aria-expanded="open" aria-controls="mobile-nav" :aria-label="open ? 'Aizvērt izvēlni' : 'Atvērt izvēlni'" @click="open = !open">
<HomeIcon :name="open ? 'close' : 'menu'" />
</button>
</div>
</div>
<nav v-if="open" id="mobile-nav" class="mobile-nav shell" aria-label="Mobilā navigācija" @keydown.esc="open = false">
<a href="#ka-tas-darbojas" @click="open = false">Kā tas darbojas</a>
<a href="#iespejas" @click="open = false">Iespējas</a>
<a href="#drosiba" @click="open = false">Drošība</a>
<button @click="open = false; $emit('start')">Pieslēgties / Izveidot kontu</button>
</nav>
</header>
</template>
