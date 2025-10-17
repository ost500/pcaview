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
        enter-active-class="transition duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="props.showIOSInstructions" class="modal fade show" style="display: block; background: rgba(0,0,0,0.5)" @click.self="closeIOSInstructions">
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div v-if="props.showIOSInstructions" class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content" style="border-radius: 1.5rem; border: none; overflow: hidden; max-height: 90vh; overflow-y: auto;">
                        <!-- 헤더 -->
                        <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; padding: 2rem 1.5rem 1.5rem;">
                            <div class="w-100 text-center">
                                <!-- 앱 아이콘 -->
                                <div class="mx-auto mb-3" style="width: 80px; height: 80px; background: white; border-radius: 20px; padding: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                                    <img src="/jubogo_favicon.png" alt="주보고" style="width: 100%; height: 100%; border-radius: 12px;" />
                                </div>
                                <h5 class="modal-title text-white fw-bold mb-1" style="font-size: 1.5rem;">설치 방법 안내</h5>
                                <p class="text-white-50 mb-0" style="font-size: 0.875rem;">
                                    <template v-if="props.isIOS">iOS Safari에서 설치하기</template>
                                    <template v-else>데스크톱에서 즐겨찾기 추가</template>
                                </p>
                            </div>
                            <button @click="closeIOSInstructions" type="button" class="btn-close btn-close-white position-absolute" style="top: 1rem; right: 1rem; opacity: 0.8;" aria-label="닫기"></button>
                        </div>

                        <!-- 바디 -->
                        <div class="modal-body" style="padding: 1.5rem;">
                            <!-- iOS 안내 -->
                            <template v-if="props.isIOS">
                                <p class="text-muted mb-4" style="font-size: 0.875rem;">
                                    Safari에서 주보고를 홈 화면에 추가하면 앱처럼 빠르게 이용할 수 있습니다.
                                </p>

                                <!-- iOS 단계별 안내 카드 -->
                                <div class="card mb-3" style="border: 2px solid #f0f0f0; border-radius: 1rem; background: #fafafa;">
                                    <div class="card-body p-3">
                                        <!-- 1단계 -->
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem;">
                                                1
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="d-flex align-items-center mb-1">
                                                    <Share :size="18" style="color: #667eea;" class="me-2" />
                                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">공유 버튼 누르기</h6>
                                                </div>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">Safari 하단의 공유 버튼(상자에서 화살표)을 누르세요</p>
                                            </div>
                                        </div>

                                        <hr class="my-2" style="border-color: #e0e0e0;" />

                                        <!-- 2단계 -->
                                        <div class="d-flex align-items-start mb-3">
                                            <div class="flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem;">
                                                2
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="d-flex align-items-center mb-1">
                                                    <Plus :size="18" style="color: #f5576c;" class="me-2" />
                                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">"홈 화면에 추가" 선택</h6>
                                                </div>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">아래로 스크롤하여 "홈 화면에 추가"를 찾아 누르세요</p>
                                            </div>
                                        </div>

                                        <hr class="my-2" style="border-color: #e0e0e0;" />

                                        <!-- 3단계 -->
                                        <div class="d-flex align-items-start">
                                            <div class="flex-shrink-0" style="width: 36px; height: 36px; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 0.875rem;">
                                                3
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="d-flex align-items-center mb-1">
                                                    <Home :size="18" style="color: #4facfe;" class="me-2" />
                                                    <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">추가 버튼 누르기</h6>
                                                </div>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">오른쪽 상단의 "추가" 버튼을 눌러 완료하세요</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- iOS 결과 안내 -->
                                <div class="alert alert-success mb-0" style="background: linear-gradient(135deg, #d4fc79 0%, #96e6a1 100%); border: none; border-radius: 1rem;">
                                    <p class="mb-0" style="font-size: 0.875rem;">
                                        ✨ 홈 화면에 주보고 아이콘이 생기고, 앱처럼 빠르게 실행할 수 있습니다!
                                    </p>
                                </div>
                            </template>

                            <!-- 데스크톱 안내 -->
                            <template v-else>
                                <p class="text-muted mb-4" style="font-size: 0.875rem;">
                                    주보고를 즐겨찾기에 추가하면 더욱 빠르게 접속할 수 있습니다.
                                </p>

                                <!-- 브라우저별 안내 카드 -->
                                <div class="card mb-3" style="border: 2px solid #f0f0f0; border-radius: 1rem; background: #fafafa;">
                                    <div class="card-body p-3">
                                        <!-- Chrome/Edge -->
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #667eea, #764ba2); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <Star :size="20" color="white" />
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">Chrome / Edge</h6>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">주소창 오른쪽 ⭐ 별 아이콘 클릭 → "북마크 추가"</p>
                                            </div>
                                        </div>

                                        <hr class="my-2" style="border-color: #e0e0e0;" />

                                        <!-- Safari -->
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #f093fb, #f5576c); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <Share :size="20" color="white" />
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">Safari</h6>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;">공유 버튼 클릭 → "즐겨찾기에 추가"</p>
                                            </div>
                                        </div>

                                        <hr class="my-2" style="border-color: #e0e0e0;" />

                                        <!-- 단축키 -->
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0" style="width: 40px; height: 40px; background: linear-gradient(135deg, #4facfe, #00f2fe); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                                                <Command :size="20" color="white" />
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0 fw-semibold" style="font-size: 0.875rem;">단축키</h6>
                                                <p class="mb-0 text-muted" style="font-size: 0.75rem;"><strong>Ctrl + D</strong> (Windows) / <strong>⌘ + D</strong> (Mac)</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 데스크톱 결과 안내 -->
                                <div class="alert alert-info mb-0" style="background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); border: none; border-radius: 1rem;">
                                    <p class="mb-0" style="font-size: 0.875rem;">
                                        ✨ 즐겨찾기에서 주보고를 빠르게 찾을 수 있습니다!
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- 푸터 -->
                        <div class="modal-footer" style="border: none; padding: 0 1.5rem 1.5rem; gap: 0.5rem;">
                            <button @click="closeIOSInstructions" type="button" class="btn btn-primary w-100 fw-semibold" style="background: linear-gradient(135deg, #667eea, #764ba2); border: none; border-radius: 12px; padding: 0.875rem; font-size: 1rem;">
                                확인했습니다
                            </button>
                            <button @click="dismissPermanently" type="button" class="btn btn-light w-100 text-muted" style="border-radius: 10px; padding: 0.75rem; font-size: 0.875rem;">
                                다시 보지 않기
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
