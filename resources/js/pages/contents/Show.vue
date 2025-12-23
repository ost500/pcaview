<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Head } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps<{ contents: Contents }>();

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
            name: 'PCAview 주보고',
            logo: {
                '@type': 'ImageObject',
                url: window.location.origin + '/og_image.png',
            },
        },
        description: 'PCAview ' + props.contents.title + ' - ' + (props.contents.department?.name || '교회 소식'),
        inLanguage: 'ko-KR',
        articleSection: props.contents.department?.name || 'PCAview 소식',
        keywords: 'PCAview, PCAview 주보, 교회, 주보, ' + (props.contents.department?.name || '') + ', ' + props.contents.title,
    });
    document.head.appendChild(script);
});
</script>

<template>
    <div>
        <Head :title="`PCAview ${contents.title}`">
            <!-- Basic Meta Tags -->
            <meta name="description" :content="`PCAview ${contents.title} - ${contents.department?.name || '교회 소식'}`" />
            <meta name="keywords" :content="`PCAview, PCAview 주보, 교회, 주보, ${contents.department?.name || ''}, ${contents.title}`" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="`https://jubogo.com/contents/${contents.id}`" />
            <meta property="og:title" :content="`PCAview ${contents.title}`" />
            <meta property="og:description" :content="`PCAview ${contents.title} - ${contents.department?.name || '교회 소식'}`" />
            <meta property="og:image" :content="contents.thumbnail_url" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:site_name" content="주보고" />
            <meta property="article:published_time" :content="contents.published_at" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" :content="`https://jubogo.com/contents/${contents.id}`" />
            <meta name="twitter:title" :content="`PCAview ${contents.title}`" />
            <meta name="twitter:description" :content="`PCAview ${contents.title} - ${contents.department?.name || '교회 소식'}`" />
            <meta name="twitter:image" :content="contents.thumbnail_url" />

            <!-- Canonical URL -->
            <link rel="canonical" :href="`https://jubogo.com/contents/${contents.id}`" />
        </Head>
        <Header title="주보" :backbutton="true"></Header>

        <div class="mx-auto w-full max-w-2xl">
            <div class="space-y-4">
                <div class="page-content py-10">
                    <div class="container pt-0 pb-0">
                        <div class="rounded-lg bg-white shadow">
                            <!-- Department 정보 -->
                            <div v-if="contents.department" class="flex items-center gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                    <img
                                        :src="contents.department.icon_image"
                                        :alt="contents.department.name"
                                        class="h-full w-full object-cover"
                                    />
                                </div>
                                <span class="text-sm font-semibold text-sky-900">{{ contents.department.name }}</span>
                            </div>


                            <!-- Title -->
                            <div class="border-b border-gray-200 px-4 py-3">
                                <h5 class="mb-0 text-lg font-semibold">{{ contents.title }}</h5>
                            </div>

                            <!-- 이미지 또는 비디오 -->
                            <div v-if="contents.file_type != 'YOUTUBE' && contents.file_type != 'HTML'">
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
                                <img
                                    :src="contents.thumbnail_url"
                                    class="w-full"
                                    :alt="contents.title"
                                    loading="lazy"
                                    decoding="async"
                                />

                                <div class="content-body" v-html="contents.body"></div>
                            </div>

                            <!-- 외부 링크 (뉴스 타입인 경우) -->
                            <div v-if="contents.type === 'news' && contents.file_url" class="px-4 pb-4">
                                <a
                                    :href="contents.file_url"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-blue-700"
                                >
                                    원문 보기
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
                                        />
                                    </svg>
                                </a>
                            </div>

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
