<script setup lang="ts">
import Logo from '@/components/template/Logo.vue';
import MenuBar from '@/components/template/MenuBar.vue';

interface Props {
    title: string;
    backbutton?: boolean;
    count?: number;
}

const props = defineProps<Props>();

// 뒤로가기 함수
const goBack = () => {
    window.history.back();
};

const isProduction = import.meta.env.PROD; // vite 기본 제공 플래그
</script>
<template>
    <!--     Preloader -->
    <div v-if="isProduction" id="preloader">
        <div class="loader">
            <div class="load-circle">
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <!-- Preloader end-->

    <header class="header header-fixed">
        <div class="container">
            <div class="header-content">
                <div class="left-content">
                    <a v-if="backbutton" href="javascript:void(0);" class="back-btn" @click="goBack">
                        <i class="icon feather icon-arrow-left"></i>
                    </a>

                    <Logo></Logo>

                    <h6 class="title">{{ props.title }}</h6>
                </div>
                <div class="mid-content"></div>
                <div class="right-content"></div>
            </div>
        </div>
    </header>

    <MenuBar></MenuBar>
</template>

<style scoped></style>
