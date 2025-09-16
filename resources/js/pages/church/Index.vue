<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { Church } from '@/types/church';
import { route } from 'ziggy-js';

const props = defineProps<{ churches: Church[] }>();

function goToContent(id: number) {
    window.location.href = route('church.show', { id: id });
}
</script>

<template>
    <div>
        <Header title="교회"></Header>

        <div class="page-content space-top p-b60">
            <div class="container">
                <div class="row g-3">
                    <div v-for="church in churches" class="col-12" v-bind:key="church.id">
                        <div class="card" @click="goToContent(church.id)">
                            <div class="card-header">
                                <h5 class="card-title">{{ church.name }}</h5>
                            </div>
                            <img :src="church.icon_url" class="card-img-top" alt="..." />
                            <div class="card-body">
                                <h5 class="card-title">{{ church.address }}</h5>
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                                <p class="mb-0 text-right">
                                    <a :href="route('church.show', { id: church.id })" class="btn btn-primary text-right btn-sm">자세히</a>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
