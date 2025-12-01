<script setup lang="ts">
import { X, Download, EyeOff } from 'lucide-vue-next';

const props = defineProps<{
    showInstallPrompt: boolean;
    isAndroid?: boolean;
}>();

const emit = defineEmits<{
    install: [];
    dismiss: [];
    dismissPermanently: [];
}>();

const handleInstall = () => {
    emit('install');
};

const handleDismiss = () => {
    emit('dismiss');
};

const handleDismissPermanently = () => {
    emit('dismissPermanently');
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="props.showInstallPrompt" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="handleDismiss">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div v-if="props.showInstallPrompt" class="relative w-full max-w-md overflow-hidden rounded-3xl bg-white shadow-2xl">
                    <!-- 헤더 -->
                    <div class="bg-gradient-to-br from-blue-600 to-purple-600 px-6 pb-6 pt-8 text-center">
                        <!-- 앱 아이콘 -->
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white p-3 shadow-xl">
                            <img src="/jubogo_favicon.png" alt="주보고" class="h-full w-full rounded-xl object-contain" />
                        </div>
                        <h2 class="mb-2 text-2xl font-bold text-white">주보고</h2>
                        <p class="text-sm text-white/70">앱으로 더 편리하게 이용하세요</p>

                        <!-- 닫기 버튼 -->
                        <button @click="handleDismiss" class="absolute right-4 top-4 rounded-full p-2 text-white/80 transition-colors hover:bg-white/20" aria-label="닫기">
                            <X :size="20" />
                        </button>
                    </div>

                    <!-- 바디 -->
                    <div class="p-6">
                        <!-- 기능 카드 -->
                        <div class="mb-4 rounded-2xl border-2 border-gray-100 bg-gray-50 p-4">
                            <!-- 기능 1 -->
                            <div class="mb-4 flex items-center">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-purple-600">
                                    <Download :size="20" class="text-white" />
                                </div>
                                <div class="ml-3 flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-900">빠른 접근</h3>
                                    <p class="text-xs text-gray-500">홈 화면에서 바로 실행</p>
                                </div>
                            </div>

                            <hr class="my-3 border-gray-200" />

                            <!-- 기능 2 -->
                            <div class="mb-4 flex items-center">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-red-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
                                        <path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-900">전체 화면</h3>
                                    <p class="text-xs text-gray-500">앱처럼 독립적으로 실행</p>
                                </div>
                            </div>

                            <hr class="my-3 border-gray-200" />

                            <!-- 기능 3 -->
                            <div class="flex items-center">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
                                        <path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                    </svg>
                                </div>
                                <div class="ml-3 flex-grow">
                                    <h3 class="text-sm font-semibold text-gray-900">빠른 로딩</h3>
                                    <p class="text-xs text-gray-500">캐싱으로 더 빠르게</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 푸터 -->
                    <div class="flex flex-col gap-2 px-6 pb-6">
                        <button @click="handleInstall" class="flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3.5 font-semibold text-white transition-transform hover:scale-[1.02] active:scale-[0.98]">
                            <Download :size="18" />
                            <template v-if="props.isAndroid">Play Store에서 설치</template>
                            <template v-else>지금 설치하기</template>
                        </button>
                        <div class="flex gap-2">
                            <button @click="handleDismiss" class="flex-1 rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-200">
                                나중에
                            </button>
                            <button @click="handleDismissPermanently" class="flex flex-1 items-center justify-center gap-1 rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-500 transition-colors hover:bg-gray-200">
                                <EyeOff :size="14" />
                                보지않기
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
/* TailwindCSS handles all styling */
</style>
