<script setup lang="ts">
import { store as feedStore } from '@/routes/feed';
import { Department } from '@/types/department';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    department: Department;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const content = ref('');
const images = ref<File[]>([]);
const isSubmitting = ref(false);
const showImagePreview = ref(false);
const imagePreviewUrls = ref<string[]>([]);

const handleImageSelect = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const files = Array.from(target.files);
        images.value = [...images.value, ...files];

        // 미리보기 URL 생성
        files.forEach((file) => {
            const url = URL.createObjectURL(file);
            imagePreviewUrls.value.push(url);
        });

        showImagePreview.value = true;
    }
};

const removeImage = (index: number) => {
    URL.revokeObjectURL(imagePreviewUrls.value[index]);
    images.value.splice(index, 1);
    imagePreviewUrls.value.splice(index, 1);

    if (images.value.length === 0) {
        showImagePreview.value = false;
    }
};

const submitPost = () => {
    if (!content.value.trim() && images.value.length === 0) {
        alert('내용을 입력하거나 이미지를 추가해주세요.');
        return;
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('content', content.value);
    formData.append('department_id', props.department.id.toString());

    images.value.forEach((image, index) => {
        formData.append(`images[${index}]`, image);
    });

    router.post(feedStore.url(), formData, {
        preserveScroll: true,
        onSuccess: () => {
            content.value = '';
            images.value = [];
            imagePreviewUrls.value.forEach((url) => URL.revokeObjectURL(url));
            imagePreviewUrls.value = [];
            showImagePreview.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <div class="mx-auto mb-4 w-full max-w-2xl rounded-lg bg-gradient-to-br from-sky-50 to-blue-50 shadow-sm transition-all hover:from-sky-100 hover:to-blue-100 hover:shadow-md">
        <div class="space-y-4">
            <div
                class="cursor-pointer overflow-hidden rounded-lg bg-gradient-to-br from-sky-50 to-blue-50 shadow-sm transition-all hover:from-sky-100 hover:to-blue-100 hover:shadow-md"
            >
                <!-- Department 정보 -->
                <div v-if="department" class="flex items-center gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                        <img :src="department.icon_image" :alt="department.name" class="h-full w-full object-cover" />
                    </div>
                    <span class="text-sm font-semibold text-sky-900">{{ department.name }}</span>
                </div>

                <!-- 타이틀 및 자세히 버튼 -->
                <div class="bg-white/60 px-4 py-3 backdrop-blur-sm">
                    <textarea
                        ref="contentInput"
                        v-model="content"
                        placeholder="무슨 생각을 하고 계신가요?"
                        rows="4"
                        maxlength="5000"
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                        :disabled="isSubmitting"
                    ></textarea>

                </div>
                <!-- 액션 버튼들 -->
                <div class="border-t border-gray-200 p-3">
                    <div class="flex items-center justify-around">
                        <label class="flex cursor-pointer items-center gap-2 rounded-lg px-4 py-2 transition hover:bg-gray-100">
                            <svg class="h-5 w-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-sm font-medium text-gray-700">사진</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="handleImageSelect" />
                        </label>
                    </div>
                </div>

            </div>
        </div>

        <!-- 입력 영역 (포커스 시 확장) -->
        <div class="p-4">
            <!-- 이미지 미리보기 -->
            <div v-if="showImagePreview && imagePreviewUrls.length > 0" class="mt-3 grid grid-cols-2 gap-2">
                <div v-for="(url, index) in imagePreviewUrls" :key="index" class="relative">
                    <img :src="url" class="h-40 w-full rounded-lg object-cover" />
                    <button
                        @click="removeImage(index)"
                        class="bg-opacity-75 hover:bg-opacity-90 absolute top-2 right-2 rounded-full bg-gray-900 p-1 text-white transition"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <div class="mt-3 flex items-center justify-between">
                <span class="text-xs text-gray-500">{{ content.length }} / 5000</span>
                <button
                    @click="submitPost"
                    :disabled="isSubmitting || (!content.trim() && images.length === 0)"
                    class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 text-sm font-medium text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                >
                    {{ isSubmitting ? '게시 중...' : '게시' }}
                </button>
            </div>
        </div>

    </div>
</template>

<style scoped>
textarea:focus {
    outline: none;
}
</style>
