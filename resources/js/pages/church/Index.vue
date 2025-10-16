<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { Church } from '@/types/church';
import { route } from 'ziggy-js';
import { onMounted } from 'vue';

const props = defineProps<{ churches: Church[] }>();

function goToContent(id: number) {
    window.location.href = route('church.show', { id: id });
}

// Kakao AdFit 광고 로드
const loadKakaoAd = () => {
    const existingScript = document.querySelector('script[src*="t1.daumcdn.net/kas"]');
    if (existingScript) {
        existingScript.remove();
    }

    const script = document.createElement('script');
    script.async = true;
    script.type = 'text/javascript';
    script.src = 'https://t1.daumcdn.net/kas/static/ba.min.js';
    document.head.appendChild(script);
};

onMounted(() => {
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
});
</script>

<template>
    <div>
        <Header title="교회"></Header>

        <div class="page-content space-top p-b60">
            <div class="container">
                <ins
                    class="kakao_ad_area"
                    style="display: block"
                    data-ad-unit="DAN-bE302RQ73kwLuzKI"
                    data-ad-width="320"
                    data-ad-height="50"
                ></ins>
                <div class="row g-3">
                    <div v-for="church in churches" class="col-12" v-bind:key="church.id">
                        <div class="card" @click="goToContent(church.id)">
                            <div class="card-header">
                                <h5 class="card-title">{{ church.name }}</h5>
                            </div>
                            <img :src="church.icon_url" class="card-img-top" alt="..." />
                            <div class="card-body">
                                <h5 class="card-title">{{ church.address }}</h5>
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                                <p class="mb-0 text-right">
                                    <a :href="route('church.show', { id: church.id })" class="btn btn-primary text-right btn-sm">자세히</a>
                                </p>
                            </div>
                        </div>
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
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
