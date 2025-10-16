<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import { route } from 'ziggy-js';
import { Department } from '@/types/department';

const props = defineProps<{ departments: Department[] }>();

function goToContent(id: number) {
    window.location.href = route('department.show', { id: id });
}
</script>

<template>
    <div>
        <Header title="부서"></Header>

        <div class="page-content space-top p-b60">
            <div class="container">
                <div class="row g-3">
                    <div v-for="department in departments" class="col-6" v-bind:key="department.id">
                        <div class="card" @click="goToContent(department.id)">
                            <div class="card-header">
                                <h5 class="card-title">{{ department.name }}</h5>
                            </div>
                            <img :src="department.icon_image" class="card-img-top" alt="..." />
                            <div class="card-body">
                                <!--                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>-->
                            </div>
                        </div>
                    </div>
                </div>
                <BusinessInfo class="mt-3" />
            </div>
        </div>
    </div>
</template>

<style scoped></style>
