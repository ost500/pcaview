<script setup lang="ts">
import ContentsList from '@/components/contents/ContentsList.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { Contents } from '@/types/contents';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps<{
    contents: Contents;
    relatedContents: Contents[];
}>();

// URL 파라미터로 헤더 숨김 여부 확인
const hideHeader = ref(false);
if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    hideHeader.value = urlParams.get('hideHeader') === 'true';
}

const page = usePage();
const user = computed(() => page.props.auth.user);

const images = props.contents.images?.map((image) => image.file_url) || [];
const showViewer = ref(false);
const index = ref(0);

function open(indexNumber: number) {
    index.value = indexNumber;
    showViewer.value = true;
}

function close() {
    showViewer.value = false;
}

// 댓글 기능
const commentBody = ref('');
const guestName = ref('');
const isSubmitting = ref(false);

const submitComment = () => {
    if (!commentBody.value.trim()) return;
    if (!user.value && !guestName.value.trim()) {
        alert('이름을 입력해주세요.');
        return;
    }

    isSubmitting.value = true;
    const data: any = { body: commentBody.value };
    if (!user.value) {
        data.guest_name = guestName.value;
    }

    router.post(safeRoute('comments.store', { content: props.contents.id }), data, {
        preserveScroll: true,
        onSuccess: () => {
            commentBody.value = '';
            if (!user.value) {
                guestName.value = '';
            }
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};

const deleteComment = (commentId: number) => {
    if (!confirm('댓글을 삭제하시겠습니까?')) return;

    router.delete(safeRoute('comments.destroy', { comment: commentId }), {
        preserveScroll: true,
    });
};

const deleteContents = () => {
    if (!confirm('정말로 이 콘텐츠를 삭제하시겠습니까?')) return;

    router.post(
        `/contents/${props.contents.id}/delete`,
        {},
        {
            onSuccess: () => {
                window.location.href = safeRoute('home');
            },
        },
    );
};

const canDeleteContents = computed(() => {
    return user.value && props.contents.user_id === user.value.id;
});

const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diff = now.getTime() - date.getTime();
    const minutes = Math.floor(diff / 60000);
    const hours = Math.floor(diff / 3600000);
    const days = Math.floor(diff / 86400000);

    if (minutes < 1) return '방금 전';
    if (minutes < 60) return `${minutes}분 전`;
    if (hours < 24) return `${hours}시간 전`;
    if (days < 7) return `${days}일 전`;

    return date.toLocaleDateString('ko-KR');
};

const formatDateTime = (dateString: string) => {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hours = String(date.getHours()).padStart(2, '0');
    const minutes = String(date.getMinutes()).padStart(2, '0');

    return `${year}.${month}.${day} ${hours}:${minutes}`;
};

function goToDepartment(id: number) {
    if (typeof window !== 'undefined') {
        window.location.href = safeRoute('department.show', { id: id });
    }
}

// 뉴스 본문 표시 (AI 리라이팅된 경우 전체, 아니면 일부만 표시)
const displayBody = computed(() => {
    const newsTypes = ['nate_news', 'news', 'naver_news'];
    if (!newsTypes.includes(props.contents.type) || !props.contents.body) {
        return props.contents.body;
    }

    // AI 리라이팅된 콘텐츠는 전체 본문 표시
    if (props.contents.is_ai_rewritten) {
        return props.contents.body;
    }

    // SSR에서는 원본 반환 (클라이언트에서 처리)
    if (import.meta.env.SSR) {
        return props.contents.body;
    }

    // 저작권 보호: AI 리라이팅되지 않은 뉴스는 일부만 표시
    // 임시 div를 만들어서 텍스트 추출
    const tempDiv = document.createElement('div');
    tempDiv.innerHTML = props.contents.body;

    // 저작권 보호: 모든 이미지 제거
    const images = tempDiv.querySelectorAll('img, picture, figure');
    images.forEach((img) => img.remove());

    const fullText = tempDiv.textContent || tempDiv.innerText || '';
    const textLength = fullText.length;

    if (textLength === 0) {
        return props.contents.body;
    }

    // 최대 200자로 제한 (2-3줄 정도)
    const maxLength = 200;

    // HTML 요소들을 순회하면서 텍스트 길이 누적
    let currentLength = 0;
    let truncatePoint: Node | null = null;

    const walker = document.createTreeWalker(tempDiv, NodeFilter.SHOW_ALL, null);

    let currentNode: Node | null;
    while ((currentNode = walker.nextNode())) {
        if (currentNode.nodeType === Node.TEXT_NODE) {
            const textContent = currentNode.textContent || '';
            currentLength += textContent.length;

            if (currentLength >= maxLength) {
                truncatePoint = currentNode;
                break;
            }
        }
    }

    if (truncatePoint) {
        // truncatePoint 이후의 모든 노드 제거
        let nodeToRemove = truncatePoint.nextSibling;
        while (nodeToRemove) {
            const next = nodeToRemove.nextSibling;
            nodeToRemove.parentNode?.removeChild(nodeToRemove);
            nodeToRemove = next;
        }

        // 상위 노드들도 확인하면서 이후 형제 노드 제거
        let parent = truncatePoint.parentNode;
        while (parent && parent !== tempDiv) {
            let sibling = parent.nextSibling;
            while (sibling) {
                const next = sibling.nextSibling;
                sibling.parentNode?.removeChild(sibling);
                sibling = next;
            }
            parent = parent.parentNode;
        }
    }

    return tempDiv.innerHTML;
});

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
    // Kakao AdFit 광고 로드 - 주석 처리
    // DOM이 완전히 렌더링된 후 실행
    /*
    setTimeout(() => {
        loadKakaoAd();
    }, 100);
    */

    // JSON-LD structured data 추가
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.text = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        mainEntityOfPage: {
            '@type': 'WebPage',
            '@id': window.location.href,
        },
        headline: props.contents.title,
        image: props.contents.thumbnail_url,
        datePublished: props.contents.published_at,
        dateModified: props.contents.updated_at || props.contents.published_at,
        author: {
            '@type': 'Organization',
            name: props.contents.department?.name || 'PCAview',
        },
        publisher: {
            '@type': 'Organization',
            name: 'PCAview',
            logo: {
                '@type': 'ImageObject',
                url: window.location.origin + '/og_image.png',
            },
        },
        description: 'PCAview ' + props.contents.title + ' - ' + (props.contents.department?.name || '소식'),
        inLanguage: 'ko-KR',
        articleSection: props.contents.department?.name || 'PCAview 소식',
        keywords:
            'PCAview, 피카뷰, 트렌드, 뉴스, ' +
            (props.contents.department?.name || '') +
            ', ' +
            props.contents.title +
            (props.contents.tags && props.contents.tags.length > 0 ? ', ' + props.contents.tags.map((t) => t.name).join(', ') : ''),
    });
    document.head.appendChild(script);
});
</script>

<template>
    <div>
        <Head :title="`${contents.department?.name || 'PCAview'} - ${contents.title}`">
            <!-- Basic Meta Tags -->
            <meta name="description" :content="`PCAview ${contents.title} - ${contents.department?.name}`" />
            <meta
                name="keywords"
                :content="`PCAview, PCAview ${contents.department?.name || ''}, ${contents.title}${contents.tags && contents.tags.length > 0 ? ', ' + contents.tags.map((t) => t.name).join(', ') : ''}`"
            />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="`https://pcaview.com/contents/${contents.id}`" />
            <meta property="og:title" :content="`PCAview ${contents.title}`" />
            <meta property="og:description" :content="`PCAview ${contents.title} - ${contents.department?.name}`" />
            <meta property="og:image" :content="contents.thumbnail_url" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:site_name" content="PCAview" />
            <meta property="article:published_time" :content="contents.published_at" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" :content="`https://pcaview.com/contents/${contents.id}`" />
            <meta name="twitter:title" :content="`PCAview ${contents.title}`" />
            <meta name="twitter:description" :content="`PCAview ${contents.title} - ${contents.department?.name}`" />
            <meta name="twitter:image" :content="contents.thumbnail_url" />

            <!-- Canonical URL -->
            <link rel="canonical" :href="`https://pcaview.com/contents/${contents.id}`" />
        </Head>
        <Header v-if="!hideHeader" title="VIEW"></Header>

        <div class="mx-auto w-full max-w-2xl" :class="{ 'pt-3': hideHeader }">
            <div class="space-y-4 pb-20">
                <div class="page-content">
                    <div class="container pt-0 pb-0">
                        <div class="rounded-lg bg-white shadow">
                            <!-- User 정보 (user_id가 있는 경우) 또는 Church 정보 (없는 경우) -->
                            <div
                                v-if="contents.user || contents.church"
                                class="flex items-center justify-between gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                        <img
                                            v-if="contents.user"
                                            :src="contents.user.profile_photo"
                                            :alt="contents.user.name"
                                            class="h-full w-full object-cover"
                                        />
                                        <img
                                            v-else-if="contents.church"
                                            :src="contents.church.icon_url || '/pcaview_icon.png'"
                                            :alt="contents.church.name"
                                            class="h-full w-full object-cover"
                                        />
                                    </div>
                                    <span class="text-sm font-semibold text-sky-900">
                                        {{ contents.user ? contents.user.name : contents.church ? contents.church.name : '' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <!-- 작성 시간 -->
                                    <span v-if="contents.published_at" class="text-xs text-gray-500">
                                        {{ formatDate(contents.published_at) }}
                                    </span>
                                    <!-- 삭제 버튼 (작성자만) -->
                                    <button
                                        v-if="canDeleteContents"
                                        @click="deleteContents"
                                        class="text-xs text-red-600 transition-colors hover:text-red-700"
                                        title="삭제"
                                    >
                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Department 정보 -->
                            <div
                                v-if="contents.department"
                                @click="goToDepartment(contents.department.id)"
                                class="flex cursor-pointer items-center gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm transition-colors hover:bg-sky-50"
                            >
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                    <img
                                        :src="contents.department.icon_image || '/pcaview_icon.png'"
                                        :alt="contents.department.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <span class="text-sm font-semibold text-sky-900">{{ contents.department.name }}</span>
                            </div>

                            <!-- Title -->
                            <div class="border-b border-gray-200 px-4 py-3">
                                <h5 class="mb-2 text-lg font-semibold">{{ contents.title }}</h5>
                                <p class="text-xs text-gray-500">{{ formatDateTime(contents.published_at) }}</p>

                                <!-- Tags -->
                                <div v-if="contents.tags && contents.tags.length > 0" class="mt-3 flex flex-wrap gap-2">
                                    <span
                                        v-for="tag in contents.tags"
                                        :key="tag.id"
                                        class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100"
                                    >
                                        #{{ tag.name }}
                                    </span>
                                </div>
                            </div>

                            <!-- 동영상 또는 이미지 -->
                            <div v-if="contents.file_type != 'YOUTUBE' && contents.file_type != 'HTML'">
                                <!-- 동영상이 있는 경우 -->
                                <div v-if="contents.video_url" class="w-full">
                                    <video :src="contents.video_url" class="w-full" controls preload="metadata" />
                                </div>
                                <!-- 이미지가 있는 경우 -->
                                <div v-for="(image, index) in contents.images" v-bind:key="image.id">
                                    <img
                                        :src="image.file_url"
                                        @click="open(index)"
                                        class="w-full"
                                        :alt="contents.title"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                </div>
                                <VueEasyLightbox @hide="close" :visible="showViewer" :imgs="images" :index="index" />
                            </div>
                            <iframe
                                v-else-if="contents.file_type == 'YOUTUBE'"
                                width="100%"
                                height="315"
                                :src="'https://www.youtube.com/embed/' + contents.file_url"
                                title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>

                            <!-- HTML 본문 내용 -->
                            <div class="p-4" v-if="contents.body">
                                <div class="content-body" v-html="displayBody"></div>

                                <!-- 뉴스 저작권 안내 (AI 리라이팅되지 않은 경우만) -->
                                <div
                                    v-if="['nate_news', 'news', 'naver_news'].includes(contents.type) && contents.file_url && !contents.is_ai_rewritten"
                                    class="mt-5 rounded border-l-4 border-blue-500 bg-gray-50 p-4"
                                >
                                    <p class="mb-0 text-sm text-gray-600">저작권 보호를 위해 본문의 일부만 표시됩니다.</p>
                                    <a
                                        :href="contents.file_url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="mt-2 inline-flex items-center font-medium text-blue-600 transition hover:text-blue-700"
                                    >
                                        원문 보기 →
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- 댓글 섹션 -->
                        <div class="mt-4 rounded-lg bg-white shadow">
                            <div class="border-b border-gray-200 px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">
                                    댓글 <span class="text-sm text-gray-500">({{ contents.comments?.length || 0 }})</span>
                                </h3>
                            </div>

                            <!-- 댓글 작성 폼 -->
                            <div class="border-b border-gray-100 p-4">
                                <form @submit.prevent="submitComment" class="space-y-3">
                                    <!-- 비로그인 시 이름 입력 -->
                                    <div v-if="!user">
                                        <input
                                            v-model="guestName"
                                            type="text"
                                            placeholder="이름을 입력하세요"
                                            maxlength="50"
                                            class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                            :disabled="isSubmitting"
                                        />
                                    </div>
                                    <textarea
                                        v-model="commentBody"
                                        placeholder="댓글을 입력하세요..."
                                        rows="3"
                                        maxlength="1000"
                                        class="w-full rounded-lg border border-gray-300 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                        :disabled="isSubmitting"
                                    ></textarea>
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">{{ commentBody.length }} / 1000</span>
                                        <button
                                            type="submit"
                                            :disabled="isSubmitting || !commentBody.trim() || (!user && !guestName.trim())"
                                            class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 text-sm font-medium text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                                        >
                                            {{ isSubmitting ? '등록 중...' : '댓글 등록' }}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- 댓글 목록 -->
                            <div v-if="contents.comments && contents.comments.length > 0" class="divide-y divide-gray-100">
                                <div v-for="comment in contents.comments" :key="comment.id" class="px-4 py-4">
                                    <div class="flex items-start gap-3">
                                        <!-- 프로필 이미지 -->
                                        <img
                                            v-if="comment.user && comment.user.profile_photo"
                                            :src="comment.user.profile_photo"
                                            :alt="comment.display_name"
                                            class="h-10 w-10 flex-shrink-0 rounded-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-100 to-purple-100"
                                        >
                                            <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    fill-rule="evenodd"
                                                    d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                                    clip-rule="evenodd"
                                                />
                                            </svg>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="flex flex-col">
                                                    <div class="flex items-center gap-2">
                                                        <p class="text-sm font-medium text-gray-900">{{ comment.display_name }}</p>
                                                        <p class="text-xs text-gray-500">{{ formatDate(comment.created_at) }}</p>
                                                    </div>
                                                    <p class="mt-2 text-sm whitespace-pre-wrap text-gray-700">{{ comment.body }}</p>
                                                </div>
                                                <div class="flex flex-shrink-0 items-center gap-2">
                                                    <span v-if="comment.ip_last_digits" class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                        XXX.{{ comment.ip_last_digits }}
                                                    </span>
                                                    <button
                                                        v-if="(user && comment.user_id && user.id === comment.user_id) || !comment.user_id"
                                                        @click="deleteComment(comment.id)"
                                                        class="text-xs text-red-600 hover:text-red-700 hover:underline"
                                                    >
                                                        삭제
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- 댓글 없음 -->
                            <div v-else class="px-4 py-8 text-center text-sm text-gray-500">첫 번째 댓글을 작성해보세요!</div>
                        </div>

                        <!-- 관련 콘텐츠 -->
                        <div v-if="relatedContents && relatedContents.length > 0" class="mt-4">
                            <div class="mb-4 px-4 py-3">
                                <h3 class="text-base font-semibold text-gray-900">
                                    {{ contents.department ? contents.department.name + '의 ' : '' }}다른 소식
                                </h3>
                            </div>
                            <ContentsList :contents="relatedContents" />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.content-body {
    line-height: 1.6;
    word-break: keep-all;
    color: #000;
}

.content-body :deep(p) {
    margin-bottom: 1rem;
}

.content-body :deep(table) {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
    display: block;
    overflow-x: auto;
    white-space: nowrap;
}

.content-body :deep(table td),
.content-body :deep(table th) {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
    white-space: normal;
    word-break: break-word;
}

.content-body :deep(table th) {
    background-color: #f2f2f2;
    font-weight: bold;
}

.content-body :deep(a) {
    color: #007bff;
    text-decoration: none;
}

.content-body :deep(a:hover) {
    text-decoration: underline;
}

.content-body :deep(img) {
    max-width: 100%;
    height: auto;
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
    flex-shrink: 0;
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

/* 가로 스크롤 방지 - 광고 iframe */
iframe {
    max-width: 100%;
    overflow: hidden;
}
</style>
