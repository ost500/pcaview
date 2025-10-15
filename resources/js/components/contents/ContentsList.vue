<script setup lang="ts">
import { Contents } from '@/types/contents';
import { onMounted, onUnmounted, ref } from 'vue';
import { route } from 'ziggy-js';

const props = defineProps<{
    contents: Contents[];
    isLoading?: boolean;
    hasMore?: boolean;
}>();

const emit = defineEmits<{
    loadMore: [];
}>();

const loadMoreTrigger = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

function goToContent(id: number) {
    window.location.href = route('contents.show', { id: id });
}

onMounted(() => {
    // Setup Intersection Observer for infinite scroll
    if (loadMoreTrigger.value && props.hasMore) {
        observer = new IntersectionObserver(
            (entries) => {
                const [entry] = entries;
                if (entry.isIntersecting && !props.isLoading && props.hasMore) {
                    emit('loadMore');
                }
            },
            {
                root: null,
                rootMargin: '200px',
                threshold: 0.1,
            }
        );

        observer.observe(loadMoreTrigger.value);
    }
});

onUnmounted(() => {
    if (observer && loadMoreTrigger.value) {
        observer.unobserve(loadMoreTrigger.value);
        observer.disconnect();
    }
});
</script>

<template>
    <div class="row">
        <div v-for="content in props.contents" class="col-12" v-bind:key="content.id">
            <div class="card" @click="goToContent(content.id)">
                <div class="card-header">
                    <h5 class="card-title">{{ content.title }}</h5>
                </div>
                <div style="max-height: 600px; overflow: hidden">
                    <img :src="content.thumbnail_url" class="card-img-top" alt="..." />
                </div>
                <div class="card-body">
                    <!--                            <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                    <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                    <p class="mb-0 text-right">
                        <a :href="route('contents.show', { id: content.id })" class="btn btn-primary btn-sm text-right">자세히</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Infinite scroll trigger -->
        <div ref="loadMoreTrigger" class="col-12 text-center py-4">
            <div v-if="isLoading" class="spinner-border text-primary" role="status">
                <span class="visually-hidden">로딩 중...</span>
            </div>
            <div v-else-if="!hasMore" class="text-muted">
                <small>모든 소식을 불러왔습니다</small>
            </div>
        </div>
    </div>
</template>

<style scoped>
.page-link {
    width: 2.5rem !important;
    height: 2.5rem !important;
    font-size: 1.5rem !important;
}
</style>
