<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import ContentsList from '@/components/contents/ContentsList.vue';
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{ department: Department; contents: Pagination<Contents> }>();

const allContents = ref<Contents[]>([...props.contents.data]);
const currentPage = ref(props.contents.current_page);
const hasMorePages = ref(!!props.contents.next_page_url);
const isLoading = ref(false);

const loadMore = () => {
    if (isLoading.value || !hasMorePages.value) return;

    const nextPage = currentPage.value + 1;
    isLoading.value = true;

    router.get(
        route('department.show', { id: props.department.id }),
        { page: nextPage },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['contents'],
            onSuccess: (page: any) => {
                const newContents = page.props.contents;
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
</script>

<template>
    <Head :title="department.name + ' - 명성교회 주보고'">
        <!-- Basic Meta Tags -->
        <meta name="description" :content="department.name + ' 부서의 주보와 소식을 확인하세요.'" />
        <meta name="keywords" :content="'교회, 주보, ' + department.name + ', 부서 소식'" />

        <!-- Open Graph -->
        <meta property="og:type" content="website" />
        <meta property="og:url" :content="route('department.show', { id: department.id })" />
        <meta property="og:title" :content="department.name + ' - 명성교회 주보고'" />
        <meta property="og:description" :content="department.name + ' 부서의 주보와 소식을 확인하세요.'" />
        <meta property="og:image" :content="department.icon_image" />
        <meta property="og:site_name" content="명성교회 주보고" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:url" :content="route('department.show', { id: department.id })" />
        <meta name="twitter:title" :content="department.name + ' - 명성교회 주보고'" />
        <meta name="twitter:description" :content="department.name + ' 부서의 주보와 소식을 확인하세요.'" />
        <meta name="twitter:image" :content="department.icon_image" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="route('department.show', { id: department.id })" />
    </Head>
    <Header :title="'부서 / ' + department.name" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <div class="main-profile">
                    <div class="about-profile">
                        <div class="media rounded-circle">
                            <img :src="department.icon_image" :alt="department.name + ' 아이콘'" loading="lazy" decoding="async" />
                            <svg class="radial-progress m-b20" data-percentage="100" viewBox="0 0 80 80">
                                <circle class="incomplete" cx="40" cy="40" r="35"></circle>
                                <circle class="complete" cx="40" cy="40" r="35" style="stroke-dashoffset: 0"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="profile-detail">
                        <h4 class="name">{{ department.name }}</h4>
                    </div>
                </div>

                <div class="detail-bottom-area">
                    <div class="about">
                        <ContentsList :contents="allContents" :isLoading="isLoading" :hasMore="hasMorePages" @loadMore="loadMore"></ContentsList>
                    </div>
                </div>
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
