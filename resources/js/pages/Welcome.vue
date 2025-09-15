<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import MenuBar from '@/components/template/MenuBar.vue';
import { Contents } from '@/types/contents';
import { Pagination } from '@/types/pagination';
import { onMounted } from 'vue';
import { route } from 'ziggy-js';
import ContentsList from '@/components/contents/ContentsList.vue';

const props = defineProps<{ contents: Pagination<Contents> }>();

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
                    <div class="swiper-slide m-r15">
                        <a href="chat.html" class="recent active">
                            <div class="media media-60 rounded-circle">
                                <img src="/storage/msch.webp" alt="" />
                            </div>
                            <span>명성교회</span>
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
