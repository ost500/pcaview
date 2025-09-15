<script setup lang="ts">
import { route } from 'ziggy-js';
</script>

<template>
    <div class="menubar-area style-2 footer-fixed">
        <div class="toolbar-inner menubar-nav">
            <a :href="route('home')" class="nav-link active">
                <i class="fa-solid fa-house"></i>
                <span>홈</span>
            </a>
            <a href="pages.html" class="nav-link">
                <i class="fa-solid fa-church"></i>
                <span>교회</span>
            </a>
            <a href="home-setting.html" class="nav-link">
                <i class="fa-solid fa-star"></i>
                <span>구독</span>
            </a>
            <a href="media.html" class="nav-link">
                <i class="fa-solid fa-person"></i>
                <span>프로필</span>
            </a>
        </div>
    </div>
</template>

<style scoped></style>
