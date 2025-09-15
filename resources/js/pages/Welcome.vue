<script setup lang="ts">
import ContentsList from '@/components/contents/ContentsList.vue';
import Header from '@/components/template/Header.vue';
import { Church } from '@/types/church';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { onMounted } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ contents: Pagination<Contents>; churches: Church[]; departments: Department[] }>();

onMounted(() => {
    if (import.meta.hot) {
        const preloader = document.getElementById('preloader');
        if (preloader) preloader.style.display = 'none';
    }
});
</script>

<template>
    <Header title="홈"></Header>

    <div class="page-content space-top p-b60">
        <div class="container">
            <div class="swiper chat-swiper">
                <div class="swiper-wrapper">
<!--                    <div v-for="church in churches" class="swiper-slide m-r15" v-bind:key="church.id">-->
<!--                        <a :href="route('church', { id: church.id })" class="recent active">-->
<!--                            <div class="media media-60 rounded-circle">-->
<!--                                <img :src="church.icon_url" :alt="church.name + 'icon'" />-->
<!--                            </div>-->
<!--                            <span>{{ church.name }}</span>-->
<!--                        </a>-->
<!--                    </div>-->
                    <div v-for="department in departments" class="swiper-slide m-r15" v-bind:key="department.id">
                        <a :href="route('church', { id: department.id })" class="recent active">
                            <div class="media media-60 rounded-circle">
                                <img :src="department.icon_image" :alt="department.name + 'icon'" />
                            </div>
                            <span>{{ department.name }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="title-bar">
                <h6 class="title">소식</h6>
            </div>
            <ContentsList :contents="contents"></ContentsList>
        </div>
    </div>
</template>
