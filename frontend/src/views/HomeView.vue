<script setup lang="ts">
import { ref } from 'vue'
import HomeNav from '../components/home/HomeNav.vue'
import HomeHero from '../components/home/HomeHero.vue'
import HomeHowItWorks from '../components/home/HomeHowItWorks.vue'
import HomeProductShowcase from '../components/home/HomeProductShowcase.vue'
import HomeWarrantyStory from '../components/home/HomeWarrantyStory.vue'
import HomeFeatures from '../components/home/HomeFeatures.vue'
import HomeClosing from '../components/home/HomeClosing.vue'
import { useHomeMotion } from '../composables/useHomeMotion'
const root = ref<HTMLElement | null>(null)
const showNotice = ref(false)
useHomeMotion(root)
</script>
<template><div ref="root"><a class="skip-link" href="#main">Pāriet uz saturu</a><HomeNav @start="showNotice = true" /><main id="main"><HomeHero @start="showNotice = true" /><HomeHowItWorks /><HomeProductShowcase /><HomeWarrantyStory /><HomeFeatures /><HomeClosing @start="showNotice = true" /></main><v-dialog v-model="showNotice" max-width="440" aria-labelledby="notice-title" aria-describedby="notice-description"><v-card class="notice-card"><p class="eyebrow">SCAN & SAVE</p><h2 id="notice-title">Tiekamies drīzumā!</h2><p id="notice-description">Šobrīd vari iepazīt Scan & Save iespējas. Konta izveide un pieslēgšanās būs pieejama nākamajā projekta posmā.</p><v-btn class="button button-blue" variant="flat" @click="showNotice = false">Sapratu</v-btn></v-card></v-dialog></div></template>
