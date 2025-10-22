<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth);

// 프로필 클릭 핸들러 - 항상 프로필 페이지로 이동 (페이지 내에서 로그인 상태 체크)
const handleProfileClick = () => {
    window.location.href = route('profile');
};
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
            <a
                href="#"
                @click.prevent="handleProfileClick"
                class="nav-link"
                :class="{ active: route().current('profile*') || route().current('settings*') }"
            >
                <i class="fa-solid fa-person"></i>
                <span>{{ auth.user ? '프로필' : '로그인' }}</span>
            </a>
        </div>
    </div>
</template>

<style scoped></style>
