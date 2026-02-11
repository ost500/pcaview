<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import AddChannelModal from '@/components/department/AddChannelModal.vue';
import DepartmentCard from '@/components/department/DepartmentCard.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Department } from '@/types/department';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

interface PaginatedDepartments {
    data: Department[];
    current_page: number;
    last_page: number;
    next_page_url: string | null;
}

const props = defineProps<{ departments: PaginatedDepartments }>();

const allDepartments = ref<Department[]>(props.departments.data);
const currentPage = ref(props.departments.current_page);
const lastPage = ref(props.departments.last_page);
const nextPageUrl = ref(props.departments.next_page_url);
const isLoading = ref(false);

const page = usePage();
const user = computed(() => page.props.auth.user);

// 첫 번째 부서의 교회 ID를 기본값으로 사용
const defaultChurchId = computed(() => {
    return allDepartments.value.length > 0 && allDepartments.value[0].church ? allDepartments.value[0].church.id : 1;
});

// 무한 스크롤 함수
const loadMoreDepartments = () => {
    if (isLoading.value || !nextPageUrl.value || currentPage.value >= lastPage.value) {
        return;
    }

    isLoading.value = true;

    router.get(
        nextPageUrl.value,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['departments'],
            onSuccess: (page: any) => {
                const newData = page.props.departments;
                allDepartments.value = [...allDepartments.value, ...newData.data];
                currentPage.value = newData.current_page;
                lastPage.value = newData.last_page;
                nextPageUrl.value = newData.next_page_url;
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
            },
        }
    );
};

// 스크롤 이벤트 핸들러
const handleScroll = () => {
    const scrollTop = window.scrollY || document.documentElement.scrollTop;
    const windowHeight = window.innerHeight;
    const documentHeight = document.documentElement.scrollHeight;

    // 페이지 하단에서 200px 이전에 로드 시작
    if (scrollTop + windowHeight >= documentHeight - 200) {
        loadMoreDepartments();
    }
};

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
    // 스크롤 이벤트 리스너 추가
    window.addEventListener('scroll', handleScroll);

    // JSON-LD structured data 추가
    const structuredData = document.createElement('script');
    structuredData.type = 'application/ld+json';
    structuredData.text = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'ItemList',
        name: '부서 목록',
        description: '주보고에 등록된 교회 부서 목록',
        numberOfItems: allDepartments.value.length,
        itemListElement: allDepartments.value.map((department, index) => ({
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

onUnmounted(() => {
    // 스크롤 이벤트 리스너 제거
    window.removeEventListener('scroll', handleScroll);
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
                    <DepartmentCard v-for="department in allDepartments" :key="department.id" :department="department" @click="goToContent" />
                </div>

                <!-- 로딩 인디케이터 -->
                <div v-if="isLoading" class="mt-6 flex justify-center">
                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-blue-500"></div>
                </div>

                <!-- 더 이상 데이터가 없을 때 -->
                <div v-else-if="currentPage >= lastPage && allDepartments.length > 0" class="mt-6 text-center text-sm text-gray-500">
                    모든 부서를 불러왔습니다
                </div>

                <BusinessInfo class="mt-4" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
