<script setup lang="ts">
import AdminLayout from '@/layouts/admin/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Department {
    id: number;
    name: string;
}

interface Props {
    departments: Department[];
}

const props = defineProps<Props>();

const form = useForm({
    name: '',
    display_name: '',
    slug: '',
    description: '',
    address: '',
    primary_department_id: null as number | null,
    icon_image: null as File | null,
    logo_image: null as File | null,
    worship_time_image: null as File | null,
    address_image: null as File | null,
});

const previewIcon = ref<string | null>(null);
const previewLogo = ref<string | null>(null);
const previewWorshipTime = ref<string | null>(null);
const previewAddress = ref<string | null>(null);

function handleIconChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.icon_image = target.files[0];
        previewIcon.value = URL.createObjectURL(target.files[0]);
    }
}

function handleLogoChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.logo_image = target.files[0];
        previewLogo.value = URL.createObjectURL(target.files[0]);
    }
}

function handleWorshipTimeChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.worship_time_image = target.files[0];
        previewWorshipTime.value = URL.createObjectURL(target.files[0]);
    }
}

function handleAddressChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        form.address_image = target.files[0];
        previewAddress.value = URL.createObjectURL(target.files[0]);
    }
}

function submit() {
    form.post('/admin/churches', {
        preserveScroll: true,
        onSuccess: () => {
            router.visit('/admin/churches');
        },
    });
}

function cancel() {
    router.visit('/admin/churches');
}
</script>

<template>
    <Head title="Create Church" />

    <AdminLayout>
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-gray-900">Create New Church</h1>
                <p class="mt-2 text-sm text-gray-600">Add a new church to the system</p>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <div class="overflow-hidden bg-white shadow sm:rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <div class="grid grid-cols-1 gap-6">
                            <!-- Name -->
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Church Name *</label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</div>
                            </div>

                            <!-- Display Name -->
                            <div>
                                <label for="display_name" class="block text-sm font-medium text-gray-700">Display Name</label>
                                <input
                                    id="display_name"
                                    v-model="form.display_name"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">If set, this will be displayed instead of the church name</p>
                                <div v-if="form.errors.display_name" class="mt-1 text-sm text-red-600">{{ form.errors.display_name }}</div>
                            </div>

                            <!-- Slug -->
                            <div>
                                <label for="slug" class="block text-sm font-medium text-gray-700">Slug *</label>
                                <input
                                    id="slug"
                                    v-model="form.slug"
                                    type="text"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">URL-friendly identifier (e.g., 'goldang', 'maple')</p>
                                <div v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</div>
                            </div>

                            <!-- Description -->
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="3"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                ></textarea>
                                <div v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</div>
                            </div>

                            <!-- Address -->
                            <div>
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <input
                                    id="address"
                                    v-model="form.address"
                                    type="text"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                />
                                <div v-if="form.errors.address" class="mt-1 text-sm text-red-600">{{ form.errors.address }}</div>
                            </div>

                            <!-- Primary Department -->
                            <div>
                                <label for="primary_department_id" class="block text-sm font-medium text-gray-700">Primary Department</label>
                                <select
                                    id="primary_department_id"
                                    v-model="form.primary_department_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                >
                                    <option :value="null">None</option>
                                    <option v-for="dept in departments" :key="dept.id" :value="dept.id">
                                        {{ dept.name }}
                                    </option>
                                </select>
                                <div v-if="form.errors.primary_department_id" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.primary_department_id }}
                                </div>
                            </div>

                            <!-- Icon Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Icon Image</label>
                                <input type="file" @change="handleIconChange" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <img v-if="previewIcon" :src="previewIcon" alt="Icon preview" class="mt-2 h-24 w-24 rounded-lg object-cover shadow" />
                                <div v-if="form.errors.icon_image" class="mt-1 text-sm text-red-600">{{ form.errors.icon_image }}</div>
                            </div>

                            <!-- Logo Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Logo Image</label>
                                <input type="file" @change="handleLogoChange" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <img v-if="previewLogo" :src="previewLogo" alt="Logo preview" class="mt-2 h-24 w-auto rounded-lg object-cover shadow" />
                                <div v-if="form.errors.logo_image" class="mt-1 text-sm text-red-600">{{ form.errors.logo_image }}</div>
                            </div>

                            <!-- Worship Time Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Worship Time Image</label>
                                <input type="file" @change="handleWorshipTimeChange" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <img
                                    v-if="previewWorshipTime"
                                    :src="previewWorshipTime"
                                    alt="Worship time preview"
                                    class="mt-2 h-auto w-64 rounded-lg object-cover shadow"
                                />
                                <div v-if="form.errors.worship_time_image" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.worship_time_image }}
                                </div>
                            </div>

                            <!-- Address Image -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Address Image (Map)</label>
                                <input type="file" @change="handleAddressChange" accept="image/*" class="mt-1 block w-full text-sm text-gray-500" />
                                <img
                                    v-if="previewAddress"
                                    :src="previewAddress"
                                    alt="Address preview"
                                    class="mt-2 h-auto w-64 rounded-lg object-cover shadow"
                                />
                                <div v-if="form.errors.address_image" class="mt-1 text-sm text-red-600">{{ form.errors.address_image }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 text-right sm:px-6">
                        <button
                            type="button"
                            @click="cancel"
                            class="mr-3 inline-flex justify-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="inline-flex justify-center rounded-md border border-transparent bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Creating...' : 'Create Church' }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
