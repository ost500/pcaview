<script setup lang="ts">
import { X, Share, Plus, Home, Star, Command } from 'lucide-vue-next';

const props = defineProps<{
    showIOSInstructions: boolean;
    isIOS: boolean;
    isAndroid?: boolean;
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
        enter-active-class="transition duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="props.showIOSInstructions" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" @click.self="closeIOSInstructions">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div v-if="props.showIOSInstructions" class="relative max-h-[90vh] w-full max-w-md overflow-y-auto rounded-3xl bg-white shadow-2xl">
                    <!-- 헤더 -->
                    <div class="bg-gradient-to-br from-blue-600 to-purple-600 px-6 pb-6 pt-8 text-center">
                        <!-- 앱 아이콘 -->
                        <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-2xl bg-white p-3 shadow-xl">
                            <img src="/jubogo_favicon.png" alt="주보고" class="h-full w-full rounded-xl object-contain" />
                        </div>
                        <h2 class="mb-2 text-2xl font-bold text-white">설치 방법 안내</h2>
                        <p class="text-sm text-white/70">
                            <template v-if="props.isAndroid">Android에서 설치하기</template>
                            <template v-else-if="props.isIOS">iOS Safari에서 설치하기</template>
                            <template v-else>데스크톱에서 즐겨찾기 추가</template>
                        </p>

                        <!-- 닫기 버튼 -->
                        <button @click="closeIOSInstructions" class="absolute right-4 top-4 rounded-full p-2 text-white/80 transition-colors hover:bg-white/20" aria-label="닫기">
                            <X :size="20" />
                        </button>
                    </div>

                    <!-- 바디 -->
                    <div class="p-6">
                        <!-- Android 안내 -->
                        <template v-if="props.isAndroid">
                            <p class="mb-4 text-sm text-gray-600">
                                Play Store에서 주보고 앱을 다운로드하여 더 나은 경험을 즐기세요.
                            </p>

                            <!-- Android 단계별 안내 카드 -->
                            <div class="mb-4 rounded-2xl border-2 border-gray-100 bg-gray-50 p-4">
                                <!-- 1단계 -->
                                <div class="mb-4 flex items-start">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-purple-600 text-sm font-bold text-white">
                                        1
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <h3 class="mb-1 text-sm font-semibold text-gray-900">Play Store 방문</h3>
                                        <p class="text-xs text-gray-500">아래 버튼을 눌러 Play Store로 이동하세요</p>
                                    </div>
                                </div>

                                <hr class="my-3 border-gray-200" />

                                <!-- 2단계 -->
                                <div class="flex items-start">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-red-500 text-sm font-bold text-white">
                                        2
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <h3 class="mb-1 text-sm font-semibold text-gray-900">앱 설치</h3>
                                        <p class="text-xs text-gray-500">"설치" 버튼을 눌러 주보고 앱을 다운로드하세요</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Play Store 버튼 -->
                            <a
                                href="https://play.google.com/store/apps/details?id=com.jubogo.porting&pcampaignid=web_share"
                                target="_blank"
                                class="mb-4 flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3.5 font-semibold text-white no-underline transition-transform hover:scale-[1.02] active:scale-[0.98]"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M3,20.5V3.5C3,2.91 3.34,2.39 3.84,2.15L13.69,12L3.84,21.85C3.34,21.6 3,21.09 3,20.5M16.81,15.12L6.05,21.34L14.54,12.85L16.81,15.12M20.16,10.81C20.5,11.08 20.75,11.5 20.75,12C20.75,12.5 20.53,12.9 20.18,13.18L17.89,14.5L15.39,12L17.89,9.5L20.16,10.81M6.05,2.66L16.81,8.88L14.54,11.15L6.05,2.66Z" />
                                </svg>
                                Play Store에서 설치하기
                            </a>

                            <!-- Android 결과 안내 -->
                            <div class="rounded-2xl bg-gradient-to-r from-green-400 to-emerald-500 p-4">
                                <p class="text-sm text-white">
                                    ✨ Play Store에서 설치하면 자동 업데이트와 안정적인 성능을 제공받을 수 있습니다!
                                </p>
                            </div>
                        </template>

                        <!-- iOS 안내 -->
                        <template v-else-if="props.isIOS">
                            <p class="mb-4 text-sm text-gray-600">
                                Safari에서 주보고를 홈 화면에 추가하면 앱처럼 빠르게 이용할 수 있습니다.
                            </p>

                            <!-- iOS 단계별 안내 카드 -->
                            <div class="mb-4 rounded-2xl border-2 border-gray-100 bg-gray-50 p-4">
                                <!-- 1단계 -->
                                <div class="mb-4 flex items-start">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-blue-600 to-purple-600 text-sm font-bold text-white">
                                        1
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <div class="mb-1 flex items-center">
                                            <Share :size="18" class="mr-2 text-blue-600" />
                                            <h3 class="text-sm font-semibold text-gray-900">공유 버튼 누르기</h3>
                                        </div>
                                        <p class="text-xs text-gray-500">Safari 하단의 공유 버튼(상자에서 화살표)을 누르세요</p>
                                    </div>
                                </div>

                                <hr class="my-3 border-gray-200" />

                                <!-- 2단계 -->
                                <div class="mb-4 flex items-start">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-pink-500 to-red-500 text-sm font-bold text-white">
                                        2
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <div class="mb-1 flex items-center">
                                            <Plus :size="18" class="mr-2 text-red-500" />
                                            <h3 class="text-sm font-semibold text-gray-900">"홈 화면에 추가" 선택</h3>
                                        </div>
                                        <p class="text-xs text-gray-500">아래로 스크롤하여 "홈 화면에 추가"를 찾아 누르세요</p>
                                    </div>
                                </div>

                                <hr class="my-3 border-gray-200" />

                                <!-- 3단계 -->
                                <div class="flex items-start">
                                    <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-cyan-400 to-blue-500 text-sm font-bold text-white">
                                        3
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <div class="mb-1 flex items-center">
                                            <Home :size="18" class="mr-2 text-cyan-500" />
                                            <h3 class="text-sm font-semibold text-gray-900">추가 버튼 누르기</h3>
                                        </div>
                                        <p class="text-xs text-gray-500">오른쪽 상단의 "추가" 버튼을 눌러 완료하세요</p>
                                    </div>
                                </div>
                            </div>

                            <!-- iOS 결과 안내 -->
                            <div class="rounded-2xl bg-gradient-to-r from-green-400 to-emerald-500 p-4">
                                <p class="text-sm text-white">
                                    ✨ 홈 화면에 주보고 아이콘이 생기고, 앱처럼 빠르게 실행할 수 있습니다!
                                </p>
                            </div>
                        </template>

                        <!-- 데스크톱 안내 -->
                        <template v-else>
                            <p class="mb-4 text-sm text-gray-600">
                                주보고를 즐겨찾기에 추가하면 더욱 빠르게 접속할 수 있습니다.
                            </p>

                            <!-- 브라우저별 안내 카드 -->
                            <div class="mb-4 rounded-2xl border-2 border-gray-100 bg-gray-50 p-4">
                                <!-- Chrome/Edge -->
                                <div class="mb-4 flex items-center">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-purple-600">
                                        <Star :size="20" class="text-white" />
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <h3 class="text-sm font-semibold text-gray-900">Chrome / Edge</h3>
                                        <p class="text-xs text-gray-500">주소창 오른쪽 ⭐ 별 아이콘 클릭 → "북마크 추가"</p>
                                    </div>
                                </div>

                                <hr class="my-3 border-gray-200" />

                                <!-- Safari -->
                                <div class="mb-4 flex items-center">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-pink-500 to-red-500">
                                        <Share :size="20" class="text-white" />
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <h3 class="text-sm font-semibold text-gray-900">Safari</h3>
                                        <p class="text-xs text-gray-500">공유 버튼 클릭 → "즐겨찾기에 추가"</p>
                                    </div>
                                </div>

                                <hr class="my-3 border-gray-200" />

                                <!-- 단축키 -->
                                <div class="flex items-center">
                                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-cyan-400 to-blue-500">
                                        <Command :size="20" class="text-white" />
                                    </div>
                                    <div class="ml-3 flex-grow">
                                        <h3 class="text-sm font-semibold text-gray-900">단축키</h3>
                                        <p class="text-xs text-gray-500"><strong>Ctrl + D</strong> (Windows) / <strong>⌘ + D</strong> (Mac)</p>
                                    </div>
                                </div>
                            </div>

                            <!-- 데스크톱 결과 안내 -->
                            <div class="rounded-2xl bg-gradient-to-r from-emerald-400 to-cyan-500 p-4">
                                <p class="text-sm text-white">
                                    ✨ 즐겨찾기에서 주보고를 빠르게 찾을 수 있습니다!
                                </p>
                            </div>
                        </template>
                    </div>

                    <!-- 푸터 -->
                    <div class="flex flex-col gap-2 px-6 pb-6">
                        <button @click="closeIOSInstructions" class="w-full rounded-xl bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-3.5 font-semibold text-white transition-transform hover:scale-[1.02] active:scale-[0.98]">
                            확인했습니다
                        </button>
                        <button @click="dismissPermanently" class="w-full rounded-xl bg-gray-100 px-4 py-3 text-sm font-medium text-gray-500 transition-colors hover:bg-gray-200">
                            다시 보지 않기
                        </button>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>

<style scoped>
/* TailwindCSS handles all styling */
</style>
