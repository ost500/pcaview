<script setup lang="ts">
import Logo from '@/components/template/Logo.vue';
import MenuBar from '@/components/template/MenuBar.vue';
import { ArrowLeft } from 'lucide-vue-next';

interface Props {
    title: string;
    backbutton?: boolean;
    count?: number;
}

const props = defineProps<Props>();

// 뒤로가기 함수
const goBack = () => {
    window.history.back();
};
</script>

<template>
    <!-- Fixed header with shadow -->
    <header class="fixed left-0 right-0 top-0 z-40 bg-white shadow-sm dark:bg-gray-800">
        <div class="container mx-auto px-4">
            <div class="flex h-16 items-center justify-between">
                <!-- Left content -->
                <div class="flex items-center gap-3">
                    <button
                        v-if="backbutton"
                        @click="goBack"
                        class="flex h-10 w-10 items-center justify-center rounded-full text-gray-700 transition-colors hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-700"
                        aria-label="뒤로가기"
                    >
                        <ArrowLeft :size="20" />
                    </button>

                    <Logo />

                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">{{ props.title }}</h1>
                </div>

                <!-- Middle content (placeholder for future features) -->
                <div class="flex-1"></div>

                <!-- Right content (placeholder for future features) -->
                <div class="flex items-center gap-2"></div>
            </div>
        </div>
    </header>

    <!-- Spacer to prevent content from going under fixed header -->
    <div class="h-16"></div>

    <MenuBar />
</template>

<style scoped>
/* Component styles are handled by Tailwind classes */
</style>
