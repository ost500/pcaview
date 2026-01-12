<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Department {
    id: number;
    name: string;
    slug: string;
    is_crawl: boolean;
    icon_image: string | null;
    church: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    department: Department;
}

const props = defineProps<Props>();

const form = useForm({
    name: props.department.name,
    is_crawl: props.department.is_crawl ?? true,
    icon_image: null as File | null,
});

const previewImage = ref<string | null>(props.department.icon_image);

function handleImageChange(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (file) {
        form.icon_image = file;
        const reader = new FileReader();
        reader.onload = (e) => {
            previewImage.value = e.target?.result as string;
        };
        reader.readAsDataURL(file);
    }
}

function submit() {
    form.put(`/admin/departments/${props.department.id}`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            // Success handled by redirect
        },
    });
}

function cancel() {
    if (typeof window !== 'undefined') {
        window.location.href = '/admin/departments';
    }
}
</script>

<template>
    <Head :title="`Edit ${department.name}`" />

    <AdminLayout>
        <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Edit Department</h1>
                <p class="mt-2 text-sm text-gray-600">Update department icon and information</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
                <!-- Department Info -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Church</label>
                    <div class="mt-1 text-sm text-gray-900">{{ department.church?.name || '-' }}</div>
                </div>

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700"> Department Name </label>
                    <input
                        id="name"
                        v-model="form.name"
                        type="text"
                        class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:ring-blue-500 focus:outline-none"
                    />
                    <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                        {{ form.errors.name }}
                    </div>
                </div>

                <!-- Enable Crawling -->
                <div>
                    <div class="flex items-center">
                        <input
                            id="is_crawl"
                            v-model="form.is_crawl"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label for="is_crawl" class="ml-2 block text-sm text-gray-700">Enable automatic crawling</label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">Uncheck to disable automatic bulletin crawling for this department</p>
                    <div v-if="form.errors.is_crawl" class="mt-1 text-sm text-red-600">
                        {{ form.errors.is_crawl }}
                    </div>
                </div>

                <!-- Current Icon -->
                <div>
                    <label class="block text-sm font-medium text-gray-700">Current Icon</label>
                    <div class="mt-2">
                        <img v-if="previewImage" :src="previewImage" :alt="department.name" class="h-24 w-24 rounded-lg object-cover shadow" />
                        <div v-else class="h-24 w-24 rounded-lg bg-gray-200"></div>
                    </div>
                </div>

                <!-- Upload New Icon -->
                <div>
                    <label for="icon_image" class="block text-sm font-medium text-gray-700"> Upload New Icon </label>
                    <input
                        id="icon_image"
                        type="file"
                        accept="image/*"
                        @change="handleImageChange"
                        class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                    />
                    <p class="mt-1 text-xs text-gray-500">PNG, JPG, GIF, SVG, WebP up to 2MB</p>
                    <div v-if="form.errors.icon_image" class="mt-1 text-sm text-red-600">
                        {{ form.errors.icon_image }}
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-3 border-t pt-4">
                    <button
                        type="button"
                        @click="cancel"
                        class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
                        :disabled="form.processing"
                    >
                        Cancel
                    </button>
                    <button
                        type="submit"
                        class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:opacity-50"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving...' : 'Save Changes' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
