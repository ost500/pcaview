<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { Church } from '@/types/church';
import { ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import { Head } from '@inertiajs/vue3';
import { safeRoute } from '@/composables/useSafeRoute';

const props = defineProps<{ church: Church }>();

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
</script>

<template>
    <Head :title="church.name + ' 주보고'">
        <!-- Basic Meta Tags -->
        <meta name="description" :content="church.name + ' 교회 정보 - 예배시간과 약도를 확인하세요.'" />
        <meta name="keywords" :content="'교회, ' + church.name + ', 예배시간, 교회 위치, 약도'" />

        <!-- Open Graph -->
        <meta property="og:type" content="place" />
        <meta property="og:url" :content="safeRoute('church.show', { id: church.id })" />
        <meta property="og:title" :content="church.name + ' 주보고'" />
        <meta property="og:description" :content="church.name + ' 교회 정보 - 예배시간과 약도를 확인하세요.'" />
        <meta property="og:image" :content="church.icon_url" />
        <meta property="og:site_name" content="주보고" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary" />
        <meta name="twitter:url" :content="safeRoute('church.show', { id: church.id })" />
        <meta name="twitter:title" :content="church.name + ' 주보고'" />
        <meta name="twitter:description" :content="church.name + ' 교회 정보 - 예배시간과 약도를 확인하세요.'" />
        <meta name="twitter:image" :content="church.icon_url" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="safeRoute('church.show', { id: church.id })" />

        <!-- Schema.org JSON-LD -->
        <script type="application/ld+json" v-html="JSON.stringify({
            '@context': 'https://schema.org',
            '@type': 'Church',
            'name': church.name,
            'address': {
                '@type': 'PostalAddress',
                'addressLocality': church.address
            },
            'image': church.icon_url,
            'url': safeRoute('church.show', { id: church.id })
        })"></script>
    </Head>
    <Header :title="'교회 / ' + church.name" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <div class="main-profile">
                    <div class="about-profile">
                        <div class="media rounded-circle">
                            <img :src="church.icon_url" :alt="church.name + ' 아이콘'" loading="lazy" decoding="async" />
                            <svg class="radial-progress m-b20" data-percentage="100" viewBox="0 0 80 80">
                                <circle class="incomplete" cx="40" cy="40" r="35"></circle>
                                <circle class="complete" cx="40" cy="40" r="35" style="stroke-dashoffset: 0"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="profile-detail">
                        <h4 class="name">{{ church.name }}</h4>
                        <p class="mb-0"><i class="icon feather icon-map-pin me-1"></i>{{ church.address }}</p>
                    </div>
                </div>

                <div class="detail-bottom-area">
                    <div class="about">
                        <h6 class="title">예배시간</h6>
                        <p class="para-text">
                            <img :src="church.worship_time_image" @click="open(0)" :alt="church.name + ' 예배 시간'" loading="lazy" decoding="async" />
                        </p>
                    </div>
                </div>
                <div class="detail-bottom-area">
                    <div class="about">
                        <h6 class="title">약도</h6>
                        <p class="para-text">
                            <img :src="church.address_url" @click="open(1)" :alt="church.name + ' 약도'" loading="lazy" decoding="async" />
                        </p>
                    </div>
                </div>

                <VueEasyLightbox @hide="close" :visible="showViewer" :imgs="images" :index="index" />
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
