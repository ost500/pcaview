<script setup lang="ts">
import PasswordResetLinkController from '@/actions/App/Http/Controllers/Auth/PasswordResetLinkController';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { login } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout title="비밀번호 찾기" description="비밀번호 재설정 링크를 받을 이메일을 입력하세요">
        <Head title="비밀번호 찾기 - 명성교회 주보고">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <div class="space-y-6">
            <Form v-bind="PasswordResetLinkController.store.form()" v-slot="{ errors, processing }">
                <div class="grid gap-2">
                    <Label for="email">이메일</Label>
                    <Input id="email" type="email" name="email" autocomplete="off" autofocus placeholder="email@example.com" />
                    <InputError :message="errors.email" />
                </div>

                <div class="my-6 flex items-center justify-start">
                    <Button class="w-full" :disabled="processing">
                        <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                        비밀번호 재설정 링크 보내기
                    </Button>
                </div>
            </Form>

            <div class="space-x-1 text-center text-sm text-muted-foreground">
                <span>또는,</span>
                <TextLink :href="login()">로그인</TextLink>
                <span>으로 돌아가기</span>
            </div>
        </div>
    </AuthLayout>
</template>
