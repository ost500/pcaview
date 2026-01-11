<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Church {
    id: number;
    name: string;
    display_name?: string | null;
}

interface Department {
    id: number;
    name: string;
}

interface User {
    id: number;
    name: string;
}

interface Content {
    id: number;
    title: string;
    description: string | null;
    thumbnail_url: string | null;
    video_url: string | null;
    published_at: string;
    church: Church | null;
    department: Department | null;
    user: User | null;
}

interface Props {
    contents: {
        data: Content[];
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
    churches: Church[];
    departments: Department[];
    filters: {
        search?: string;
        church_id?: number;
        department_id?: number;
    };
}

const props = defineProps<Props>();

const search = ref(props.filters.search || '');
const churchId = ref(props.filters.church_id || '');
const departmentId = ref(props.filters.department_id || '');

function editContent(id: number) {
    router.visit(`/admin/contents/${id}/edit`);
}

function deleteContent(id: number) {
    if (confirm('Are you sure you want to delete this content?')) {
        router.delete(`/admin/contents/${id}`);
    }
}

// 검색어 변경 시 자동으로 검색 실행 (디바운스 적용)
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch([search, churchId, departmentId], () => {
    if (searchTimeout) {
        clearTimeout(searchTimeout);
    }

    searchTimeout = setTimeout(() => {
        router.get(
            '/admin/contents',
            {
                search: search.value || undefined,
                church_id: churchId.value || undefined,
                department_id: departmentId.value || undefined,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    }, 300);
});

function getChurchDisplayName(church: Church | null): string {
    if (!church) return '-';
    return church.display_name || church.name;
}
</script>

<template>
    <Head title="Manage Contents" />

    <AdminLayout>
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Manage Contents</h1>
                    <p class="mt-2 text-sm text-gray-600">View and manage all content posts</p>
                </div>
                <Link
                    href="/admin/contents/create"
                    class="inline-flex items-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    <svg class="-ml-1 mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Create Content
                </Link>
            </div>

            <!-- Filters -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                <!-- Search -->
                <div class="relative">
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Search by title or description..."
                        class="w-full rounded-lg border border-gray-300 px-4 py-2 pl-10 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                    />
                    <svg class="absolute top-2.5 left-3 h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Church Filter -->
                <select
                    v-model="churchId"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Churches</option>
                    <option v-for="church in churches" :key="church.id" :value="church.id">
                        {{ getChurchDisplayName(church) }}
                    </option>
                </select>

                <!-- Department Filter -->
                <select
                    v-model="departmentId"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">All Departments</option>
                    <option v-for="department in departments" :key="department.id" :value="department.id">
                        {{ department.name }}
                    </option>
                </select>
            </div>

            <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Thumbnail</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Title</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Church</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Author</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Published</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        <tr v-for="content in contents.data" :key="content.id" class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <img
                                    v-if="content.thumbnail_url"
                                    :src="content.thumbnail_url"
                                    :alt="content.title"
                                    class="h-16 w-16 rounded object-cover"
                                />
                                <div v-else-if="content.video_url" class="flex h-16 w-16 items-center justify-center rounded bg-gray-200">
                                    <svg class="h-8 w-8 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z" />
                                    </svg>
                                </div>
                                <div v-else class="h-16 w-16 rounded bg-gray-200"></div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900">{{ content.title }}</div>
                                <div v-if="content.description" class="text-sm text-gray-500 line-clamp-2">
                                    {{ content.description }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ getChurchDisplayName(content.church) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ content.department?.name || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ content.user?.name || '-' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ new Date(content.published_at).toLocaleDateString() }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button @click="editContent(content.id)" class="mr-3 text-blue-600 hover:text-blue-900">Edit</button>
                                <button @click="deleteContent(content.id)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="contents.links && contents.links.length > 3" class="mt-6 flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing {{ (contents.current_page - 1) * contents.per_page + 1 }} to
                    {{ Math.min(contents.current_page * contents.per_page, contents.total) }}
                    of {{ contents.total }} results
                </div>
                <div class="flex gap-2">
                    <Link
                        v-for="link in contents.links"
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
