<script setup lang="ts">
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-screen items-center justify-center bg-gradient-to-br from-blue-50 via-white to-purple-50 px-4 py-12">
        <div class="w-full max-w-md">
            <div class="overflow-hidden rounded-2xl bg-white shadow-2xl">
                <!-- Header Section -->
                <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-8 py-10">
                    <Link :href="home()" class="mb-4 flex justify-center">
                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-white transition-transform hover:scale-110">
                            <img src="/pcaview_icon.png" alt="PCAview Logo" class="h-12 w-12 object-contain" />
                        </div>
                    </Link>
                    <h1 class="text-center text-2xl font-bold text-white">{{ title }}</h1>
                    <p v-if="description" class="mt-2 text-center text-sm text-white/90">{{ description }}</p>
                </div>

                <!-- Form Section -->
                <div class="px-8 py-8">
                    <slot />
                </div>
            </div>

            <!-- Footer Text -->
            <p class="mt-6 text-center text-sm text-gray-600">
                빠르게 돌아가는 세상을 바라보는 뷰 <span class="font-semibold text-blue-600">피카뷰</span>
            </p>
        </div>
    </div>
</template>
