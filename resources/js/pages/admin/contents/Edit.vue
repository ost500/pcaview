<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, ref } from 'vue';

interface Church {
    id: number;
    name: string;
    display_name?: string | null;
}

interface Department {
    id: number;
    name: string;
    church_id: number | null;
}

interface User {
    id: number;
    name: string;
}

interface Content {
    id: number;
    title: string;
    church_id: number;
    department_id: number | null;
    published_at: string;
    is_hide: boolean;
    thumbnail_url: string | null;
    file_url: string | null;
    file_type: string | null;
    video_url: string | null;
    body: string | null;
    church: Church | null;
    department: Department | null;
    departments: Department[];
    user: User | null;
}

interface Props {
    content: Content;
    churches: Church[];
    departments: Department[];
}

const props = defineProps<Props>();

const form = useForm({
    title: props.content.title,
    church_id: props.content.church_id as number | null,
    department_id: props.content.department_id as number | null,
    departments: props.content.departments?.map((d) => d.id) || ([] as number[]),
    published_at: props.content.published_at ? new Date(props.content.published_at).toISOString().slice(0, 16) : '',
    is_hide: props.content.is_hide || false,
    thumbnail: null as File | null,
    images: [] as File[],
    video: null as File | null,
});

const previewThumbnail = ref<string | null>(props.content.thumbnail_url);
const previewVideo = ref<string | null>(props.content.file_type === 'video' ? props.content.file_url : props.content.video_url);
const previewImages = ref<string[]>([]);

// Church에 속한 Departments만 필터링
const filteredDepartments = computed(() => {
    if (!form.church_id) return [];
    return props.departments.filter((dept) => dept.church_id === form.church_id);
});

// 기존 이미지 파싱
onMounted(() => {
    if (props.content.body) {
        try {
            const bodyData = JSON.parse(props.content.body);
            if (bodyData.images && Array.isArray(bodyData.images)) {
                previewImages.value = bodyData.images;
            }
        } catch (e) {
            // body가 JSON이 아닐 수 있음
        }
    }
});

function handleThumbnailChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.thumbnail = target.files[0];
        previewThumbnail.value = URL.createObjectURL(target.files[0]);
    }
}

function handleImagesChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files) {
        form.images = Array.from(target.files);
        previewImages.value = Array.from(target.files).map((file) => URL.createObjectURL(file));
    }
}

function handleVideoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.video = target.files[0];
        previewVideo.value = URL.createObjectURL(target.files[0]);
    }
}

function submit() {
    form.post(`/admin/contents/${props.content.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/admin/contents');
        },
        // Laravel의 _method spoofing을 위해
        headers: {
            'X-HTTP-Method-Override': 'PUT',
        },
    });
}

function cancel() {
    router.visit('/admin/contents');
}

function getChurchDisplayName(church: Church): string {
    return church.display_name || church.name;
}
</script>

<template>
    <Head title="Edit Content" />

    <AdminLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Content</h1>
                <p class="mt-2 text-sm text-gray-600">Update content information</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Title -->
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Title *</label>
                                <input
                                    id="title"
                                    v-model="form.title"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <div v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</div>
                            </div>

                            <!-- Church -->
                            <div>
                                <label for="church_id" class="block text-sm font-medium text-gray-700">Church *</label>
                                <select
                                    id="church_id"
                                    v-model="form.church_id"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option :value="null">Select Church</option>
                                    <option v-for="church in churches" :key="church.id" :value="church.id">
                                        {{ getChurchDisplayName(church) }}
                                    </option>
                                </select>
                                <div v-if="form.errors.church_id" class="mt-1 text-sm text-red-600">{{ form.errors.church_id }}</div>
                            </div>

                            <!-- Departments (Multiple Select) -->
                            <div v-if="form.church_id">
                                <label for="departments" class="block text-sm font-medium text-gray-700">Departments</label>
                                <select
                                    id="departments"
                                    v-model="form.departments"
                                    multiple
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                    size="5"
                                >
                                    <option v-for="department in filteredDepartments" :key="department.id" :value="department.id">
                                        {{ department.name }}
                                    </option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Hold Ctrl/Cmd to select multiple departments</p>
                                <div v-if="form.errors.departments" class="mt-1 text-sm text-red-600">{{ form.errors.departments }}</div>
                            </div>

                            <!-- Published At -->
                            <div>
                                <label for="published_at" class="block text-sm font-medium text-gray-700">Published At</label>
                                <input
                                    id="published_at"
                                    v-model="form.published_at"
                                    type="datetime-local"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <div v-if="form.errors.published_at" class="mt-1 text-sm text-red-600">{{ form.errors.published_at }}</div>
                            </div>

                            <!-- Hide from Feed -->
                            <div>
                                <div class="flex items-center">
                                    <input
                                        id="is_hide"
                                        v-model="form.is_hide"
                                        type="checkbox"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    />
                                    <label for="is_hide" class="ml-2 block text-sm text-gray-700">Hide from feed</label>
                                </div>
                                <p class="mt-1 text-xs text-gray-500">Check this to hide the content from public feeds</p>
                                <div v-if="form.errors.is_hide" class="mt-1 text-sm text-red-600">{{ form.errors.is_hide }}</div>
                            </div>

                            <!-- Thumbnail -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Thumbnail Image</label>
                                <input type="file" @change="handleThumbnailChange" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <img
                                    v-if="previewThumbnail"
                                    :src="previewThumbnail"
                                    alt="Thumbnail preview"
                                    class="mt-2 h-32 w-auto rounded-lg object-cover shadow"
                                />
                                <div v-if="form.errors.thumbnail" class="mt-1 text-sm text-red-600">{{ form.errors.thumbnail }}</div>
                            </div>

                            <!-- Images -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Content Images</label>
                                <input
                                    type="file"
                                    @change="handleImagesChange"
                                    accept="image/*"
                                    multiple
                                    class="mt-1 block w-full text-sm text-gray-500"
                                />
                                <div v-if="previewImages.length > 0" class="mt-2 grid grid-cols-4 gap-2">
                                    <img
                                        v-for="(img, idx) in previewImages"
                                        :key="idx"
                                        :src="img"
                                        alt="Image preview"
                                        class="h-24 w-auto rounded-lg object-cover shadow"
                                    />
                                </div>
                                <p v-if="previewImages.length > 0" class="mt-1 text-xs text-gray-500">Upload new images to replace existing ones</p>
                                <div v-if="form.errors.images" class="mt-1 text-sm text-red-600">{{ form.errors.images }}</div>
                            </div>

                            <!-- Video -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Video (Max 500MB)</label>
                                <input type="file" @change="handleVideoChange" accept="video/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <video v-if="previewVideo" :src="previewVideo" controls class="mt-2 h-48 w-auto rounded-lg shadow"></video>
                                <p v-if="previewVideo" class="mt-1 text-xs text-gray-500">Upload a new video to replace the existing one</p>
                                <div v-if="form.errors.video" class="mt-1 text-sm text-red-600">{{ form.errors.video }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                        <button
                            type="button"
                            @click="cancel"
                            class="mr-3 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                        >
                            {{ form.processing ? 'Updating...' : 'Update Content' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
