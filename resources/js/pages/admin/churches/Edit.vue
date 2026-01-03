<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/layouts/admin/AdminLayout.vue';

interface Church {
  id: number;
  name: string;
  slug: string;
  description: string | null;
  icon_image: string | null;
  primary_department_id: number | null;
  primary_department: {
    id: number;
    name: string;
  } | null;
}

interface Department {
  id: number;
  name: string;
}

interface Props {
  church: Church;
  departments: Department[];
}

const props = defineProps<Props>();

const form = useForm({
  name: props.church.name,
  slug: props.church.slug,
  description: props.church.description || '',
  primary_department_id: props.church.primary_department_id,
  icon_image: null as File | null,
});

const previewImage = ref<string | null>(props.church.icon_image);

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
  form.put(`/admin/churches/${props.church.slug}`, {
    forceFormData: true,
    preserveScroll: true,
    onSuccess: () => {
      // Success handled by redirect
    },
  });
}

function cancel() {
  if (typeof window !== 'undefined') {
    window.location.href = '/admin/churches';
  }
}
</script>

<template>
  <Head :title="`Edit ${church.name}`" />

  <AdminLayout>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Edit Church</h1>
        <p class="mt-2 text-sm text-gray-600">Update church information and settings</p>
      </div>

      <form @submit.prevent="submit" class="space-y-6 rounded-lg bg-white p-6 shadow">
        <!-- Name -->
        <div>
          <label for="name" class="block text-sm font-medium text-gray-700">
            Church Name
          </label>
          <input
            id="name"
            v-model="form.name"
            type="text"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
          />
          <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
            {{ form.errors.name }}
          </div>
        </div>

        <!-- Slug -->
        <div>
          <label for="slug" class="block text-sm font-medium text-gray-700">
            Slug (URL)
          </label>
          <input
            id="slug"
            v-model="form.slug"
            type="text"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
          />
          <p class="mt-1 text-xs text-gray-500">Used in URL: /c/{{ form.slug }}</p>
          <div v-if="form.errors.slug" class="mt-1 text-sm text-red-600">
            {{ form.errors.slug }}
          </div>
        </div>

        <!-- Description -->
        <div>
          <label for="description" class="block text-sm font-medium text-gray-700">
            Description
          </label>
          <textarea
            id="description"
            v-model="form.description"
            rows="3"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
          ></textarea>
          <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">
            {{ form.errors.description }}
          </div>
        </div>

        <!-- Primary Department -->
        <div>
          <label for="primary_department_id" class="block text-sm font-medium text-gray-700">
            Primary Department
          </label>
          <select
            id="primary_department_id"
            v-model="form.primary_department_id"
            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-blue-500"
          >
            <option :value="null">- None -</option>
            <option v-for="dept in departments" :key="dept.id" :value="dept.id">
              {{ dept.name }}
            </option>
          </select>
          <div v-if="form.errors.primary_department_id" class="mt-1 text-sm text-red-600">
            {{ form.errors.primary_department_id }}
          </div>
        </div>

        <!-- Current Icon -->
        <div>
          <label class="block text-sm font-medium text-gray-700">Current Icon</label>
          <div class="mt-2">
            <img
              v-if="previewImage"
              :src="previewImage"
              :alt="church.name"
              class="h-24 w-24 rounded-lg object-cover shadow"
            />
            <div v-else class="h-24 w-24 rounded-lg bg-gray-200"></div>
          </div>
        </div>

        <!-- Upload New Icon -->
        <div>
          <label for="icon_image" class="block text-sm font-medium text-gray-700">
            Upload New Icon
          </label>
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
            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            :disabled="form.processing"
          >
            Cancel
          </button>
          <button
            type="submit"
            class="rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
            :disabled="form.processing"
          >
            {{ form.processing ? 'Saving...' : 'Save Changes' }}
          </button>
        </div>
      </form>
    </div>
  </AdminLayout>
</template>
