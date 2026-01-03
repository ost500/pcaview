<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface Department {
  id: number;
  name: string;
  slug: string;
  icon_image: string | null;
  church: {
    id: number;
    name: string;
  } | null;
}

interface Props {
  departments: {
    data: Department[];
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

function editDepartment(id: number) {
  router.visit(`/admin/departments/${id}/edit`);
}

// 검색어 변경 시 자동으로 검색 실행 (디바운스 적용)
let searchTimeout: ReturnType<typeof setTimeout> | null = null;

watch(search, (value) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    router.get(
      '/admin/departments',
      { search: value },
      {
        preserveState: true,
        preserveScroll: true,
        replace: true,
      }
    );
  }, 300);
});
</script>

<template>
  <Head title="Manage Departments" />

  <AdminLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Manage Departments</h1>
        <p class="mt-2 text-sm text-gray-600">Update department icons and settings</p>
      </div>

      <!-- Search Bar -->
      <div class="mb-6">
        <div class="relative">
          <input
            v-model="search"
            type="text"
            placeholder="Search by department name, slug, or church..."
            class="w-full rounded-lg border border-gray-300 px-4 py-2 pl-10 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
          />
          <svg
            class="absolute left-3 top-2.5 h-5 w-5 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
      </div>

      <div class="overflow-hidden bg-white shadow sm:rounded-lg">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Icon
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Name
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Church
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                Actions
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 bg-white">
            <tr v-for="department in departments.data" :key="department.id" class="hover:bg-gray-50">
              <td class="whitespace-nowrap px-6 py-4">
                <img
                  v-if="department.icon_image"
                  :src="department.icon_image"
                  :alt="department.name"
                  class="h-10 w-10 rounded-full object-cover"
                />
                <div v-else class="h-10 w-10 rounded-full bg-gray-200"></div>
              </td>
              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm font-medium text-gray-900">{{ department.name }}</div>
                <div class="text-sm text-gray-500">{{ department.slug }}</div>
              </td>
              <td class="whitespace-nowrap px-6 py-4">
                <div class="text-sm text-gray-900">{{ department.church?.name || '-' }}</div>
              </td>
              <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                <button
                  @click="editDepartment(department.id)"
                  class="text-blue-600 hover:text-blue-900"
                >
                  Edit
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="departments.links && departments.links.length > 3" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ (departments.current_page - 1) * departments.per_page + 1 }} to
          {{
            Math.min(departments.current_page * departments.per_page, departments.total)
          }}
          of {{ departments.total }} results
        </div>
        <div class="flex gap-2">
          <Link
            v-for="link in departments.links"
            :key="link.label"
            :href="link.url || '#'"
            :class="[
              'rounded border px-4 py-2 text-sm font-medium',
              link.active
                ? 'border-blue-500 bg-blue-500 text-white'
                : link.url
                  ? 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'
                  : 'border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed'
            ]"
            :preserve-scroll="true"
            v-html="link.label"
          />
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
