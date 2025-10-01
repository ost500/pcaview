<script setup lang="ts">
import { route } from 'ziggy-js';

function not_ready() {
    alert('준비중 입니다');
    FlutterAlert.postMessage('Flutter에서 표시되는 alert입니다!');
}
</script>

<template>
    <div class="menubar-area style-2 footer-fixed">
        <div class="toolbar-inner menubar-nav">
            <a :href="route('home')" class="nav-link" :class="{ active: route().current('home') || route().current('contents*') }">
                <i class="fa-solid fa-house"></i>
                <span>홈</span>
            </a>
            <a :href="route('church')" class="nav-link" :class="{ active: route().current('church*') }">
                <i class="fa-solid fa-church"></i>
                <span>교회</span>
            </a>
            <a :href="route('department')" class="nav-link" :class="{ active: route().current('department*') }">
                <i class="fa-solid fa-star"></i>
                <span>부서</span>
            </a>
            <a href="#" @click.prevent="not_ready" class="nav-link" :class="{ active: route().current('profile') }">
                <i class="fa-solid fa-person"></i>
                <span>프로필</span>
            </a>
        </div>
    </div>
</template>

<style scoped></style>
