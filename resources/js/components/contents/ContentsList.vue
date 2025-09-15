<script setup lang="ts">

import { route } from 'ziggy-js';
import { Pagination } from '@/types/pagination';
import { Contents } from '@/types/contents';

const props = defineProps<{ contents: Pagination<Contents> }>();

function goToContent(id: number) {
    window.location.href = route('contents.show', { id: id });
}

</script>

<template>
    <div class="row" >
        <div v-for="content in props.contents.data" class="col-12" v-bind:key="content.id">
            <div class="card" @click="goToContent(content.id)">
                <div class="card-header">
                    <h5 class="card-title">{{ content.title }}</h5>
                </div>
                <img :src="content.thumbnail_url" class="card-img-top" alt="..." />
                <div class="card-body">
                    <!--                            <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                    <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                    <p class="mb-0 text-right">
                        <a :href="route('contents.show', { id: content.id })" class="btn btn-primary text-right">자세히</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

</template>

<style scoped>

</style>
