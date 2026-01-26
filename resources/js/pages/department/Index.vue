<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import AddChannelModal from '@/components/department/AddChannelModal.vue';
import DepartmentCard from '@/components/department/DepartmentCard.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Department } from '@/types/department';
import { Head, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{ departments: Department[] }>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// 첫 번째 부서의 교회 ID를 기본값으로 사용
const defaultChurchId = computed(() => {
    return props.departments.length > 0 && props.departments[0].church ? props.departments[0].church.id : 1;
});

function goToContent(id: number) {
    window.location.href = safeRoute('department.show', { id: id });
}

// 채널 추가 모달 상태
const showAddChannelModal = ref(false);

function handleAddChannel() {
    if (!user.value) {
        alert('로그인이 필요합니다.');
        return;
    }
    showAddChannelModal.value = true;
}

const closeAddChannelModal = () => {
    showAddChannelModal.value = false;
};

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
        name: '부서 목록',
        description: '주보고에 등록된 교회 부서 목록',
        numberOfItems: props.departments.length,
        itemListElement: props.departments.map((department, index) => ({
            '@type': 'ListItem',
            position: index + 1,
            item: {
                '@type': 'Organization',
                '@id': safeRoute('department.show', { id: department.id }),
                name: department.name,
                image: department.icon_image || 'https://pcaview.com/pcaview_icon.png',
            },
        })),
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

        <!-- 채널 추가 모달 -->
        <AddChannelModal :show="showAddChannelModal" :church-id="defaultChurchId" @close="closeAddChannelModal" />

        <div class="bg-white pt-3 pb-14 sm:pt-4 sm:pb-16">
            <div class="mx-auto max-w-2xl px-4">
                <!-- 3 Column Grid -->
                <div class="grid grid-cols-3 gap-3 sm:gap-4">
                    <!-- 채널 추가 카드 (로그인 시에만 표시) -->
                    <div v-if="user" @click="handleAddChannel" class="cursor-pointer transition-transform active:scale-95 sm:hover:scale-105">
                        <div
                            class="overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 shadow-md transition-colors hover:border-blue-500 hover:bg-blue-50"
                        >
                            <!-- Plus Icon -->
                            <div class="flex aspect-square w-full items-center justify-center bg-gray-100">
                                <Plus :size="48" class="text-gray-400" :stroke-width="2" />
                            </div>
                            <!-- Label -->
                            <div class="p-2 sm:p-3">
                                <h3 class="text-center text-xs font-medium text-gray-600 sm:text-sm">채널 추가</h3>
                            </div>
                        </div>
                    </div>

                    <!-- 기존 Department 카드들 -->
                    <DepartmentCard v-for="department in departments" :key="department.id" :department="department" @click="goToContent" />
                </div>

                <BusinessInfo class="mt-4" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
