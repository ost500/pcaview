<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import Header from '@/components/template/Header.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head, router } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

// URL 파라미터로 헤더 숨김 여부 확인
const hideHeader = ref(false);
if (typeof window !== 'undefined') {
    const urlParams = new URLSearchParams(window.location.search);
    hideHeader.value = urlParams.get('hideHeader') === 'true';
}

// Kakao SDK 초기화
const initKakaoSDK = () => {
    const kakaoAppKey = import.meta.env.VITE_KAKAO_CLIENT_ID || '';

    console.log(kakaoAppKey);
    console.log("kakaoAppKey!!!");
    if (!kakaoAppKey) {
        console.error('VITE_KAKAO_CLIENT_ID가 설정되지 않았습니다.');
        return;
    }

    // SDK가 로드될 때까지 대기
    if (window.Kakao) {
        if (!window.Kakao.isInitialized()) {
            window.Kakao.init(kakaoAppKey);
            console.log('Kakao SDK 초기화 완료');
        }
    } else {
        // SDK 로드 대기
        setTimeout(initKakaoSDK, 100);
    }
};

onMounted(() => {
    if (typeof window !== 'undefined') {
        initKakaoSDK();
    }
});

// Kakao 로그인 핸들러
const handleKakaoLogin = () => {
    if (!window.Kakao) {
        alert('Kakao SDK가 로드되지 않았습니다. 페이지를 새로고침해주세요.');
        return;
    }

    if (!window.Kakao.isInitialized()) {
        alert('Kakao SDK 초기화 중입니다. 잠시 후 다시 시도해주세요.');
        return;
    }

    console.log(window.location.origin + '/auth/kakao/callback');
    console.log(111);
    window.Kakao.Auth.authorize({
        redirectUri: window.location.origin + '/auth/kakao/callback',
        scope: 'profile_nickname,profile_image',
    });
};
</script>

<template>
    <Head title="로그인">
        <meta name="robots" content="noindex, nofollow" />
    </Head>
    <Header v-if="!hideHeader" title="로그인"></Header>

    <AuthLayout title="로그인" description="PCAview에 오신 것을 환영합니다" :class="{ 'pt-0': hideHeader }">
        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-3 text-center text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <Form v-bind="AuthenticatedSessionController.store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="space-y-5">
            <div class="space-y-4">
                <div class="space-y-2">
                    <Label for="email" class="text-sm font-medium text-gray-700">이메일</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="h-12 rounded-lg border-gray-300 px-4 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <Label for="password" class="text-sm font-medium text-gray-700">비밀번호</Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs text-blue-600 hover:text-blue-700 hover:underline"
                            :tabindex="5"
                        >
                            비밀번호 찾기
                        </TextLink>
                    </div>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        class="h-12 rounded-lg border-gray-300 px-4 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="flex items-center">
                    <Label for="remember" class="flex cursor-pointer items-center space-x-2">
                        <Checkbox id="remember" name="remember" :tabindex="3" />
                        <span class="text-sm text-gray-600">로그인 상태 유지</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-6 h-12 w-full rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-base font-semibold text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-lg disabled:opacity-50"
                    :tabindex="4"
                    :disabled="processing"
                >
                    <LoaderCircle v-if="processing" class="mr-2 h-5 w-5 animate-spin" />
                    {{ processing ? '로그인 중...' : '로그인' }}
                </Button>
            </div>

            <div class="relative">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-200"></div>
                </div>
                <div class="relative flex justify-center text-xs">
                    <span class="bg-white px-2 text-gray-500">또는</span>
                </div>
            </div>

            <button
                @click="handleKakaoLogin"
                type="button"
                class="flex h-12 w-full items-center justify-center gap-2 rounded-lg bg-[#FEE500] text-base font-semibold text-[#000000] opacity-85 transition-all hover:opacity-100 hover:shadow-lg"
                :tabindex="6"
            >
                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                    <path
                        d="M12 3C6.477 3 2 6.477 2 10.5c0 2.617 1.674 4.915 4.184 6.258-.174.64-.566 2.073-.65 2.404-.097.382.14.378.295.275.122-.08 1.975-1.323 2.798-1.878.576.078 1.167.118 1.773.118 5.523 0 10-3.477 10-7.5S17.523 3 12 3z"
                    />
                </svg>
                카카오 로그인
            </button>

            <div class="text-center">
                <span class="text-sm text-gray-600">계정이 없으신가요?</span>
                <TextLink :href="register()" :tabindex="7" class="ml-1 text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline">
                    가입하기
                </TextLink>
            </div>
        </Form>
    </AuthLayout>
</template>
