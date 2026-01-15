<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 px-3 py-4 sm:px-4 sm:py-12">
        <div class="w-full max-w-md">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-6 sm:px-8 sm:py-10">
                    <Link :href="home()" class="mb-3 flex justify-center sm:mb-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-full bg-white transition-transform hover:scale-110 sm:h-16 sm:w-16">
                            <img src="/pcaview_icon.png" alt="PCAview Logo" class="h-9 w-9 object-contain sm:h-12 sm:w-12" />
                        </div>
                    </Link>
                    <h1 class="text-center text-xl font-bold text-white sm:text-2xl">{{ title }}</h1>
                    <p v-if="description" class="mt-1.5 text-center text-xs text-white/90 sm:mt-2 sm:text-sm">{{ description }}</p>
                </div>

                <!-- Form Section -->
                <div class="px-4 py-5 sm:px-8 sm:py-8">
                    <slot />
                </div>
            </div>

            <!-- Footer Text -->
            <p class="mt-4 text-center text-xs text-gray-600 sm:mt-6 sm:text-sm">
                빠르게 돌아가는 세상을 바라보는 뷰 <span class="font-semibold text-blue-600">피카뷰</span>
            </p>
        </div>
    </div>
</template>
