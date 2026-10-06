<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '../stores/auth'
import HomeNav from '../components/home/HomeNav.vue'
import HomeHero from '../components/home/HomeHero.vue'
import HomeHowItWorks from '../components/home/HomeHowItWorks.vue'
import HomeProductShowcase from '../components/home/HomeProductShowcase.vue'
import HomeWarrantyStory from '../components/home/HomeWarrantyStory.vue'
import HomeFeatures from '../components/home/HomeFeatures.vue'
import HomeClosing from '../components/home/HomeClosing.vue'
import { useHomeMotion } from '../composables/useHomeMotion'
const root = ref<HTMLElement | null>(null)
const router = useRouter()
const auth = useAuthStore()
const start = () => router.push(auth.user ? '/app' : '/register')
useHomeMotion(root)
</script>
<template><div ref="root"><a class="skip-link" href="#main">Pāriet uz saturu</a><HomeNav @start="start" /><main id="main"><HomeHero @start="start" /><HomeHowItWorks /><HomeProductShowcase /><HomeWarrantyStory /><HomeFeatures /><HomeClosing @start="start" /></main></div></template>
