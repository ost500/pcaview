<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { safeRoute } from '@/composables/useSafeRoute';
import { computed, ref } from 'vue';
import type { Department } from '@/types/department';
import BusinessInfo from '@/components/BusinessInfo.vue';

interface Props {
    allDepartments?: Department[];
    subscribedDepartmentIds?: number[];
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

// 구독 상태 관리
const subscribed = ref<Set<number>>(new Set(props.subscribedDepartmentIds || []));

const handleLogout = () => {
    if (confirm('로그아웃 하시겠습니까?')) {
        router.post(safeRoute('logout'), {}, {
            onSuccess: () => {
                window.location.href = safeRoute('home');
            }
        });
    }
};

const goToSettings = () => {
    window.location.href = safeRoute('profile.edit');
};

// 부서 구독 토글
const toggleSubscription = (departmentId: number) => {
    router.post(safeRoute('profile.subscribe'), {
        department_id: departmentId
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // 구독 상태 토글
            if (subscribed.value.has(departmentId)) {
                subscribed.value.delete(departmentId);
            } else {
                subscribed.value.add(departmentId);
            }
        }
    });
};
</script>

<template>
    <Header v-if="!hideHeader" title="프로필"></Header>

    <div class="bg-white pb-14 pt-3 sm:pb-16 sm:pt-4" :class="{ 'pt-0': hideHeader }">
        <div class="mx-auto max-w-screen-xl px-4">
            <!-- 로그인 안 된 경우 - 인라인 로그인 폼 -->
            <div v-if="!user" class="mx-auto max-w-md">
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8 text-center">
                        <div class="mb-3 flex justify-center">
                            <div class="flex h-20 w-20 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                <svg class="h-12 w-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
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
                                class="h-12 w-full rounded-lg border border-gray-300 px-4 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
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
                                <a v-if="canResetPassword" :href="safeRoute('password.request')" class="text-xs text-blue-600 hover:text-blue-700 hover:underline">
                                    비밀번호 찾기
                                </a>
                            </div>
                            <input
                                id="password"
                                type="password"
                                class="h-12 w-full rounded-lg border border-gray-300 px-4 transition-all focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
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
                            <label class="ml-2 cursor-pointer text-sm text-gray-600" for="remember">
                                로그인 상태 유지
                            </label>
                        </div>

                        <!-- 로그인 버튼 -->
                        <button
                            type="submit"
                            class="h-12 w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-base font-semibold text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-lg disabled:opacity-50"
                            :disabled="loginForm.processing"
                        >
                            <span v-if="loginForm.processing" class="mr-2 inline-block h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent"></span>
                            {{ loginForm.processing ? '로그인 중...' : '로그인' }}
                        </button>

                        <!-- 회원가입 버튼 -->
                        <a
                            :href="safeRoute('register')"
                            class="block h-12 w-full rounded-lg border-2 border-gray-300 text-center leading-[2.75rem] font-medium text-gray-700 transition-all hover:border-blue-500 hover:text-blue-600"
                        >
                            회원가입
                        </a>
                    </form>
                </div>
            </div>

            <!-- 로그인된 경우 -->
            <div v-else class="space-y-4">
                <!-- 프로필 카드 -->
                <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-blue-50 to-purple-50 shadow-lg">
                    <div class="bg-gradient-to-r from-blue-600 to-purple-600 px-6 py-8">
                        <div class="flex justify-center">
                            <div class="flex h-24 w-24 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                                <svg class="h-14 w-14 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white px-6 py-6 text-center">
                        <h2 class="mb-1 text-xl font-bold text-gray-900">{{ user.name }}</h2>
                        <p class="mb-3 text-sm text-gray-600">{{ user.email }}</p>
                        <span v-if="user.email_verified_at" class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            이메일 인증됨
                        </span>
                        <span v-else class="inline-flex items-center gap-1 rounded-full bg-yellow-100 px-3 py-1 text-xs font-medium text-yellow-700">
                            <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            이메일 미인증
                        </span>
                    </div>
                </div>

                <!-- 구독 부서 관리 -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h3 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                            <svg class="h-5 w-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                            </svg>
                            구독 부서 관리
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">관심있는 부서를 체크하세요. 해당 부서의 소식을 받아볼 수 있습니다.</p>
                    </div>

                    <div v-if="allDepartments && allDepartments.length > 0" class="divide-y divide-gray-100">
                        <div
                            v-for="department in allDepartments"
                            :key="department.id"
                            class="flex cursor-pointer items-center gap-3 px-6 py-4 transition-colors hover:bg-gray-50 active:bg-gray-100"
                            @click="toggleSubscription(department.id)"
                        >
                            <div class="h-12 w-12 flex-shrink-0 overflow-hidden rounded-full">
                                <img :src="department.icon_image || '/pcaview_icon.png'" :alt="department.name" class="h-full w-full object-cover" />
                            </div>
                            <div class="flex-1">
                                <h4 class="font-medium text-gray-900">{{ department.name }}</h4>
                            </div>
                            <input
                                type="checkbox"
                                :checked="subscribed.has(department.id)"
                                @click.stop="toggleSubscription(department.id)"
                                class="h-5 w-5 cursor-pointer rounded border-gray-300 text-blue-600 transition-all focus:ring-2 focus:ring-blue-200"
                            />
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center text-sm text-gray-500">
                        등록된 부서가 없습니다.
                    </div>
                </div>

                <!-- 메뉴 리스트 -->
                <div class="overflow-hidden rounded-2xl bg-white shadow-lg">
                    <div
                        @click="goToSettings"
                        class="flex cursor-pointer items-center justify-between border-b border-gray-100 px-6 py-4 transition-colors hover:bg-gray-50 active:bg-gray-100"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                            <span class="font-medium text-gray-900">프로필 수정</span>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <div
                        @click="handleLogout"
                        class="flex cursor-pointer items-center justify-between px-6 py-4 transition-colors hover:bg-red-50 active:bg-red-100"
                    >
                        <div class="flex items-center gap-3">
                            <svg class="h-5 w-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                            <span class="font-medium text-red-600">로그아웃</span>
                        </div>
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
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
