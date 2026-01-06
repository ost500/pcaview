<script setup lang="ts">
import BusinessInfo from '@/components/BusinessInfo.vue';
import Header from '@/components/template/Header.vue';
import { safeRoute } from '@/composables/useSafeRoute';
import { router, useForm, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Props {
    canResetPassword?: boolean;
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user);

// URL 파라미터로 헤더 숨김 여부 확인
const hideHeader = ref(false);
if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    hideHeader.value = urlParams.get('hideHeader') === 'true';
}

// 로그인 폼
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const handleLogin = () => {
    loginForm.post(safeRoute('login'), {
        preserveScroll: true,
        onSuccess: () => {
            loginForm.reset('password');
        },
    });
};

const handleLogout = () => {
    if (confirm('로그아웃 하시겠습니까?')) {
        router.post(
            safeRoute('logout'),
            {},
            {
                onSuccess: () => {
                    window.location.href = safeRoute('home');
                },
            },
        );
    }
};

const goToSettings = () => {
    window.location.href = safeRoute('profile.edit');
};

// 프로필 사진 업로드
const fileInput = ref<HTMLInputElement | null>(null);
const uploadingPhoto = ref(false);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileChange = async (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) return;

    // 파일 크기 체크 (5MB)
    if (file.size > 5 * 1024 * 1024) {
        alert('파일 크기는 5MB 이하여야 합니다.');
        return;
    }

    // 이미지 파일 체크
    if (!file.type.startsWith('image/')) {
        alert('이미지 파일만 업로드 가능합니다.');
        return;
    }

    uploadingPhoto.value = true;

    router.post(
        '/profile/photo',
        {
            profile_photo: file,
        },
        {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                uploadingPhoto.value = false;
                if (target) target.value = '';
            },
            onError: (errors) => {
                uploadingPhoto.value = false;
                if (target) target.value = '';
                alert(errors.profile_photo || '프로필 사진 업로드에 실패했습니다.');
            },
        },
    );
};

// 이름 수정
const isEditingName = ref(false);
const editedName = ref('');
const updatingName = ref(false);

const startEditingName = () => {
    editedName.value = user.value.name;
    isEditingName.value = true;
};

const cancelEditingName = () => {
    isEditingName.value = false;
    editedName.value = '';
};

const updateName = () => {
    if (!editedName.value.trim()) {
        alert('이름을 입력해주세요.');
        return;
    }

    updatingName.value = true;

    router.patch(
        safeRoute('profile.update'),
        {
            name: editedName.value,
            email: user.value.email,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                updatingName.value = false;
                isEditingName.value = false;
            },
            onError: (errors) => {
                updatingName.value = false;
                alert(errors.name || '이름 수정에 실패했습니다.');
            },
        },
    );
};
</script>

<template>
    <Header v-if="!hideHeader" title="프로필"></Header>

    <div class="bg-white pt-3 pb-14 sm:pt-4 sm:pb-16" :class="{ 'pt-0': hideHeader }">
        <div class="mx-auto max-w-screen-xl px-4">
            <!-- 로그인 안 된 경우 - 인라인 로그인 폼 -->
            <div v-if="!user" class="mx-auto max-w-md">
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8 text-center">
                        <div class="mb-3 flex justify-center">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                <svg class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>
                        <h2 class="text-2xl font-bold text-white">로그인</h2>
                        <p class="mt-2 text-sm text-white/90">프로필을 보려면 로그인해주세요</p>
                    </div>

                    <form @submit.prevent="handleLogin" class="space-y-4 px-6 py-6">
                        <!-- 이메일 -->
                        <div class="space-y-2">
                            <label for="email" class="text-sm font-medium text-gray-700">이메일</label>
                            <input
                                id="email"
                                type="email"
                                class="h-12 w-full rounded-lg border border-gray-300 px-4 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                v-model="loginForm.email"
                                placeholder="email@example.com"
                                required
                                autofocus
                                autocomplete="email"
                            />
                            <p v-if="loginForm.errors.email" class="text-sm text-red-600">
                                {{ loginForm.errors.email }}
                            </p>
                        </div>

                        <!-- 비밀번호 -->
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label for="password" class="text-sm font-medium text-gray-700">비밀번호</label>
                                <a
                                    v-if="canResetPassword"
                                    :href="safeRoute('password.request')"
                                    class="text-xs text-blue-600 hover:text-blue-700 hover:underline"
                                >
                                    비밀번호 찾기
                                </a>
                            </div>
                            <input
                                id="password"
                                type="password"
                                class="h-12 w-full rounded-lg border border-gray-300 px-4 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                v-model="loginForm.password"
                                placeholder="비밀번호를 입력하세요"
                                required
                                autocomplete="current-password"
                            />
                            <p v-if="loginForm.errors.password" class="text-sm text-red-600">
                                {{ loginForm.errors.password }}
                            </p>
                        </div>

                        <!-- 자동 로그인 -->
                        <div class="flex items-center">
                            <input
                                type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-2 focus:ring-blue-200"
                                id="remember"
                                v-model="loginForm.remember"
                            />
                            <label class="ml-2 cursor-pointer text-sm text-gray-600" for="remember"> 로그인 상태 유지 </label>
                        </div>

                        <!-- 로그인 버튼 -->
                        <button
                            type="submit"
                            class="h-12 w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-base font-semibold text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-lg disabled:opacity-50"
                            :disabled="loginForm.processing"
                        >
                            <span
                                v-if="loginForm.processing"
                                class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"
                            ></span>
                            {{ loginForm.processing ? '로그인 중...' : '로그인' }}
                        </button>

                        <!-- 회원가입 버튼 -->
                        <a
                            :href="safeRoute('register')"
                            class="block h-12 w-full rounded-lg border-2 border-gray-300 text-center leading-[2.75rem] font-medium text-gray-700 transition-all hover:border-blue-500 hover:text-blue-600"
                        >
                            회원가입
                        </a>

                        <!-- 카카오 로그인 버튼 -->
                        <button onclick="KakaoLogin.kakaoLogin()" class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#FEE500] text-base font-semibold text-[#000000] transition-all hover:bg-[#FDD835] hover:shadow-lg">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 3C6.5 3 2 6.6 2 11c0 2.8 1.9 5.3 4.7 6.7-.2.8-.7 2.8-.8 3.2-.1.5.2.5.4.4.3-.1 3.7-2.4 4.3-2.8.5.1 1 .1 1.5.1 5.5 0 10-3.6 10-8S17.5 3 12 3z"/>
                            </svg>
                            카카오 로그인
                        </button>
                    </form>
                </div>
            </div>

            <!-- 로그인된 경우 -->
            <div v-else class="space-y-4">
                <!-- 프로필 카드 -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 to-purple-50 shadow-lg">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8">
                        <div class="flex justify-center">
                            <div
                                @click="triggerFileInput"
                                class="group relative h-24 w-24 cursor-pointer overflow-hidden rounded-full bg-white/20 backdrop-blur-sm transition-all hover:bg-white/30"
                            >
                                <!-- 업로드 중 오버레이 -->
                                <div
                                    v-if="uploadingPhoto"
                                    class="absolute inset-0 z-10 flex items-center justify-center bg-black/50"
                                >
                                    <div class="h-8 w-8 animate-spin rounded-full border-4 border-white border-t-transparent"></div>
                                </div>

                                <!-- 호버 오버레이 -->
                                <div
                                    class="absolute inset-0 flex items-center justify-center bg-black/40 opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <svg class="h-8 w-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                                        />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>

                                <!-- 프로필 사진 -->
                                <img
                                    :src="user.profile_photo"
                                    :alt="user.name"
                                    class="h-full w-full object-cover"
                                />
                            </div>

                            <!-- 숨겨진 파일 입력 -->
                            <input
                                ref="fileInput"
                                type="file"
                                accept="image/*"
                                class="hidden"
                                @change="handleFileChange"
                            />
                        </div>
                    </div>
                    <div class="bg-white px-6 py-6 text-center">
                        <!-- 이름 표시 및 수정 -->
                        <div v-if="!isEditingName" class="mb-1 flex items-center justify-center gap-2">
                            <h2 class="text-xl font-bold text-gray-900">{{ user.name }}</h2>
                            <button
                                @click="startEditingName"
                                class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                title="이름 수정"
                            >
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"
                                    />
                                </svg>
                            </button>
                        </div>

                        <!-- 이름 수정 폼 -->
                        <div v-else class="mb-1 space-y-2">
                            <input
                                v-model="editedName"
                                type="text"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-center text-xl font-bold transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                                placeholder="이름을 입력하세요"
                                @keyup.enter="updateName"
                                @keyup.escape="cancelEditingName"
                            />
                            <div class="flex justify-center gap-2">
                                <button
                                    @click="updateName"
                                    :disabled="updatingName"
                                    class="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                                >
                                    {{ updatingName ? '저장 중...' : '저장' }}
                                </button>
                                <button
                                    @click="cancelEditingName"
                                    :disabled="updatingName"
                                    class="rounded-lg border border-gray-300 px-4 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                                >
                                    취소
                                </button>
                            </div>
                        </div>

                        <p class="mb-3 text-sm text-gray-600">{{ user.email }}</p>
                        <span
                            v-if="user.email_verified_at"
                            class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700"
                        >
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            이메일 인증됨
                        </span>
                        <span v-else class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            이메일 미인증
                        </span>
                    </div>
                </div>

                <!-- 메뉴 리스트 -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
<!--                    <div-->
<!--                        @click="goToSettings"-->
<!--                        class="flex cursor-pointer items-center justify-between border-b border-gray-100 px-6 py-4 transition-colors hover:bg-gray-50 active:bg-gray-100"-->
<!--                    >-->
<!--                        <div class="flex items-center gap-3">-->
<!--                            <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                                <path-->
<!--                                    stroke-linecap="round"-->
<!--                                    stroke-linejoin="round"-->
<!--                                    stroke-width="2"-->
<!--                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"-->
<!--                                />-->
<!--                            </svg>-->
<!--                            <span class="font-medium text-gray-900">프로필 수정</span>-->
<!--                        </div>-->
<!--                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">-->
<!--                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />-->
<!--                        </svg>-->
<!--                    </div>-->
                    <div
                        @click="handleLogout"
                        class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors hover:bg-red-50 active:bg-red-100"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                />
                            </svg>
                            <span class="font-medium text-red-600">로그아웃</span>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>

                <!-- 앱 정보 -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
                    <div class="px-6 py-4">
                        <h3 class="mb-3 text-lg font-semibold text-gray-900">앱 정보</h3>
                        <div class="space-y-2 text-sm text-gray-600">
                            <p>버전: 1.0.0</p>
                            <p>© 2025 PCAview</p>
                            <p class="text-xs text-gray-500">빠르게 돌아가는 세상을 바라보는 뷰 피카뷰</p>
                        </div>
                    </div>
                </div>

                <BusinessInfo class="mt-4" />
            </div>
        </div>
    </div>
</template>

<style scoped>
/* All styles now handled by Tailwind */
</style>
