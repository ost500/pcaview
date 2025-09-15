<script setup lang="ts">
import { onMounted } from 'vue';
import { route } from 'ziggy-js';
import MenuBar from '@/components/template/MenuBar.vue';
import Header from '@/components/template/Header.vue';

onMounted(() => {
    if (import.meta.hot) {
        const preloader = document.getElementById('preloader');
        if (preloader) preloader.style.display = 'none';
    }
});
</script>

<template>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader">
            <div class="load-circle">
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <!-- Preloader end-->

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
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">명성교회 주보</h5>
                        </div>
                        <img src="/storage/BULLETIN/after/36.webp" class="card-img-top" alt="..." />
                        <div class="card-body">
                            <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>
                            <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            <p class="text-right mb-0">
                                <a :href="route('contents.show', { id: 36 })" class="btn btn-primary text-right">자세히</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row" id="contentArea">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title">명성교회 밝은소리</h5>
                        </div>
                        <img src="/storage/NEWS/after/72.webp" class="card-img-top" alt="..." />
                        <div class="card-body">
                            <h5 class="card-title">명성교회 2025년 9월 10일 밝은소리</h5>
                            <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            <p class="text-right mb-0">
                                <a href="javascript:void(0);" class="btn btn-primary text-right">자세히</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Content End -->

    <MenuBar></MenuBar>
</template>
