<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import type { Tag } from '@/types/contents';
import { Head } from '@inertiajs/vue3';

interface Trend {
    id: number;
    title: string;
    description: string;
    link: string;
    image_url: string | null;
    traffic_count: number;
    pub_date: string;
    picture: string | null;
    picture_source: string | null;
    news_items: Array<{
        title: string;
        link: string;
        snippet: string;
        source: string;
    }> | null;
    tags?: Tag[];
}

const props = defineProps<{ trend: Trend }>();
</script>

<template>
    <Head :title="trend.title + ' - 트렌드'">
        <!-- Basic Meta Tags -->
        <meta name="description" :content="trend.description" />
        <meta
            name="keywords"
            :content="
                '트렌드, ' +
                trend.title +
                ', 인기검색어' +
                (trend.tags && trend.tags.length > 0 ? ', ' + trend.tags.map((t) => t.name).join(', ') : '')
            "
        />

        <!-- Open Graph -->
        <meta property="og:type" content="article" />
        <meta property="og:url" :content="safeRoute('trend.show', { keyword: trend.title })" />
        <meta property="og:title" :content="trend.title + ' - 트렌드'" />
        <meta property="og:description" :content="trend.description" />
        <meta property="og:image" :content="trend.image_url || trend.picture || '/images/default-og.jpg'" />
        <meta property="og:site_name" content="주보고" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:url" :content="safeRoute('trend.show', { keyword: trend.title })" />
        <meta name="twitter:title" :content="trend.title + ' - 트렌드'" />
        <meta name="twitter:description" :content="trend.description" />
        <meta name="twitter:image" :content="trend.image_url || trend.picture || '/images/default-og.jpg'" />

        <!-- Canonical URL -->
        <link rel="canonical" :href="safeRoute('trend.show', { keyword: trend.title })" />
    </Head>
    <Header :title="'트렌드 / ' + trend.title" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <!-- Trend Image -->
                <div v-if="trend.picture || trend.image_url" class="trend-image mb-4">
                    <img :src="trend.picture || trend.image_url" :alt="trend.title" class="w-full rounded-lg" loading="lazy" decoding="async" />
                    <p v-if="trend.picture_source" class="mt-2 text-sm text-gray-500">출처: {{ trend.picture_source }}</p>
                </div>

                <!-- Trend Info -->
                <div class="trend-info mb-6">
                    <h1 class="mb-2 text-2xl font-bold">{{ trend.title }}</h1>
                    <p class="mb-4 text-gray-600">{{ trend.description }}</p>
                    <div class="flex items-center gap-4 text-sm text-gray-500">
                        <span>🔥 검색량: {{ trend.traffic_count?.toLocaleString() || 'N/A' }}</span>
                        <span>📅 {{ new Date(trend.pub_date).toLocaleDateString('ko-KR') }}</span>
                    </div>

                    <!-- Tags -->
                    <div v-if="trend.tags && trend.tags.length > 0" class="mt-3 flex flex-wrap gap-2">
                        <span
                            v-for="tag in trend.tags"
                            :key="tag.id"
                            class="inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100"
                        >
                            #{{ tag.name }}
                        </span>
                    </div>

                    <a
                        v-if="trend.link"
                        :href="trend.link"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-4 inline-block text-blue-600 hover:underline"
                    >
                        원문 보기 →
                    </a>
                </div>

                <!-- News Items -->
                <div v-if="trend.news_items && trend.news_items.length > 0" class="news-items">
                    <h2 class="mb-4 text-xl font-bold">관련 뉴스</h2>
                    <div class="space-y-4">
                        <div v-for="(item, index) in trend.news_items" :key="index" class="news-item border-b pb-4">
                            <h3 class="mb-1 font-semibold">
                                <a :href="item.link" target="_blank" rel="noopener noreferrer" class="hover:text-blue-600">
                                    {{ item.title }}
                                </a>
                            </h3>
                            <p class="mb-2 text-sm text-gray-600">{{ item.snippet }}</p>
                            <p class="text-xs text-gray-500">{{ item.source }}</p>
                        </div>
                    </div>
                </div>

                <BusinessInfo class="mt-6" />
            </div>
        </div>
    </div>
</template>

<style scoped>
.trend-image img {
    max-height: 400px;
    object-fit: cover;
}
</style>
