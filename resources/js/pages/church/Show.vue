<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { Church } from '@/types/church';

const props = defineProps<{ church: Church }>();
</script>

<template>
    <Header title="교회" :backbutton="true"></Header>

    <div class="page-content space-top p-b60">
        <div class="container pt-0">
            <div class="profile-area">
                <div class="main-profile">
                    <div class="about-profile">
                        <div class="media rounded-circle">
                            <img :src="church.icon_url" alt="profile-image" />
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
                        <h6 class="title">약도</h6>
                        <p class="para-text">
                            <img :src="church.address_url" :alt="church.name + '약도'">
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
