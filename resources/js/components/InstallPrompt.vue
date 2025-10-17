<script setup lang="ts">
import { X, Download, EyeOff } from 'lucide-vue-next';
import { usePWA } from '@/composables/usePWA';

const { showInstallPrompt, promptInstall, dismissPrompt, dismissPermanently } = usePWA();

console.log("ssssssssssssssshhhhhhhhhhooooooooooooowwwwwwwwww");
console.log(showInstallPrompt);
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="translate-y-full opacity-0"
        enter-to-class="translate-y-0 opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="translate-y-0 opacity-100"
        leave-to-class="translate-y-full opacity-0"
    >
        <div
            v-if="showInstallPrompt"
            class="fixed right-0 bottom-0 left-0 z-50 border-t border-zinc-200 bg-white shadow-lg dark:border-zinc-800 dark:bg-zinc-900"
        >
            <div class="mx-auto max-w-screen-xl px-4 py-4">
                <div class="flex items-start gap-4">
                    <!-- 아이콘 -->
                    <div class="flex-shrink-0 rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                        <img src="/jubogo_favicon.png" alt="주보고" class="h-10 w-10 rounded-lg" />
                    </div>

                    <!-- 콘텐츠 -->
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-zinc-900 dark:text-white">주보고 앱 설치</h3>
                        <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">홈화면에 추가하고 앱처럼 빠르게 이용하세요</p>

                        <!-- 버튼 그룹 -->
                        <div class="mt-3 flex flex-col gap-2">
                            <div class="flex gap-2">
                                <button
                                    @click="promptInstall"
                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-3 py-2 text-sm font-medium whitespace-nowrap text-white transition-colors hover:bg-blue-700"
                                >
                                    <Download :size="16" />
                                    설치하기
                                </button>
                                <button
                                    @click="dismissPrompt"
                                    class="flex-1 px-3 py-2 text-sm font-medium whitespace-nowrap text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white"
                                >
                                    나중에
                                </button>
                            </div>
                            <button
                                @click="dismissPermanently"
                                class="inline-flex items-center justify-center gap-1.5 px-3 py-2 text-sm font-medium whitespace-nowrap text-zinc-500 transition-colors hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-400"
                            >
                                <EyeOff :size="16" />
                                다시 보지 않기
                            </button>
                        </div>
                    </div>

                    <!-- 닫기 버튼 -->
                    <button
                        @click="dismissPrompt"
                        class="flex-shrink-0 rounded-md p-1 text-zinc-400 transition-colors hover:text-zinc-600 dark:hover:text-zinc-300"
                        aria-label="닫기"
                    >
                        <X :size="20" />
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>
