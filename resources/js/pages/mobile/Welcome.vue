<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ContentsList from '@/components/contents/ContentsList.vue';
import FeedComposer from '@/components/feed/FeedComposer.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Church } from '@/types/church';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { Head, router, useRemember } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    contents: Pagination<Contents>;
    churches: Church[];
    departments: Department[];
    subscribedDepartmentIds?: number[];
    currentChurch?: Church;
}>();

// 구독 여부 확인 함수
const isSubscribed = (departmentId: number) => {
    return props.subscribedDepartmentIds?.includes(departmentId) ?? false;
};

// Infinite scroll state with persistence
const allContents = useRemember<Contents[]>([...props.contents.data], 'mobile-home-contents');
const currentPage = ref(props.contents.current_page);
const hasMorePages = ref(!!props.contents.next_page_url);
const isLoading = ref(false);

// Load more contents
const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) return;

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        safeRoute('mobile.home'),
        { page: nextPage },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['contents'],
            onSuccess: (page) => {
                const newContents = page.props.contents as Pagination<Contents>;
                allContents.value = [...allContents.value, ...newContents.data];
                currentPage.value = newContents.current_page;
                hasMorePages.value = !!newContents.next_page_url;
                isLoading.value = false;
            },
            onError: () => {
                isLoading.value = false;
            },
        },
    );
};

// Watch for props changes (initial load)
watch(
    () => props.contents,
    (newContents) => {
        if (newContents.current_page === 1) {
            allContents.value = [...newContents.data];
            currentPage.value = newContents.current_page;
            hasMorePages.value = !!newContents.next_page_url;
        }
    },
);
</script>

<template>
    <Head>
        <!-- Basic Meta Tags -->
        <meta
            name="description"
            content="PCAview(피카뷰)에서 트렌딩 뉴스와 실시간 소식을 한눈에 확인하세요. 다양한 분야의 최신 트렌드와 이슈를 빠르게 만나보세요."
        />
        <meta name="keywords" content="PCAview, 피카뷰, 트렌드, 뉴스, 실시간 소식, 이슈, 트렌딩, 최신 뉴스" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:locale" content="ko_KR" />
        <meta property="og:url" content="https://pcaview.com" />
        <meta property="og:title" content="PCAview(피카뷰) - 트렌딩 뉴스와 실시간 소식" />
        <meta
            property="og:description"
            content="다양한 분야의 최신 트렌드와 이슈를 한눈에 확인하세요. 실시간 업데이트되는 뉴스와 소식을 PCAview에서 만나보세요."
        />
        <meta property="og:site_name" content="PCAview" />
        <meta property="og:image" content="https://pcaview.com/og_image.png" />
        <meta property="og:image:width" content="1200" />
        <meta property="og:image:height" content="630" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://pcaview.com" />
        <meta name="twitter:title" content="PCAview(피카뷰) - 트렌딩 뉴스와 실시간 소식" />
        <meta
            name="twitter:description"
            content="다양한 분야의 최신 트렌드와 이슈를 한눈에 확인하세요. 실시간 업데이트되는 뉴스와 소식을 PCAview에서 만나보세요."
        />
        <meta name="twitter:image" content="https://pcaview.com/og_image.png" />

        <!-- Canonical URL -->
        <link rel="canonical" href="https://pcaview.com" />
    </Head>

    <div class="bg-white pb-14 sm:pb-16">
        <div class="mx-auto max-w-screen-xl">
            <!-- 부서 목록 수평 스크롤 -->
            <div class="department-scroll-container max-w-none">
                <div class="department-scroll-wrapper">
                    <a
                        v-for="department in departments"
                        :key="department.id"
                        :href="safeRoute('mobile.department.show', { id: department.id })"
                        class="department-item"
                        :class="{ active: isSubscribed(department.id) }"
                    >
                        <div class="department-icon">
                            <img :src="department.icon_image || '/pcaview_icon.png'" :alt="department.name + ' 아이콘'" loading="lazy" />
                        </div>
                        <span class="department-name">{{ department.name }}</span>
                    </a>
                </div>
            </div>

            <!-- 피드 작성 컴포넌트 (교회 페이지 + 로그인 사용자만 표시) -->
            <FeedComposer v-if="currentChurch" :church="currentChurch" :departments="departments" />

            <ContentsList :contents="allContents" :is-loading="isLoading" :has-more="hasMorePages" @load-more="loadMore"></ContentsList>
            <BusinessInfo class="mt-3" />
        </div>
    </div>
</template>

<style scoped>
/* 부서 목록 수평 스크롤 컨테이너 */
.department-scroll-container {
    margin-bottom: 0;
    margin-left: -1rem;
    margin-right: -1rem;
}

.department-scroll-wrapper {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    overflow-y: hidden;
    padding: 0.5rem 0 1rem 1rem;
    /* 스크롤바 숨김 (선택사항) */
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE and Edge */
    /* 부드러운 스크롤 */
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch; /* iOS smooth scrolling */
}

.department-scroll-wrapper::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

/* 부서 아이템 */
.department-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
    min-width: 70px;
    text-decoration: none;
    transition: transform 0.2s ease;
}

.department-item:active {
    transform: scale(0.95);
}

/* 부서 아이콘 */
.department-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid transparent;
    transition:
        border-color 0.3s ease,
        box-shadow 0.3s ease;
}

.department-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* 활성 상태 (구독한 부서) */
.department-item.active .department-icon {
    border-color: var(--primary, #667eea);
    box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
}

/* 부서 이름 */
.department-name {
    font-size: 0.75rem;
    font-weight: 500;
    color: #000000;
    text-align: center;
    max-width: 70px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    line-height: 1.2;
}

/* 호버 효과 (데스크톱) */
@media (hover: hover) {
    .department-item:hover {
        transform: translateY(-2px);
    }

    .department-item:hover .department-icon {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
}

/* 가로 스크롤 방지 - 광고 iframe */
iframe {
    max-width: 100%;
    overflow: hidden;
}
</style>
