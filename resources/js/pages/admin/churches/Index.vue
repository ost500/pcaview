<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Church {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon_image: string | null;
    primary_department: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    churches: {
        data: Church[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: {
            url: string | null;
            label: string;
            active: boolean;
        }[];
    };
    filters: {
        search?: string;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');

function editChurch(slug: string) {
    router.visit(`/admin/churches/${slug}/edit`);
}

// 검색어 변경 시 자동으로 검색 실행 (디바운스 적용)
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/churches',
            { search: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
});
</script>

<template>
    <Head title="Manage Churches" />

    <AdminLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Manage Churches</h1>
                <p class="mt-2 text-sm text-gray-600">Update church information and settings</p>
            </div>

            <!-- Search Bar -->
            <div class="mb-6">
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by church name, slug, or description..."
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 pl-10 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 focus:outline-none"
                    />
                    <svg class="absolute top-2.5 left-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Primary Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium tracking-wider text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="church in churches.data" :key="church.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img
                                    v-if="church.icon_image"
                                    :src="church.icon_image"
                                    :alt="church.name"
                                    class="h-10 w-10 rounded-full object-cover"
                                />
                                <div v-else class="h-10 w-10 rounded-full bg-gray-200"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ church.name }}</div>
                                <div v-if="church.description" class="text-sm text-gray-500">
                                    {{ church.description }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ church.slug }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ church.primary_department?.name || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium whitespace-nowrap">
                                <button @click="editChurch(church.slug)" class="text-blue-600 hover:text-blue-900">Edit</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="churches.links && churches.links.length > 3" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ (churches.current_page - 1) * churches.per_page + 1 }} to
                    {{ Math.min(churches.current_page * churches.per_page, churches.total) }}
                    of {{ churches.total }} results
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="link in churches.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        :class="[
                            'rounded border px-4 py-2 text-sm font-medium',
                            link.active
                                ? 'border-blue-500 bg-blue-500 text-white'
                                : link.url
                                  ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                                  : 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400',
                        ]"
                        :preserve-scroll="true"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
