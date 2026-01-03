<script setup lang="ts">
import Logo from '@/components/template/Logo.vue';
import MenuBar from '@/components/template/MenuBar.vue';
import { ArrowLeft } from 'lucide-vue-next';

interface Church {
    id: number;
    name: string;
    slug: string;
    icon_image?: string | null;
}

interface Props {
    title: string;
    backbutton?: boolean;
    count?: number;
    church?: Church;
}

const props = defineProps<Props>();

// 뒤로가기 함수
const goBack = () => {
    window.history.back();
};
</script>

<template>
    <!-- Fixed header with shadow -->
    <header class="fixed top-0 right-0 left-0 z-40 bg-white shadow-sm">
        <div class="mx-auto max-w-screen-xl px-4">
            <div class="flex h-14 items-center justify-between sm:h-16">
                <!-- Left content -->
                <div class="flex items-center gap-2 sm:gap-3">
                    <button
                        v-if="backbutton"
                        @click="goBack"
                        class="flex h-10 w-10 min-w-[2.5rem] items-center justify-center rounded-full text-gray-700 transition-colors active:bg-gray-200 sm:hover:bg-gray-100"
                        aria-label="뒤로가기"
                    >
                        <ArrowLeft :size="20" />
                    </button>

                    <!-- Church 아이콘과 이름 또는 기본 Logo -->
                    <template v-if="church">
                        <img
                            v-if="church.icon_image"
                            :src="church.icon_image"
                            :alt="church.name"
                            class="h-8 w-8 rounded-full object-cover sm:h-10 sm:w-10"
                        />
                        <div v-else class="h-8 w-8 rounded-full bg-gray-200 sm:h-10 sm:w-10"></div>
                        <h1 class="truncate text-base font-semibold text-gray-900 sm:text-lg">{{ church.name }}</h1>
                    </template>
                    <template v-else>
                        <Logo />
                        <h1 class="truncate text-base font-semibold text-gray-900 sm:text-lg">{{ props.title }}</h1>
                    </template>
                </div>

                <!-- Middle content (placeholder for future features) -->
                <div class="flex-1"></div>

                <!-- Right content (placeholder for future features) -->
                <div class="flex items-center gap-2"></div>
            </div>
        </div>
    </header>

    <MenuBar />
</template>

<style scoped>
/* Component styles are handled by Tailwind classes */
</style>
