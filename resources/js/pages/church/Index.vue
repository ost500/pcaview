<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ChurchCard from '@/components/church/ChurchCard.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Church } from '@/types/church';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{ churches: Church[] }>();

function goToContent(slug: string) {
    window.location.href = `/c/${slug}`;
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
        name: '교회 목록',
        description: '주보고에 등록된 교회 목록',
        numberOfItems: props.churches.length,
        itemListElement: props.churches.map((church, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            item: {
                '@type': 'Place',
                '@id': safeRoute('church.show', { id: church.id }),
                name: church.display_name || church.name,
                address: church.address,
                image: church.icon_url,
            },
        })),
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

        <Header title="배럭"></Header>

        <div class="bg-white pt-3 pb-14 sm:pt-4 sm:pb-16">
            <div class="mx-auto max-w-2xl px-4">
                <!-- 3 Column Grid -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <ChurchCard v-for="church in churches" :key="church.id" :church="church" @click="goToContent" />
                </div>

                <BusinessInfo class="mt-4" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
