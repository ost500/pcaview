<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Head } from '@inertiajs/vue3';
import { ref, onMounted } from 'vue';
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
});
</script>

<template>
    <div>
        <Head :title="contents.title + ' - 주보고'">
            <!-- Basic Meta Tags -->
            <meta name="description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta name="keywords" :content="'교회, 주보, ' + (contents.department?.name || '') + ', ' + contents.title" />

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="article" />
            <meta property="og:url" :content="route('contents.show', { id: contents.id })" />
            <meta property="og:title" :content="contents.title" />
            <meta property="og:description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta property="og:image" :content="contents.thumbnail_url" />
            <meta property="og:image:width" content="1200" />
            <meta property="og:image:height" content="630" />
            <meta property="og:site_name" content="주보고" />
            <meta property="article:published_time" :content="contents.published_at" />

            <!-- Twitter Card -->
            <meta name="twitter:card" content="summary_large_image" />
            <meta name="twitter:url" :content="route('contents.show', { id: contents.id })" />
            <meta name="twitter:title" :content="contents.title" />
            <meta name="twitter:description" :content="contents.title + ' - ' + (contents.department?.name || '교회 소식')" />
            <meta name="twitter:image" :content="contents.thumbnail_url" />

            <!-- Canonical URL -->
            <link rel="canonical" :href="route('contents.show', { id: contents.id })" />

            <!-- Schema.org JSON-LD -->
            <script type="application/ld+json">
                {
                    "@context": "https://schema.org",
                    "@type": "Article",
                    "headline": "{{ contents.title }}",
                    "image": "{{ contents.thumbnail_url }}",
                    "datePublished": "{{ contents.published_at }}",
                    "dateModified": "{{ contents.updated_at }}",
                    "author": {
                        "@type": "Organization",
                        "name": "{{ contents.department?.name || '주보고' }}"
                    },
                    "publisher": {
                        "@type": "Organization",
                        "name": "주보고",
                        "logo": {
                            "@type": "ImageObject",
                            "url": "{{ url('/jubogo_favicon.ico') }}"
                        }
                    },
                    "description": "{{ contents.title }}"
                }
            </script>
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
                            <div class="card-header">
                                <h5 class="card-title">{{ contents.title }}</h5>
                            </div>
                            <div v-if="contents.file_type != 'YOUTUBE'">
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
                                v-else
                                width="100%"
                                height="315"
                                :src="'https://www.youtube.com/embed/' + contents.file_url"
                                title="YouTube video player"
                                frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                referrerpolicy="strict-origin-when-cross-origin"
                                allowfullscreen
                            ></iframe>

                            <div class="card-body">
                                <!--                                <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
