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
const { showInstallPrompt, showIOSInstructions, isIOS, promptInstall, dismissPrompt, closeIOSInstructions, dismissPermanently } = usePWA();

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
    if (import.meta.hot) {
        const preloader = document.getElementById('preloader');
        if (preloader) preloader.style.display = 'none';
    }

    // Kakao AdFit 광고 로드
    // DOM이 완전히 렌더링된 후 실행
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
});
</script>

<template>
    <Head title="명성교회 주보고 - 교회 주보와 소식">
        <!-- Basic Meta Tags -->
        <meta name="description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다. 하나님께 보고 드리는 시간 명성교회 주보고가 올려 드립니다." />
        <meta name="keywords" content="교회, 주보, 교회 소식, 교회 공지, 부서 주보, 예배 안내" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://jubogo.com" />
        <meta property="og:title" content="명성교회 주보고 - 교회 주보와 소식" />
        <meta property="og:description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다." />
        <meta property="og:site_name" content="명성교회 주보고" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" content="https://jubogo.com" />
        <meta name="twitter:title" content="명성교회 주보고 - 교회 주보와 소식" />
        <meta name="twitter:description" content="교회의 모든 부서 주보와 소식을 한곳에 모았습니다." />

        <!-- Canonical URL -->
        <link rel="canonical" href="https://jubogo.com" />
    </Head>

    <!-- PWA 설치 프롬프트 -->
    <InstallPrompt
        :show-install-prompt="showInstallPrompt"
        @install="promptInstall"
        @dismiss="dismissPrompt"
        @dismiss-permanently="dismissPermanently"
    />

    <!-- iOS 설치 안내 모달 -->
    <IOSInstallInstructions
        :show-i-o-s-instructions="showIOSInstructions"
        :is-i-o-s="isIOS"
        @close="closeIOSInstructions"
        @dismiss-permanently="dismissPermanently"
    />

    <!-- 개발용 디버그 버튼 (임시) -->
    <div v-if="isDev" class="fixed right-4 bottom-20 z-50">
        <button @click="debugResetPWA" class="rounded bg-red-600 px-3 py-2 text-xs text-white shadow-lg hover:bg-red-700">PWA 초기화</button>
    </div>

    <Header title="홈"></Header>

    <div class="page-content space-top p-b60">
        <div class="container">
            <div class="swiper chat-swiper">
                <div class="swiper-wrapper">
                    <div v-for="department in departments" class="swiper-slide m-r15" v-bind:key="department.id">
                        <a :href="safeRoute('department.show', { id: department.id })" class="recent" :class="{ active: isSubscribed(department.id) }">
                            <div class="media media-60 rounded-circle">
                                <img :src="department.icon_image" :alt="department.name + 'icon'" />
                            </div>
                            <span>{{ department.name }}</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="title-bar">
                <h6 class="title">소식</h6>
            </div>
            <ins class="kakao_ad_area" style="display: block" data-ad-unit="DAN-bE302RQ73kwLuzKI" data-ad-width="320" data-ad-height="50"></ins>
            <ContentsList :contents="allContents" :is-loading="isLoading" :has-more="hasMorePages" @load-more="loadMore"></ContentsList>
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
</template>
