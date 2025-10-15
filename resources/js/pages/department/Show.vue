<script setup lang="ts">
import ContentsList from '@/components/contents/ContentsList.vue';
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';
import { router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
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
        }
    );
};
</script>

<template>
    <Header :title="'부서 / ' + department.name" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <div class="main-profile">
                    <div class="about-profile">
                        <div class="media rounded-circle">
                            <img :src="department.icon_image" alt="profile-image" />
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
            </div>
        </div>
    </div>
</template>

<style scoped></style>
