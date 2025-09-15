<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import MenuBar from '@/components/template/MenuBar.vue';
import { Contents } from '@/types/contents';
import { ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';

const props = defineProps<{ contents: Contents }>();

const images = props.contents.images.map(image => image.file_url);
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
    <div>
        <Header title="주보" :backbutton="true"></Header>

        <div class="page-content space-top p-b60">
            <div class="container">
                <div class="title-bar">
                    <h6 class="title">소식</h6>
                </div>
                <div class="row" id="contentArea">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title">{{ contents.title }}</h5>
                            </div>
                            <div v-for="(image, index) in contents.images" v-bind:key="image.id">
                                <img :src="image.file_url" @click="open(index)" class="card-img-top" :alt="contents.title" />
                            </div>
                            <VueEasyLightbox @hide="close" :visible="showViewer" :imgs="images" :index="index" />

                            <div class="card-body">
                                <!--                                <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>-->
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <MenuBar></MenuBar>
    </div>
</template>

<style scoped></style>
