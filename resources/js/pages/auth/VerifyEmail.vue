<script setup lang="ts">
import EmailVerificationNotificationController from '@/actions/App/Http/Controllers/Auth/EmailVerificationNotificationController';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import AuthLayout from '@/layouts/AuthLayout.vue';
import { logout } from '@/routes';
import { Form, Head } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AuthLayout title="이메일 인증" description="이메일로 전송된 링크를 클릭하여 이메일 주소를 인증해주세요.">
        <Head title="이메일 인증">
            <meta name="robots" content="noindex, nofollow" />
        </Head>

        <div v-if="status === 'verification-link-sent'" class="mb-4 text-center text-sm font-medium text-green-600">
            회원가입 시 입력하신 이메일 주소로 새로운 인증 링크가 전송되었습니다.
        </div>

        <Form v-bind="EmailVerificationNotificationController.store.form()" class="space-y-6 text-center" v-slot="{ processing }">
            <Button :disabled="processing" variant="secondary">
                <LoaderCircle v-if="processing" class="h-4 w-4 animate-spin" />
                인증 이메일 재전송
            </Button>

            <TextLink :href="logout()" as="button" class="mx-auto block text-sm">로그아웃</TextLink>
        </Form>
    </AuthLayout>
</template>
