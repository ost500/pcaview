<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import MenuBar from '@/components/template/MenuBar.vue';
import { Contents } from '@/types/contents';
import { Pagination } from '@/types/pagination';
import { onMounted } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ contents: Pagination<Contents> }>();

onMounted(() => {
    if (import.meta.hot) {
        const preloader = document.getElementById('preloader');
        if (preloader) preloader.style.display = 'none';
    }
});

function goToContent(id: number) {
    window.location.href = route('contents.show', { id: id });
}
</script>

<template>
    <Header title="홈"></Header>

    <!-- Page Content Start -->
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
            <div class="row" id="contentArea">
                <div v-for="content in props.contents.data" class="col-12" v-bind:key="content.id">
                    <div class="card" @click="goToContent(content.id)">
                        <div class="card-header">
                            <h5 class="card-title">{{ content.title }}</h5>
                        </div>
                        <img :src="content.thumbnail_url" class="card-img-top" alt="..." />
                        <div class="card-body">
                            <!--                            <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                            <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            <p class="mb-0 text-right">
                                <a :href="route('contents.show', { id: content.id })" class="btn btn-primary text-right">자세히</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Content End -->

</template>
