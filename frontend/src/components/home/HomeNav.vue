<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useAuthStore } from '../../stores/auth'
import HomeBrand from './HomeBrand.vue'
import HomeIcon from './HomeIcon.vue'
defineEmits<{ start: [] }>()
const open = ref(false)
const auth = useAuthStore()
onMounted(() => { auth.fetchUser().catch(() => {}) })
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
<RouterLink class="login-link" :to="auth.user ? '/app' : '/login'">{{ auth.user ? 'Mana telpa' : 'Pieslēgties' }}</RouterLink>
<v-btn class="button button-dark nav-cta" variant="flat" @click="$emit('start')">{{ auth.user ? 'Atvērt lietotni' : 'Izveidot kontu' }} <HomeIcon name="arrow" :size="16" />
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
<RouterLink :to="auth.user ? '/app' : '/login'" @click="open = false">{{ auth.user ? 'Mana telpa' : 'Pieslēgties' }}</RouterLink>
<RouterLink v-if="!auth.user" to="/register" @click="open = false">Izveidot kontu</RouterLink>
</nav>
</header>
</template>
