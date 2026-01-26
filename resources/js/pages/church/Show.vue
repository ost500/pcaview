<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ContentsList from '@/components/contents/ContentsList.vue';
import AddChannelModal from '@/components/department/AddChannelModal.vue';
import FeedComposer from '@/components/feed/FeedComposer.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Church } from '@/types/church';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Plus } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps<{
    church: Church;
    contents: Pagination<Contents>;
    departments: Department[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

// display_name이 있으면 사용, 없으면 name 사용
const churchDisplayName = computed(() => props.church.display_name || props.church.name);

// URL 파라미터로 헤더 숨김 여부 확인
const hideHeader = ref(false);
if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    hideHeader.value = urlParams.get('hideHeader') === 'true';
}

function goToDepartment(id: number) {
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

const allContents = ref<Contents[]>([...props.contents.data]);
const currentPage = ref(props.contents.current_page);
const hasMorePages = ref(!!props.contents.next_page_url);
const isLoading = ref(false);

const images = [props.church.worship_time_image, props.church.address_url];
const showViewer = ref(false);
const index = ref(0);

function open(indexNumber: number) {
    index.value = indexNumber;
    showViewer.value = true;
}

function close() {
    showViewer.value = false;
}

const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) return;

    isLoading.value = true;
    const nextPage = currentPage.value + 1;

    router.get(
        safeRoute('church.show', { id: props.church.id }),
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
    <Head :title="churchDisplayName">
        <!-- Basic Meta Tags -->
        <meta
            name="description"
            :content="`${churchDisplayName}의 최신 소식과 트렌드를 실시간으로 확인하세요. 예배시간과 약도도 확인할 수 있습니다.`"
        />
        <meta name="keywords" :content="`PCAview, 피카뷰, ${churchDisplayName}, 교회, 예배시간, 약도, 뉴스, 트렌드`" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="`https://pcaview.com/church/${church.id}`" />
        <meta property="og:title" :content="`${churchDisplayName} - PCAview`" />
        <meta property="og:description" :content="`${churchDisplayName}의 최신 소식과 트렌드를 실시간으로 확인하세요.`" />
        <meta property="og:image" :content="church.icon_url" />
        <meta property="og:site_name" content="PCAview" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:url" :content="`https://pcaview.com/church/${church.id}`" />
        <meta name="twitter:title" :content="`${churchDisplayName} - PCAview`" />
        <meta name="twitter:description" :content="`${churchDisplayName}의 최신 소식과 트렌드를 실시간으로 확인하세요.`" />
        <meta name="twitter:image" :content="church.icon_url" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="`https://pcaview.com/church/${church.id}`" />
    </Head>

    <Header v-if="!hideHeader" :title="churchDisplayName" :backbutton="true"></Header>

    <!-- 채널 추가 모달 -->
    <AddChannelModal :show="showAddChannelModal" :church-id="church.id" @close="closeAddChannelModal" />

    <div class="bg-white pt-3 pb-14 sm:pt-4 sm:pb-16" :class="{ 'pt-0': hideHeader }">
        <div class="mx-auto max-w-2xl px-4">
            <!-- 교회 정보 -->
            <div class="mb-4 flex items-center gap-4 sm:mb-6">
                <div class="church-icon">
                    <img :src="church.icon_url" :alt="churchDisplayName + ' 아이콘'" loading="lazy" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ churchDisplayName }}</h1>
                    <p class="mt-1 text-sm text-gray-600"><i class="icon feather icon-map-pin me-1"></i>{{ church.address }}</p>
                </div>
            </div>

            <!-- 예배시간과 약도 -->
            <div class="mb-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="rounded-lg border border-gray-200 p-4">
                    <h2 class="mb-2 text-lg font-semibold text-gray-900">예배시간</h2>
                    <img
                        :src="church.worship_time_image"
                        @click="open(0)"
                        :alt="churchDisplayName + ' 예배 시간'"
                        loading="lazy"
                        class="cursor-pointer rounded"
                    />
                </div>

                <div class="detail-bottom-area">
                    <div class="about">
                        <h6 class="title">예배시간</h6>
                        <p class="para-text">
                            <img
                                :src="church.worship_time_image"
                                @click="open(0)"
                                :alt="churchDisplayName + ' 예배 시간'"
                                loading="lazy"
                                decoding="async"
                            />
                        </p>
                    </div>
                </div>
            </div>

            <!-- 부서 목록 (수평 스크롤) -->
            <div v-if="departments && departments.length > 0" class="mb-4">
                <h2 class="mb-3 text-lg font-semibold text-gray-900">채널</h2>
                <div class="no-scrollbar flex gap-3 overflow-x-auto pb-2">
                    <!-- 채널 추가 카드 (로그인 시에만 표시) -->
                    <div
                        v-if="user"
                        @click="handleAddChannel"
                        class="flex-shrink-0 cursor-pointer transition-transform active:scale-95"
                        style="width: 120px"
                    >
                        <div
                            class="overflow-hidden rounded-lg border-2 border-dashed border-gray-300 bg-gray-50 shadow-md transition-colors hover:border-blue-500 hover:bg-blue-50"
                        >
                            <!-- Plus Icon -->
                            <div class="flex aspect-square w-full items-center justify-center bg-gray-100">
                                <Plus :size="40" class="text-gray-400" :stroke-width="2" />
                            </div>
                            <!-- Label -->
                            <div class="p-2">
                                <h3 class="text-center text-xs font-medium text-gray-600">채널 추가</h3>
                            </div>
                        </div>
                    </div>

                    <!-- 기존 Department 카드들 -->
                    <div
                        v-for="department in departments"
                        :key="department.id"
                        @click="goToDepartment(department.id)"
                        class="flex-shrink-0 cursor-pointer transition-transform active:scale-95"
                        style="width: 120px"
                    >
                        <div class="overflow-hidden rounded-lg bg-white shadow-md">
                            <!-- Icon Image -->
                            <div class="aspect-square w-full overflow-hidden bg-gray-100">
                                <img
                                    :src="department.icon_image || '/pcaview_icon.png'"
                                    :alt="department.name + ' 아이콘'"
                                    class="h-full w-full object-cover"
                                    loading="lazy"
                                />
                            </div>
                            <!-- Department Name -->
                            <div class="p-2">
                                <h3 class="text-center text-xs font-medium text-gray-900">
                                    {{ department.name }}
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 피드 작성 컴포넌트 -->
            <FeedComposer :church="church" :departments="departments" />

            <!-- 컨텐츠 리스트 -->
            <ContentsList :contents="allContents" :is-loading="isLoading" :has-more="hasMorePages" @load-more="loadMore"></ContentsList>

            <VueEasyLightbox @hide="close" :visible="showViewer" :imgs="images" :index="index" />
            <BusinessInfo class="mt-3" />
        </div>
    </div>
</template>

<style scoped>
/* 교회 아이콘 */
.church-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid var(--primary, #667eea);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    flex-shrink: 0;
}

.church-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

@media (min-width: 640px) {
    .church-icon {
        width: 100px;
        height: 100px;
    }
}

/* 스크롤바 숨기기 */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
