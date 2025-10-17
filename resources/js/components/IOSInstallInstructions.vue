<script setup lang="ts">
import { X, Share, Plus, Home, Star, Command } from 'lucide-vue-next';

const props = defineProps<{
    showIOSInstructions: boolean;
    isIOS: boolean;
}>();

const emit = defineEmits<{
    close: [];
    dismissPermanently: [];
}>();

const closeIOSInstructions = () => {
    emit('close');
};

const dismissPermanently = () => {
    emit('dismissPermanently');
};
</script>

<template>
    <Transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="props.showIOSInstructions"
            class="fixed inset-0 z-50 flex items-end justify-center bg-black/50 sm:items-center"
            @click.self="closeIOSInstructions"
        >
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-full sm:scale-95 opacity-0"
                enter-to-class="translate-y-0 sm:scale-100 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 sm:scale-100 opacity-100"
                leave-to-class="translate-y-full sm:scale-95 opacity-0"
            >
                <div
                    v-if="props.showIOSInstructions"
                    class="mx-4 mb-0 w-full max-w-md rounded-t-2xl bg-white shadow-xl sm:mb-4 sm:rounded-2xl dark:bg-zinc-900"
                >
                    <!-- 헤더 -->
                    <div class="flex items-start justify-between border-b border-zinc-200 p-6 dark:border-zinc-800">
                        <div class="flex items-center gap-3">
                            <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                                <img src="/jubogo_favicon.png" alt="주보고" class="h-10 w-10 rounded-lg" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">주보고 앱 설치</h3>
                                <p class="text-sm text-zinc-600 dark:text-zinc-400">홈 화면에 추가하기</p>
                            </div>
                        </div>
                        <button
                            @click="closeIOSInstructions"
                            class="rounded-md p-1 text-zinc-400 transition-colors hover:text-zinc-600 dark:hover:text-zinc-300"
                            aria-label="닫기"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <!-- 안내 내용 -->
                    <div class="space-y-6 p-6">
                        <!-- iOS 안내 -->
                        <template v-if="props.isIOS">
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                Safari에서 주보고를 홈 화면에 추가하면 앱처럼 빠르게 이용할 수 있습니다.
                            </p>

                            <!-- iOS 단계별 안내 -->
                            <div class="space-y-4">
                            <!-- 1단계 -->
                            <div class="flex gap-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    1
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="mb-2 flex items-center gap-2">
                                        <Share :size="20" class="text-blue-600 dark:text-blue-400" />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white"> 공유 버튼 누르기 </span>
                                    </div>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">Safari 하단의 공유 버튼(상자에서 화살표)을 누르세요</p>
                                </div>
                            </div>

                            <!-- 2단계 -->
                            <div class="flex gap-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    2
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="mb-2 flex items-center gap-2">
                                        <Plus :size="20" class="text-blue-600 dark:text-blue-400" />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white"> "홈 화면에 추가" 선택 </span>
                                    </div>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">아래로 스크롤하여 "홈 화면에 추가"를 찾아 누르세요</p>
                                </div>
                            </div>

                            <!-- 3단계 -->
                            <div class="flex gap-4">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                >
                                    3
                                </div>
                                <div class="flex-1 pt-1">
                                    <div class="mb-2 flex items-center gap-2">
                                        <Home :size="20" class="text-blue-600 dark:text-blue-400" />
                                        <span class="text-sm font-medium text-zinc-900 dark:text-white"> 추가 버튼 누르기 </span>
                                    </div>
                                    <p class="text-xs text-zinc-600 dark:text-zinc-400">오른쪽 상단의 "추가" 버튼을 눌러 완료하세요</p>
                                </div>
                            </div>
                        </div>

                            <!-- iOS 결과 안내 -->
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                <p class="text-sm text-blue-900 dark:text-blue-300">
                                    ✨ 홈 화면에 주보고 아이콘이 생기고, 앱처럼 빠르게 실행할 수 있습니다!
                                </p>
                            </div>
                        </template>

                        <!-- 데스크톱 안내 -->
                        <template v-else>
                            <p class="text-sm text-zinc-700 dark:text-zinc-300">
                                주보고를 즐겨찾기에 추가하면 더욱 빠르게 접속할 수 있습니다.
                            </p>

                            <!-- 브라우저별 안내 -->
                            <div class="space-y-4">
                                <!-- Chrome/Edge -->
                                <div class="flex gap-4">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        <Star :size="16" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <div class="mb-2 flex items-center gap-2">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                                Chrome / Edge
                                            </span>
                                        </div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            주소창 오른쪽 ⭐ 별 아이콘 클릭 → "북마크 추가"
                                        </p>
                                    </div>
                                </div>

                                <!-- Safari -->
                                <div class="flex gap-4">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        <Share :size="16" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <div class="mb-2 flex items-center gap-2">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                                Safari
                                            </span>
                                        </div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            공유 버튼 클릭 → "즐겨찾기에 추가"
                                        </p>
                                    </div>
                                </div>

                                <!-- 단축키 -->
                                <div class="flex gap-4">
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 font-semibold text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                                    >
                                        <Command :size="16" />
                                    </div>
                                    <div class="flex-1 pt-1">
                                        <div class="mb-2 flex items-center gap-2">
                                            <span class="text-sm font-medium text-zinc-900 dark:text-white">
                                                단축키
                                            </span>
                                        </div>
                                        <p class="text-xs text-zinc-600 dark:text-zinc-400">
                                            <strong>Ctrl + D</strong> (Windows) / <strong>⌘ + D</strong> (Mac)
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- 데스크톱 결과 안내 -->
                            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                <p class="text-sm text-blue-900 dark:text-blue-300">
                                    ✨ 즐겨찾기에서 주보고를 빠르게 찾을 수 있습니다!
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- 하단 버튼 -->
                    <div class="flex flex-col gap-2 p-6 pt-0">
                        <button
                            @click="closeIOSInstructions"
                            class="w-full rounded-lg bg-blue-600 px-4 py-3 text-sm font-medium text-white transition-colors hover:bg-blue-700"
                        >
                            확인
                        </button>
                        <button
                            @click="dismissPermanently"
                            class="w-full px-4 py-2 text-sm font-medium text-zinc-500 transition-colors hover:text-zinc-700 dark:text-zinc-500 dark:hover:text-zinc-400"
                        >
                            다시 보지 않기
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
