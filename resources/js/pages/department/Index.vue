<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Department } from '@/types/department';
import { Head } from '@inertiajs/vue3';
import { onMounted } from 'vue';

const props = defineProps<{ departments: Department[] }>();

function goToContent(id: number) {
    window.location.href = safeRoute('department.show', { id: id });
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
        'name': '부서 목록',
        'description': '주보고에 등록된 교회 부서 목록',
        'numberOfItems': props.departments.length,
        'itemListElement': props.departments.map((department, index) => ({
            '@type': 'ListItem',
            'position': index + 1,
            'item': {
                '@type': 'Organization',
                '@id': safeRoute('department.show', { id: department.id }),
                'name': department.name,
                'image': department.icon_image
            }
        }))
    });
    document.head.appendChild(structuredData);
});
</script>

<template>
    <div>
        <Head title="부서 목록">
            <meta name="description" content="PCAview 부서 목록입니다. 각 부서의 주보와 소식을 확인하세요." />
            <meta name="keywords" content="PCAview, PCAview 부서, 교회, 부서, 청년부, 유치부, 주일학교, 찬양대, 교회 부서" />

            <!-- Open Graph -->
            <meta property="og:type" content="website" />
            <meta property="og:url" :content="safeRoute('department')" />
            <meta property="og:title" content="부서 목록" />
            <meta property="og:description" content="PCAview 부서 목록입니다. 각 부서의 주보와 소식을 확인하세요." />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary" />
            <meta name="twitter:title" content="부서 목록" />
            <meta name="twitter:description" content="주보고에 등록된 교회 부서 목록입니다." />

            <!-- Canonical URL -->
            <link rel="canonical" :href="safeRoute('department')" />
        </Head>

        <Header title="VIEW"></Header>

        <div class="bg-white pb-14 pt-3 sm:pb-16 sm:pt-4">
            <div class="mx-auto max-w-screen-xl px-4">
                <!-- 3 Column Grid -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <div
                        v-for="department in departments"
                        :key="department.id"
                        @click="goToContent(department.id)"
                        class="cursor-pointer transition-transform active:scale-95 sm:hover:scale-105"
                    >
                        <div class="overflow-hidden rounded-lg bg-white shadow-md">
                            <!-- Icon Image -->
                            <div class="aspect-square w-full overflow-hidden bg-gray-100">
                                <img
                                    :src="department.icon_image"
                                    :alt="department.name + ' 아이콘'"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <!-- Department Name -->
                            <div class="p-2 sm:p-3">
                                <h3 class="text-center text-xs font-medium text-gray-900 sm:text-sm">
                                    {{ department.name }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <BusinessInfo class="mt-4" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
