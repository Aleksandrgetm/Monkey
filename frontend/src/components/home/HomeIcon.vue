<script setup lang="ts">
withDefaults(defineProps<{ name: string; size?: number }>(), { size: 20 })
const paths: Record<string, string> = {
 scan: 'M8 3H4a1 1 0 0 0-1 1v4m13-5h4a1 1 0 0 1 1 1v4M3 16v4a1 1 0 0 0 1 1h4m8 0h4a1 1 0 0 0 1-1v-4M7 8h10M7 12h10M7 16h6',
 arrow: 'M4 12h16m-6-6 6 6-6 6', check: 'm5 12 4 4L19 6',
 file: 'M14 2H5v20h14V7l-5-5Zm0 0v6h5M8 12h8m-8 4h5',
 shield: 'm12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Zm-4 9 3 3 5-6',
 bell: 'M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M9 21h6',
 search: 'M21 21l-5-5M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0',
 folder: 'M3 5h6l2 3h10v12H3V5Z', upload: 'M12 16V3m-5 5 5-5 5 5M4 15v6h16v-6',
 grid: 'M3 3h7v7H3V3Zm11 0h7v7h-7V3ZM3 14h7v7H3v-7Zm11 0h7v7h-7v-7Z',
 laptop: 'M5 4h14v12H5V4ZM2 19h20l-3-3H5l-3 3Z', phone: 'M7 2h10v20H7V2Zm4 17h2',
 headphones: 'M4 14v-3a8 8 0 0 1 16 0v3M4 12H2v8h5v-8H4Zm16 0h2v8h-5v-8h3Z',
 tv: 'M2 4h20v14H2V4Zm6 18h8m-4-4v4', menu: 'M4 6h16M4 12h16M4 18h16', close: 'm6 6 12 12M6 18 18 6',
 lock: 'M5 10h14v11H5V10Zm3 0V6a4 4 0 0 1 8 0v4m-4 5v2', clock: 'M12 8v5l3 2M22 12a10 10 0 1 1-20 0 10 10 0 0 1 20 0',
}
</script>
<template>
<svg :width="size" :height="size" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
<path :d="paths[name] || paths.file" />
</svg>
</template>
