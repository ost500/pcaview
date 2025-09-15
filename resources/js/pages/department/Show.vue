<script setup lang="ts">
import ContentsList from '@/components/contents/ContentsList.vue';
import Header from '@/components/template/Header.vue';
import { Contents } from '@/types/contents';
import { Department } from '@/types/department';
import { Pagination } from '@/types/pagination';

const props = defineProps<{ department: Department; contents: Pagination<Contents> }>();
</script>

<template>
    <Header :title="'부서 / ' + department.name" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <div class="main-profile">
                    <div class="about-profile">
                        <div class="media rounded-circle">
                            <img :src="department.icon_image" alt="profile-image" />
                            <svg class="radial-progress m-b20" data-percentage="100" viewBox="0 0 80 80">
                                <circle class="incomplete" cx="40" cy="40" r="35"></circle>
                                <circle class="complete" cx="40" cy="40" r="35" style="stroke-dashoffset: 0"></circle>
                            </svg>
                        </div>
                    </div>
                    <div class="profile-detail">
                        <h4 class="name">{{ department.name }}</h4>
                    </div>
                </div>

                <div class="detail-bottom-area">
                    <div class="about">
                        <ContentsList :contents="contents"></ContentsList>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
