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

// HTML에서 텍스트만 추출하는 함수 (줄바꿈 보존)
function extractTextFromHtml(html: string, maxLength: number = 300): string {
    if (!html) return '';

    // <p>, <br>, <div> 태그를 줄바꿈으로 변환
    let processedHtml = html
        .replace(/<\/p>/gi, '\n\n')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/div>/gi, '\n')
        .replace(/<\/li>/gi, '\n');

    // HTML 태그 제거
    const temp = document.createElement('div');
    temp.innerHTML = processedHtml;
    const text = temp.textContent || temp.innerText || '';

    // 연속된 공백을 하나로, 3개 이상의 줄바꿈을 2개로 정리
    const cleanText = text
        .replace(/ +/g, ' ')
        .replace(/\n{3,}/g, '\n\n')
        .trim();

    // 지정된 길이로 자르기
    if (cleanText.length > maxLength) {
        return cleanText.substring(0, maxLength) + '...';
    }

    return cleanText;
}

// HTML 타입인지 확인하는 함수
function isHtmlType(content: Contents): boolean {
    return content.file_type === 'HTML';
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
            },
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
                <!-- Department 정보 -->
                <div v-if="content.department" class="card-header department-header">
                    <div class="d-flex align-items-center">
                        <div class="department-icon">
                            <img :src="content.department.icon_image" :alt="content.department.name" />
                        </div>
                        <span class="department-name">{{ content.department.name }}</span>
                    </div>
                </div>

                <!-- 내용: 이미지 또는 텍스트 미리보기 -->
                <div v-if="!isHtmlType(content)" style="max-height: 600px; overflow: hidden">
                    <img :src="content.thumbnail_url" class="card-img-top" alt="..." />
                </div>
                <div v-if="isHtmlType(content)" class="card-body">
                    <p class="card-text preview-text mb-0 text-muted">
                        {{ extractTextFromHtml(content.body) }}
                    </p>
                </div>

                <!-- 타이틀 및 자세히 버튼 -->
                <div class="card-body">
                    <h5 class="card-title mb-3">{{ content.title }}</h5>
                    <p class="mb-0 text-right">
                        <a :href="route('contents.show', { id: content.id })" class="btn btn-primary btn-sm text-right">자세히</a>
                    </p>
                </div>
            </div>
        </div>

        <!-- Infinite scroll trigger -->
        <div ref="loadMoreTrigger" class="col-12 py-4 text-center">
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

.preview-text {
    white-space: pre-line;
    word-break: keep-all;
    line-height: 1.6;
    font-size: 0.9rem;
    max-height: 200px;
    overflow: hidden;
}

.department-header {
    background-color: transparent;
    border-bottom: none;
    padding: 0.75rem 1rem;
}

.department-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
}

.department-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.department-name {
    font-size: 0.95rem;
    font-weight: 600;
    color: #495057;
}
</style>
