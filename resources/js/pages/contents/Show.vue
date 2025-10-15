<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps<{ contents: Contents }>();

const images = props.contents.images?.map((image) => image.file_url) || [];
const showViewer = ref(false);
const index = ref(0);

function open(indexNumber: number) {
    index.value = indexNumber;
    showViewer.value = true;
}

function close() {
    showViewer.value = false;
}

// Kakao AdFit 광고 로드
const loadKakaoAd = () => {
    // 기존 스크립트가 있으면 제거 (중복 방지)
    const existingScript = document.querySelector('script[src*="t1.daumcdn.net/kas"]');
    if (existingScript) {
        existingScript.remove();
    }

    // 새 스크립트 생성 및 로드
    const script = document.createElement('script');
    script.async = true;
    script.type = 'text/javascript';
    script.src = 'https://t1.daumcdn.net/kas/static/ba.min.js';
    document.head.appendChild(script);
};

onMounted(() => {
    // Kakao AdFit 광고 로드
    // DOM이 완전히 렌더링된 후 실행
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
});
</script>

<template>
    <div>
        <Head :title="contents.title"></Head>
        <Header title="주보" :backbutton="true"></Header>

        <div class="page-content space-top p-b60">
            <div class="p-b0 container">
                <div class="title-bar">
                    <h6 class="title">소식</h6>
                </div>
            </div>
            <iframe
                src="https://ads-partners.coupang.com/widgets.html?id=927016&template=carousel&trackingCode=AF7527668&subId=&width=680&height=140&tsource="
                width="100%"
                height="140"
                frameborder="0"
                scrolling="no"
                referrerpolicy="unsafe-url"
                browsingtopics
            ></iframe>

            <div class="container p-0">
                <ins
                    class="kakao_ad_area"
                    style="display: block"
                    data-ad-unit="DAN-WGXmCBWunDboP7Xa"
                    data-ad-width="320"
                    data-ad-height="50"
                ></ins>
                <div class="row" id="contentArea">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{ contents.title }}</h5>
                            </div>
                            <div v-if="contents.file_type != 'YOUTUBE'">
                                <div v-for="(image, index) in contents.images" v-bind:key="image.id">
                                    <img :src="image.file_url" @click="open(index)" class="card-img-top" :alt="contents.title" />
                                </div>
                                <VueEasyLightbox @hide="close" :visible="showViewer" :imgs="images" :index="index" />
                            </div>
                            <iframe
                                v-else
                                width="100%"
                                height="315"
                                :src="'https://www.youtube.com/embed/' + contents.file_url"
                                title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>

                            <div class="card-body">
                                <!--                                <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
