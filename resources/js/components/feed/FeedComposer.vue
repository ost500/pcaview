<script setup lang="ts">
import { store as feedStore } from '@/routes/feed';
import { Church } from '@/types/church';
import { Department } from '@/types/department';
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    department?: Department;
    church?: Church;
    departments?: Department[];
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

const content = ref('');
const images = ref<File[]>([]);
const video = ref<File | null>(null);
const isSubmitting = ref(false);
const showImagePreview = ref(false);
const imagePreviewUrls = ref<string[]>([]);
const videoPreviewUrl = ref<string | null>(null);
const selectedDepartmentId = ref<number | null>(
    props.department?.id ?? (props.departments && props.departments.length > 0 ? props.departments[0].id : null),
);
const isExpanded = ref(false);

// 로그인 체크 및 리다이렉트
const checkAuth = () => {
    if (!user.value) {
        // 현재 URL을 저장하고 로그인 페이지로 이동
        if (typeof window !== 'undefined') {
            const currentPath = window.location.pathname + window.location.search;
            router.visit(`/login?intended=${encodeURIComponent(currentPath)}`);
        }
        return false;
    }
    return true;
};

const handleImageSelect = (event: Event) => {
    if (!checkAuth()) return;

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

const handleVideoSelect = (event: Event) => {
    if (!checkAuth()) return;

    const target = event.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        const file = target.files[0];

        // 파일 크기 체크 (100MB)
        if (file.size > 100 * 1024 * 1024) {
            alert('동영상 파일은 100MB 이하만 업로드 가능합니다.');
            return;
        }

        video.value = file;

        // 미리보기 URL 생성
        if (videoPreviewUrl.value) {
            URL.revokeObjectURL(videoPreviewUrl.value);
        }
        videoPreviewUrl.value = URL.createObjectURL(file);
    }
};

const removeVideo = () => {
    if (videoPreviewUrl.value) {
        URL.revokeObjectURL(videoPreviewUrl.value);
    }
    video.value = null;
    videoPreviewUrl.value = null;
};

const expandTextarea = () => {
    if (!checkAuth()) return;
    isExpanded.value = true;
};

const submitPost = () => {
    if (!checkAuth()) return;

    if (!content.value.trim() && images.value.length === 0 && !video.value) {
        alert('내용을 입력하거나 이미지 또는 동영상을 추가해주세요.');
        return;
    }

    // Church mode: no department selection required
    // Department mode: department selection required
    if (!props.church && !selectedDepartmentId.value) {
        alert('부서를 선택해주세요.');
        return;
    }

    isSubmitting.value = true;

    const formData = new FormData();
    formData.append('content', content.value);

    // If church mode, send church_id; otherwise send department_id
    if (props.church) {
        formData.append('church_id', props.church.id.toString());
    } else if (selectedDepartmentId.value) {
        formData.append('department_id', selectedDepartmentId.value.toString());
    }

    images.value.forEach((image, index) => {
        formData.append(`images[${index}]`, image);
    });

    if (video.value) {
        formData.append('video', video.value);
    }

    router.post(feedStore.url(), formData, {
        preserveScroll: true,
        onSuccess: () => {
            content.value = '';
            images.value = [];
            imagePreviewUrls.value.forEach((url) => URL.revokeObjectURL(url));
            imagePreviewUrls.value = [];
            showImagePreview.value = false;
            if (videoPreviewUrl.value) {
                URL.revokeObjectURL(videoPreviewUrl.value);
            }
            video.value = null;
            videoPreviewUrl.value = null;
            isExpanded.value = false;
        },
        onFinish: () => {
            isSubmitting.value = false;
        },
    });
};
</script>

<template>
    <div
        class="mx-auto mb-4 w-full max-w-2xl rounded-lg bg-gradient-to-br from-sky-50 to-blue-50 shadow-sm transition-all hover:from-sky-100 hover:to-blue-100 hover:shadow-md"
    >
        <div class="">
            <div
                class="cursor-pointer overflow-hidden rounded-lg bg-gradient-to-br from-sky-50 to-blue-50 shadow-sm transition-all hover:from-sky-100 hover:to-blue-100 hover:shadow-md"
            >
                <!-- Department/Church 정보 -->
                <div class="flex items-center justify-between gap-3 border-b border-sky-100 bg-white/50 px-4 py-3 backdrop-blur-sm">
                    <!-- 유저 정보 (왼쪽) - 로그인 시에만 표시 -->
                    <div v-if="user" class="flex items-center gap-2">
                        <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-blue-100 to-purple-100">
                            <img
                                v-if="user.profile_photo_url"
                                :src="user.profile_photo_url"
                                :alt="user.name"
                                class="h-full w-full object-cover"
                            />
                            <svg v-else class="h-5 w-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-gray-700">{{ user.name }}</span>
                    </div>

                    <!-- Church/Department 정보 (오른쪽) -->
                    <div class="flex items-center gap-3">
                        <!-- Church mode: show church info only (no dropdown) -->
                        <div v-if="church" class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                <img :src="church.icon_url || '/pcaview_icon.png'" :alt="church.name" class="h-full w-full object-cover" />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-semibold text-sky-900">{{ church.name }}</span>
                                <span class="text-xs text-gray-600">모든 채널에 게시됩니다</span>
                            </div>
                        </div>
                        <!-- Department mode: fixed department -->
                        <div v-else-if="department" class="flex items-center gap-3">
                            <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center overflow-hidden rounded-full bg-sky-100">
                                <img :src="department.icon_image || '/pcaview_icon.png'" :alt="department.name" class="h-full w-full object-cover" />
                            </div>
                            <span class="text-sm font-semibold text-sky-900">{{ department.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- Textarea 영역 -->
                <div class="bg-white/60 px-4 py-2 backdrop-blur-sm">
                    <textarea
                        ref="contentInput"
                        v-model="content"
                        placeholder="무슨 생각을 하고 계신가요?"
                        :rows="isExpanded ? 4 : 2"
                        maxlength="5000"
                        class="w-full resize-none rounded-lg border border-gray-300 px-4 py-3 text-sm transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none"
                        :disabled="isSubmitting"
                        @click="expandTextarea"
                    ></textarea>
                </div>
            </div>

            <!-- 입력 영역 (확장 시에만 표시) -->
            <div v-if="isExpanded" class="px-4 py-2">
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

                <!-- 동영상 미리보기 -->
                <div v-if="videoPreviewUrl" class="mt-3">
                    <div class="relative">
                        <video :src="videoPreviewUrl" class="h-60 w-full rounded-lg object-cover" controls />
                        <button
                            @click="removeVideo"
                            class="bg-opacity-75 hover:bg-opacity-90 absolute top-2 right-2 rounded-full bg-gray-900 p-1 text-white transition"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500">{{ content.length }} / 5000</span>
                    <div class="flex items-center gap-2">
                        <!-- 사진 버튼 -->
                        <label class="flex cursor-pointer items-center gap-1 rounded-lg px-3 py-2 transition hover:bg-gray-100">
                            <svg class="h-4 w-4 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    fill-rule="evenodd"
                                    d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            <span class="text-xs font-medium text-gray-700">사진</span>
                            <input type="file" accept="image/*" multiple class="hidden" @change="handleImageSelect" />
                        </label>
                        <!-- 동영상 버튼 -->
                        <label class="flex cursor-pointer items-center gap-1 rounded-lg px-3 py-2 transition hover:bg-gray-100">
                            <svg class="h-4 w-4 text-purple-600" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M2 6a2 2 0 012-2h6a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM14.553 7.106A1 1 0 0014 8v4a1 1 0 00.553.894l2 1A1 1 0 0018 13V7a1 1 0 00-1.447-.894l-2 1z" />
                            </svg>
                            <span class="text-xs font-medium text-gray-700">동영상</span>
                            <input type="file" accept="video/*" class="hidden" @change="handleVideoSelect" />
                        </label>
                        <!-- 게시 버튼 -->
                        <button
                            @click="submitPost"
                            :disabled="isSubmitting || (!content.trim() && images.length === 0 && !video)"
                            class="rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2 text-sm font-medium text-white transition-all hover:from-blue-700 hover:to-purple-700 hover:shadow-md disabled:cursor-not-allowed disabled:opacity-50"
                        >
                            {{ isSubmitting ? '게시 중...' : '게시' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
textarea:focus {
    outline: none;
}
</style>
