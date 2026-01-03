<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
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
  };
}

defineProps<Props>();

function editDepartment(id: number) {
  if (typeof window !== 'undefined') {
    window.location.href = `/admin/departments/${id}/edit`;
  }
}
</script>

<template>
  <Head title="Manage Departments" />

  <AdminLayout>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Manage Departments</h1>
        <p class="mt-2 text-sm text-gray-600">Update department icons and settings</p>
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
      <div v-if="departments.last_page > 1" class="mt-6 flex items-center justify-between">
        <div class="text-sm text-gray-700">
          Showing {{ (departments.current_page - 1) * departments.per_page + 1 }} to
          {{
            Math.min(departments.current_page * departments.per_page, departments.total)
          }}
          of {{ departments.total }} results
        </div>
        <div class="flex gap-2">
          <a
            v-if="departments.current_page > 1"
            :href="`/admin/departments?page=${departments.current_page - 1}`"
            class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Previous
          </a>
          <a
            v-if="departments.current_page < departments.last_page"
            :href="`/admin/departments?page=${departments.current_page + 1}`"
            class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
          >
            Next
          </a>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
