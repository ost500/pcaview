<script setup lang="ts">
import { safeRoute } from '@/composables/useSafeRoute';
import { Contents } from '@/types/contents';
import { onMounted, onUnmounted, ref } from 'vue';
// import CoupangAd from '@/components/ads/CoupangAd.vue'; // 광고 주석 처리

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

// SSR-safe navigation function
function goToContent(id: number) {
    if (typeof window !== 'undefined') {
        window.location.href = safeRoute('contents.show', { id: id });
    }
}

function goToDepartment(id: number) {
    if (typeof window !== 'undefined') {
        window.location.href = safeRoute('department.show', { id: id });
    }
}

// 날짜 포맷 함수
function formatDate(dateString: string): string {
    if (!dateString) return '';

    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    // 1분 미만
    if (diffMins < 1) return '방금 전';
    // 1시간 미만
    if (diffMins < 60) return `${diffMins}분 전`;
    // 24시간 미만
    if (diffHours < 24) return `${diffHours}시간 전`;
    // 7일 미만
    if (diffDays < 7) return `${diffDays}일 전`;

    // 7일 이상은 날짜 표시 (YYYY.MM.DD)
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}.${month}.${day}`;
}

// HTML에서 텍스트만 추출하는 함수 (줄바꿈 보존, SSR-safe)
function extractTextFromHtml(html: string, maxLength: number = 300): string {
    if (!html) return '';

    // <p>, <br>, <div> 태그를 줄바꿈으로 변환
    const processedHtml = html
        .replace(/<\/p>/gi, '\n\n')
        .replace(/<br\s*\/?>/gi, '\n')
        .replace(/<\/div>/gi, '\n')
        .replace(/<\/li>/gi, '\n');

    // HTML 태그 제거 (SSR-safe: 정규식 사용)
    const text = processedHtml
        .replace(/<[^>]*>/g, '') // 모든 HTML 태그 제거
        .replace(/&nbsp;/g, ' ') // &nbsp; 처리
        .replace(/&amp;/g, '&') // &amp; 처리
        .replace(/&lt;/g, '<') // &lt; 처리
        .replace(/&gt;/g, '>') // &gt; 처리
        .replace(/&quot;/g, '"') // &quot; 처리
        .replace(/&#39;/g, "'"); // &#39; 처리

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

// 광고를 표시할지 확인하는 함수 (3개 콘텐츠마다) - 주석 처리
/*
function shouldShowAd(index: number): boolean {
    return (index + 1) % 3 === 0;
}
*/

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
    <div class="mx-auto w-full max-w-2xl">
        <div class="space-y-4">
            <template v-for="(content, index) in props.contents" :key="content.id">
                <div
                    class="cursor-pointer overflow-hidden rounded-lg bg-gradient-to-br from-sky-50 to-blue-50 shadow-sm transition-all hover:from-sky-100 hover:to-blue-100 hover:shadow-md"
                >
                    <!-- User 정보 (user_id가 있는 경우) 또는 Church 정보 (없는 경우) -->
                    <div
                        v-if="content.user || content.church"
                        class="flex items-center gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm"
                    >
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                            <img
                                v-if="content.user"
                                :src="content.user.profile_photo_url || '/pcaview_icon.png'"
                                :alt="content.user.name"
                                class="h-full w-full object-cover"
                            />
                            <img
                                v-else-if="content.church"
                                :src="content.church.icon_url || '/pcaview_icon.png'"
                                :alt="content.church.name"
                                class="h-full w-full object-cover"
                            />
                        </div>
                        <span class="text-sm font-semibold text-sky-900">
                            {{ content.user ? content.user.name : content.church ? content.church.name : '' }}
                        </span>
                    </div>

                    <!-- 내용: 이미지 또는 텍스트 미리보기 -->
                    <div v-if="!isHtmlType(content)" class="max-h-[600px] overflow-hidden" @click="goToContent(content.id)">
                        <img
                            v-if="content.thumbnail_url"
                            :src="content.thumbnail_url"
                            class="w-full object-cover"
                            alt="콘텐츠 이미지"
                            loading="lazy"
                        />
                    </div>
                    <div v-if="isHtmlType(content)" class="bg-white/60 px-4 py-3 backdrop-blur-sm" @click="goToContent(content.id)">
                        <p class="preview-text mb-0 text-sm text-slate-700">
                            {{ extractTextFromHtml(content.body) }}
                        </p>
                    </div>

                    <!-- 타이틀 및 자세히 버튼 -->
                    <div class="bg-white/60 px-4 py-3 backdrop-blur-sm">
                        <!-- Department 정보 -->
                        <div
                            v-if="content.department"
                            @click="goToDepartment(content.department.id)"
                            class="mb-3 flex cursor-pointer items-center gap-2 transition-colors hover:text-sky-600"
                        >
                            <div class="flex h-6 w-6 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                <img
                                    :src="content.department.icon_image || '/pcaview_icon.png'"
                                    :alt="content.department.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>
                            <span class="text-xs font-medium text-sky-800">{{ content.department.name }}</span>
                        </div>

                        <h5 class="mb-3 text-base font-semibold text-sky-900" @click="goToContent(content.id)">{{ content.title }}</h5>

                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <!-- 댓글 개수 -->
                                <div v-if="content.comments_count !== undefined" class="flex items-center gap-1.5 text-sky-700">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
                                        />
                                    </svg>
                                    <span class="text-xs font-medium">{{ content.comments_count }}</span>
                                </div>

                                <!-- 발행 날짜 -->
                                <div v-if="content.published_at" class="flex items-center gap-1.5 text-gray-500">
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        stroke-width="2"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                                        />
                                    </svg>
                                    <span class="text-xs font-medium">{{ formatDate(content.published_at) }}</span>
                                </div>
                            </div>

                            <!-- 자세히 버튼 -->
                            <a
                                @click="goToContent(content.id)"
                                class="inline-block cursor-pointer rounded-md bg-sky-600 px-4 py-1.5 text-xs font-medium text-white transition-colors hover:bg-sky-700 active:bg-sky-800"
                            >
                                자세히
                            </a>
                        </div>
                    </div>
                </div>

                <!-- 3개 콘텐츠마다 쿠팡 광고 삽입 - 주석 처리 -->
                <!-- <CoupangAd v-if="shouldShowAd(index)" :position="index" /> -->
            </template>

            <!-- Infinite scroll trigger -->
            <div ref="loadMoreTrigger" class="py-4 text-center">
                <div
                    v-if="isLoading"
                    class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-blue-600 border-t-transparent"
                    role="status"
                >
                    <span class="sr-only">로딩 중...</span>
                </div>
                <div v-else-if="!hasMore" class="text-sm text-gray-500">모든 소식을 불러왔습니다</div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.preview-text {
    white-space: pre-line;
    word-break: keep-all;
    line-height: 1.6;
    max-height: 200px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 8;
    -webkit-box-orient: vertical;
}
</style>
