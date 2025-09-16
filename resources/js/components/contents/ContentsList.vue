<script setup lang="ts">
import { Contents } from '@/types/contents';
import { Pagination } from '@/types/pagination';
import { route } from 'ziggy-js';

const props = defineProps<{ contents: Pagination<Contents> }>();

function goToContent(id: number) {
    window.location.href = route('contents.show', { id: id });
}
</script>

<template>
    <div class="row">
        <div v-for="content in props.contents.data" class="col-12" v-bind:key="content.id">
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
                        <a :href="route('contents.show', { id: content.id })" class="btn btn-primary text-right btn-sm">자세히</a>
                    </p>
                </div>
            </div>
        </div>

        <nav class="text-center">
            <ul class="pagination pagination-sm pagination-gutter pagination-info justify-content-center">
                <li class="page-item page-indicator">
                    <a class="page-link" v-if="contents.prev_page_url" :href="contents.prev_page_url">
                        <i class="icon feather icon-chevron-left"></i
                    ></a>
                </li>
                <li class="page-item page-indicator">
                    <a class="page-link" v-if="contents.next_page_url" :href="contents.next_page_url">
                        <i class="icon feather icon-chevron-right"></i
                    ></a>
                </li>
            </ul>
        </nav>
    </div>
</template>

<style scoped>
.page-link {
    width: 2.5rem !important;
    height: 2.5rem !important;
    font-size: 1.5rem !important;
}
</style>
