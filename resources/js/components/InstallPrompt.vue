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
        <div v-if="props.showInstallPrompt" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5)" @click.self="handleDismiss">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div v-if="props.showInstallPrompt" class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content" style="border-radius: 1.5rem; border: none; overflow: hidden;">
                        <!-- 헤더 -->
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 2rem 1.5rem 1.5rem;">
                            <div class="w-100 text-center">
                                <!-- 앱 아이콘 -->
                                <div class="mx-auto mb-3" style="width: 80px; height: 80px; background: white; border-radius: 20px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                                    <img src="/jubogo_favicon.png" alt="주보고" style="width: 100%; height: 100%; border-radius: 12px;" />
                                </div>
                                <h5 class="modal-title text-white fw-bold mb-1" style="font-size: 1.5rem;">주보고</h5>
                                <p class="text-white-50 mb-0" style="font-size: 0.875rem;">앱으로 더 편리하게 이용하세요</p>
                            </div>
                            <button @click="handleDismiss" type="button" class="btn-close btn-close-white position-absolute" style="top: 1rem; right: 1rem; opacity: 0.8;" aria-label="닫기"></button>
                        </div>

                        <!-- 바디 -->
                        <div class="modal-body" style="padding: 1.5rem;">
                            <!-- 기능 카드 -->
                            <div class="card mb-3" style="border: 2px solid #f0f0f0; border-radius: 1rem; background: #fafafa;">
                                <div class="card-body p-3">
                                    <!-- 기능 1 -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <Download :size="20" color="white" />
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">빠른 접근</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.75rem;">홈 화면에서 바로 실행</p>
                                        </div>
                                    </div>

                                    <hr class="my-2" style="border-color: #e0e0e0;" />

                                    <!-- 기능 2 -->
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
                                                <path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" />
                                            </svg>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">전체 화면</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.75rem;">앱처럼 독립적으로 실행</p>
                                        </div>
                                    </div>

                                    <hr class="my-2" style="border-color: #e0e0e0;" />

                                    <!-- 기능 3 -->
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 24 24">
                                                <path stroke="white" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
                                            </svg>
                                        </div>
                                        <div class="flex-grow-1 ms-3">
                                            <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">빠른 로딩</h6>
                                            <p class="mb-0 text-muted" style="font-size: 0.75rem;">캐싱으로 더 빠르게</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 푸터 -->
                        <div class="modal-footer" style="border: none; padding: 0 1.5rem 1.5rem; gap: 0.5rem;">
                            <button @click="handleInstall" type="button" class="btn btn-primary w-100 fw-semibold" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 12px; padding: 0.875rem; font-size: 1rem;">
                                <Download :size="18" class="me-2" style="display: inline-block; vertical-align: middle;" />
                                <template v-if="props.isAndroid">Play Store에서 설치</template>
                                <template v-else>지금 설치하기</template>
                            </button>
                            <div class="d-flex gap-2 w-100">
                                <button @click="handleDismiss" type="button" class="btn btn-light flex-fill" style="border-radius: 10px; padding: 0.75rem; font-size: 0.875rem;">
                                    나중에
                                </button>
                                <button @click="handleDismissPermanently" type="button" class="btn btn-light flex-fill text-muted" style="border-radius: 10px; padding: 0.75rem; font-size: 0.875rem;">
                                    <EyeOff :size="14" class="me-1" style="display: inline-block; vertical-align: middle;" />
                                    보지않기
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
