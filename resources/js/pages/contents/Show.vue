<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { Contents } from '@/types/contents';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import { route } from 'ziggy-js';

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
    // Kakao AdFit 광고 로드
    // DOM이 완전히 렌더링된 후 실행
    setTimeout(() => {
        loadKakaoAd();
    }, 100);

    // JSON-LD structured data 추가
    const script = document.createElement('script');
    script.type = 'application/ld+json';
    script.text = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Article',
        'mainEntityOfPage': {
            '@type': 'WebPage',
            '@id': window.location.href
        },
        'headline': props.contents.title,
        'image': props.contents.thumbnail_url,
        'datePublished': props.contents.published_at,
        'dateModified': props.contents.updated_at || props.contents.published_at,
        'author': {
            '@type': 'Organization',
            'name': props.contents.department?.name || '명성교회 주보고'
        },
        'publisher': {
            '@type': 'Organization',
            'name': '명성교회 주보고',
            'logo': {
                '@type': 'ImageObject',
                'url': window.location.origin + '/og_image.png'
            }
        },
        'description': props.contents.title + ' - ' + (props.contents.department?.name || '교회 소식'),
        'inLanguage': 'ko-KR',
        'articleSection': props.contents.department?.name || '교회 소식',
        'keywords': '교회, 주보, ' + (props.contents.department?.name || '') + ', ' + props.contents.title
    });
    document.head.appendChild(script);
});
</script>

<template>
    <div>
        <Head :title="contents.title + ' - 명성교회 주보고'">
            <!-- Basic Meta Tags -->
            <meta name="description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta name="keywords" :content="'교회, 주보, ' + (contents.department?.name || '') + ', ' + contents.title" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="`https://jubogo.com/contents/${contents.id}`" />
            <meta property="og:title" :content="contents.title" />
            <meta property="og:description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta property="og:image" :content="contents.thumbnail_url" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:site_name" content="명성교회 주보고" />
            <meta property="article:published_time" :content="contents.published_at" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" :content="`https://jubogo.com/contents/${contents.id}`" />
            <meta name="twitter:title" :content="contents.title" />
            <meta name="twitter:description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta name="twitter:image" :content="contents.thumbnail_url" />

            <!-- Canonical URL -->
            <link rel="canonical" :href="`https://jubogo.com/contents/${contents.id}`" />
        </Head>
        <Header title="주보" :backbutton="true"></Header>

        <div class="page-content space-top p-b60">
            <div class="p-b0 container">
                <div class="title-bar">
                    <h6 class="title">소식</h6>
                </div>
            </div>
            <iframe
                src="https://ads-partners.coupang.com/widgets.html?id=927016&template=carousel&trackingCode=AF7527668&subId=&width=680&height=140&tsource="
                width="100%"
                height="140"
                frameborder="0"
                scrolling="no"
                referrerpolicy="unsafe-url"
                browsingtopics
            ></iframe>

            <div class="container p-0">
                <ins
                    class="kakao_ad_area"
                    style="display: block"
                    data-ad-unit="DAN-bE302RQ73kwLuzKI"
                    data-ad-width="320"
                    data-ad-height="50"
                ></ins>
                <div class="row" id="contentArea">
                    <div class="col-12">
                        <div class="card">
                            <!-- Department 정보 -->
                            <div v-if="contents.department" class="card-header department-header">
                                <div class="d-flex align-items-center">
                                    <div class="department-icon">
                                        <img :src="contents.department.icon_image" :alt="contents.department.name" />
                                    </div>
                                    <span class="department-name">{{ contents.department.name }}</span>
                                </div>
                            </div>

                            <!-- Title -->
                            <div class="card-header">
                                <h5 class="card-title mb-0">{{ contents.title }}</h5>
                            </div>

                            <!-- 이미지 또는 비디오 -->
                            <div v-if="contents.file_type != 'YOUTUBE' && contents.file_type != 'HTML'">
                                <div v-for="(image, index) in contents.images" v-bind:key="image.id">
                                    <img
                                        :src="image.file_url"
                                        @click="open(index)"
                                        class="card-img-top"
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
                            <div class="card-body" v-if="contents.body">
                                <div class="content-body" v-html="contents.body"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.content-body {
    line-height: 1.6;
    word-break: keep-all;
}

.content-body :deep(p) {
    margin-bottom: 1rem;
}

.content-body :deep(table) {
    width: 100%;
    margin: 1rem 0;
    border-collapse: collapse;
}

.content-body :deep(table td),
.content-body :deep(table th) {
    border: 1px solid #ddd;
    padding: 8px;
    text-align: left;
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
