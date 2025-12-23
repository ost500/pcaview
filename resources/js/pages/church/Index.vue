<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { Church } from '@/types/church';
import { safeRoute } from '@/composables/useSafeRoute';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{ churches: Church[] }>();

function goToContent(id: number) {
    window.location.href = safeRoute('church.show', { id: id });
}

// Kakao AdFit 광고 로드 - 주석 처리
/*
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
*/

onMounted(() => {
    // 광고 로드 주석 처리
    /*
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
    */

    // JSON-LD structured data 추가
    const structuredData = document.createElement('script');
    structuredData.type = 'application/ld+json';
    structuredData.text = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        'name': '교회 목록',
        'description': '주보고에 등록된 교회 목록',
        'numberOfItems': props.churches.length,
        'itemListElement': props.churches.map((church, index) => ({
            '@type': 'ListItem',
            'position': index + 1,
            'item': {
                '@type': 'Place',
                '@id': safeRoute('church.show', { id: church.id }),
                'name': church.name,
                'address': church.address,
                'image': church.icon_url
            }
        }))
    });
    document.head.appendChild(structuredData);
});
</script>

<template>
    <div>
        <Head title="교회 목록">
            <meta name="description" content="PCAview를 포함한 교회 목록입니다. 다양한 교회의 주보와 소식을 확인하세요." />
            <meta name="keywords" content="PCAview, 교회, 교회 목록, 주보, 교회 소식" />

            <!-- Open Graph -->
            <meta property="og:type" content="website" />
            <meta property="og:url" :content="safeRoute('church')" />
            <meta property="og:title" content="교회 목록" />
            <meta property="og:description" content="PCAview를 포함한 교회 목록입니다. 다양한 교회의 주보와 소식을 확인하세요." />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" content="교회 목록" />
            <meta name="twitter:description" content="주보고에 등록된 교회 목록입니다." />

            <!-- Canonical URL -->
            <link rel="canonical" :href="safeRoute('church')" />
        </Head>

        <Header title="교회"></Header>

        <div class="page-content space-top p-b60">
            <div class="container">
                <!-- 카카오 광고 주석 처리
                <ins
                    class="kakao_ad_area"
                    style="display: block"
                    data-ad-unit="DAN-bE302RQ73kwLuzKI"
                    data-ad-width="320"
                    data-ad-height="50"
                ></ins>
                -->
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
                                    <a :href="safeRoute('church.show', { id: church.id })" class="btn btn-primary text-right btn-sm">자세히</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- 쿠팡 광고 주석 처리
                <iframe
                    src="https://ads-partners.coupang.com/widgets.html?id=927016&template=carousel&trackingCode=AF7527668&subId=&width=680&height=140&tsource="
                    width="100%"
                    height="140"
                    frameborder="0"
                    scrolling="no"
                    referrerpolicy="unsafe-url"
                    browsingtopics
                ></iframe>
                -->
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
