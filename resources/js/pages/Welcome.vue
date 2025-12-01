<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ContentsList from '@/components/contents/ContentsList.vue';
import InstallPrompt from '@/components/InstallPrompt.vue';
import IOSInstallInstructions from '@/components/IOSInstallInstructions.vue';
import Header from '@/components/template/Header.vue';
import { usePWA } from '@/composables/usePWA';
import { Church } from '@/types/church';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { Head, router, useRemember } from '@inertiajs/vue3';
import { onMounted, ref, watch } from 'vue';
import { safeRoute } from '@/composables/useSafeRoute';

// PWA 상태 관리
const { showInstallPrompt, showIOSInstructions, isIOS, isAndroid, promptInstall, dismissPrompt, closeIOSInstructions, dismissPermanently } = usePWA();

const props = defineProps<{
    contents: Pagination<Contents>;
    churches: Church[];
    departments: Department[];
    subscribedDepartmentIds?: number[];
}>();

// 개발 환경 확인
const isDev = import.meta.env.DEV;

// 개발용: PWA 프롬프트 초기화 함수
const debugResetPWA = () => {
    localStorage.removeItem('pwa-prompt-dismissed');
    localStorage.removeItem('pwa-prompt-never-show');
    alert('PWA 프롬프트 초기화 완료! 페이지를 새로고침합니다.');
    location.reload();
};

// 구독 여부 확인 함수
const isSubscribed = (departmentId: number) => {
    return props.subscribedDepartmentIds?.includes(departmentId) ?? false;
};

// Infinite scroll state with persistence
const allContents = useRemember<Contents[]>([...props.contents.data], 'home-contents');
const currentPage = ref(props.contents.current_page);
const hasMorePages = ref(!!props.contents.next_page_url);
const isLoading = ref(false);

// Load more contents
const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) return;

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        safeRoute('home'),
        { page: nextPage },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true, // Prevent history stacking
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

// Kakao AdFit 광고 로드 - 주석 처리
/*
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
*/

onMounted(() => {
    if (import.meta.hot) {
        const preloader = document.getElementById('preloader');
        if (preloader) preloader.style.display = 'none';
    }

    // Kakao AdFit 광고 로드 - 주석 처리
    // DOM이 완전히 렌더링된 후 실행
    /*
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
    */
});
</script>

<template>
    <Head title="명성교회 주보와 소식">
        <!-- Basic Meta Tags -->
        <meta name="description" content="명성교회 주보와 소식을 한곳에 모았습니다. 명성교회 부서 주보, 예배 안내, 교회 소식을 확인하세요." />
        <meta name="keywords" content="명성교회, 명성교회 주보, 명성교회 소식, 교회, 주보, 교회 소식, 교회 공지, 부서 주보, 예배 안내" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://jubogo.com" />
        <meta property="og:title" content="명성교회 - 명성교회 주보와 소식 주보고" />
        <meta property="og:description" content="명성교회 주보와 소식을 한곳에 모았습니다. 명성교회 부서 주보, 예배 안내, 교회 소식을 확인하세요." />
        <meta property="og:site_name" content="주보고" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://jubogo.com" />
        <meta name="twitter:title" content="명성교회 - 명성교회 주보와 소식 주보고" />
        <meta name="twitter:description" content="명성교회 주보와 소식을 한곳에 모았습니다. 명성교회 부서 주보, 예배 안내, 교회 소식을 확인하세요." />

        <!-- Canonical URL -->
        <link rel="canonical" href="https://jubogo.com" />
    </Head>

    <!-- PWA 설치 프롬프트 -->
    <InstallPrompt
        :show-install-prompt="showInstallPrompt"
        :is-android="isAndroid"
        @install="promptInstall"
        @dismiss="dismissPrompt"
        @dismiss-permanently="dismissPermanently"
    />

    <!-- iOS 설치 안내 모달 -->
    <IOSInstallInstructions
        :show-i-o-s-instructions="showIOSInstructions"
        :is-i-o-s="isIOS"
        :is-android="isAndroid"
        @close="closeIOSInstructions"
        @dismiss-permanently="dismissPermanently"
    />

    <!-- 개발용 디버그 버튼 (임시) -->
    <div v-if="isDev" class="fixed right-4 bottom-20 z-50">
        <button @click="debugResetPWA" class="rounded bg-red-600 px-3 py-2 text-xs text-white shadow-lg hover:bg-red-700">PWA 초기화</button>
    </div>

    <Header title="홈"></Header>

    <div class="pb-16 pt-4">
        <div class="container mx-auto px-4">
            <!-- 부서 목록 수평 스크롤 -->
            <div class="department-scroll-container">
                <div class="department-scroll-wrapper">
                    <a
                        v-for="department in departments"
                        :key="department.id"
                        :href="safeRoute('department.show', { id: department.id })"
                        class="department-item"
                        :class="{ active: isSubscribed(department.id) }"
                    >
                        <div class="department-icon">
                            <img :src="department.icon_image" :alt="department.name + ' 아이콘'" loading="lazy" />
                        </div>
                        <span class="department-name">{{ department.name }}</span>
                    </a>
                </div>
            </div>
            <div class="mb-4 mt-4">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">명성교회 주보 및 소식</h2>
            </div>
            <!-- 카카오 광고 주석 처리
            <ins class="kakao_ad_area" style="display: block" data-ad-unit="DAN-bE302RQ73kwLuzKI" data-ad-width="320" data-ad-height="50"></ins>
            -->
            <ContentsList :contents="allContents" :is-loading="isLoading" :has-more="hasMorePages" @load-more="loadMore"></ContentsList>
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
    padding: 0.5rem 1rem 1rem 1rem;
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
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
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
