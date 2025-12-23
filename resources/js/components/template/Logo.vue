<script setup lang="ts">
import { safeRoute } from '@/composables/useSafeRoute';

// SSR 환경에서 안전하게 route 사용
const homeUrl = safeRoute('home');
</script>

<template>
    <a :href="homeUrl" class="flex items-center transition-opacity hover:opacity-80" aria-label="홈으로 이동">
        <img src="/jubogo_logo.png" alt="PCAview 로고" class="h-12 w-auto object-contain" />
    </a>
</template>

<style scoped>
/* Component styles are handled by Tailwind classes */
</style>
