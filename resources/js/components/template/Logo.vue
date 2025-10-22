<script setup lang="ts">
import { safeRoute } from '@/composables/useSafeRoute';

// SSR 환경에서 안전하게 route 사용
const homeUrl = safeRoute('home');
</script>

<template>
    <a :href="homeUrl">
        <img style="height: 50px" src="/jubogo_logo.png" alt="cplus logo">
    </a>
</template>

<style scoped>

</style>
