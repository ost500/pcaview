<script setup lang="ts">
import AuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import Header from '@/components/template/Header.vue';
import BusinessInfo from '@/components/BusinessInfo.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { register } from '@/routes';
import { request } from '@/routes/password';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="로그인">
        <meta name="robots" content="noindex, nofollow" />
    </Head>
    <Header title="로그인"></Header>

    <AuthLayout title="로그인" description="PCAview에 오신 것을 환영합니다">
        <div v-if="status" class="mb-4 rounded-lg bg-green-50 p-3 text-center text-sm font-medium text-green-700">
            {{ status }}
        </div>

        <Form
            v-bind="AuthenticatedSessionController.store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="space-y-5"
        >
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

            <div class="text-center">
                <span class="text-sm text-gray-600">계정이 없으신가요?</span>
                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="ml-1 text-sm font-semibold text-blue-600 hover:text-blue-700 hover:underline"
                >
                    가입하기
                </TextLink>
            </div>
        </Form>
    </AuthLayout>
</template>
