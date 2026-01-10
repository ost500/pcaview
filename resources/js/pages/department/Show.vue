<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ContentsList from '@/components/contents/ContentsList.vue';
import FeedComposer from '@/components/feed/FeedComposer.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{ department: Department; contents: Pagination<Contents> }>();

// URL 파라미터로 헤더 숨김 여부 확인
const hideHeader = ref(false);
if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    hideHeader.value = urlParams.get('hideHeader') === 'true';
}

const allContents = ref<Contents[]>([...props.contents.data]);
const currentPage = ref(props.contents.current_page);
const hasMorePages = ref(!!props.contents.next_page_url);
const isLoading = ref(false);

const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) return;

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        safeRoute('department.show', { id: props.department.id }),
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
    <Head :title="department.name">
        <!-- Basic Meta Tags -->
        <meta
            name="description"
            :content="`${department.name}의 최신 소식과 트렌드를 실시간으로 확인하세요. ${department.description || 'PCAview에서 제공하는 다양한 콘텐츠를 만나보세요.'}`"
        />
        <meta name="keywords" :content="`PCAview, 피카뷰, ${department.name}, 뉴스, 트렌드, 실시간 소식`" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="`https://pcaview.com/department/${department.id}`" />
        <meta property="og:title" :content="`${department.name} - PCAview`" />
        <meta property="og:description" :content="department.description || `${department.name}의 최신 소식과 트렌드를 실시간으로 확인하세요.`" />
        <meta property="og:image" :content="department.icon_image || 'https://pcaview.com/pcaview_icon.png'" />
        <meta property="og:image:width" content="400" />
        <meta property="og:image:height" content="400" />
        <meta property="og:site_name" content="PCAview" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:url" :content="`https://pcaview.com/department/${department.id}`" />
        <meta name="twitter:title" :content="`${department.name} - PCAview`" />
        <meta name="twitter:description" :content="department.description || `${department.name}의 최신 소식과 트렌드를 실시간으로 확인하세요.`" />
        <meta name="twitter:image" :content="department.icon_image || 'https://pcaview.com/pcaview_icon.png'" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="`https://pcaview.com/department/${department.id}`" />
    </Head>

    <Header v-if="!hideHeader" :title="department.name" :backbutton="true"></Header>

    <div class="bg-white pt-3 pb-14 sm:pt-4 sm:pb-16" :class="{ 'pt-0': hideHeader }">
        <div class="mx-auto max-w-2xl px-4">
            <!-- 부서 정보 -->
            <div class="mb-4 flex items-center gap-4 sm:mb-6">
                <div class="department-icon">
                    <img :src="department.icon_image || '/pcaview_icon.png'" :alt="department.name + ' 아이콘'" loading="lazy" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ department.name }}</h1>
                    <p v-if="department.description" class="mt-1 text-sm text-gray-600">{{ department.description }}</p>
                </div>
            </div>

            <!-- 피드 작성 컴포넌트 -->
            <FeedComposer :department="department" />

            <!-- 컨텐츠 리스트 -->
            <ContentsList :contents="allContents" :is-loading="isLoading" :has-more="hasMorePages" @load-more="loadMore"></ContentsList>
            <BusinessInfo class="mt-3" />
        </div>
    </div>
</template>

<style scoped>
/* 부서 아이콘 */
.department-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--primary, #667eea);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.department-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (min-width: 640px) {
    .department-icon {
        width: 100px;
        height: 100px;
    }
}
</style>
