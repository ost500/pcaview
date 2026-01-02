<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);

function logout() {
  if (typeof window !== 'undefined') {
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '/logout';
    
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = page.props.csrf_token as string;
    
    form.appendChild(csrfInput);
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <!-- Navigation -->
    <nav class="border-b border-gray-200 bg-white">
      <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
          <div class="flex">
            <!-- Logo -->
            <div class="flex flex-shrink-0 items-center">
              <a href="/" class="text-xl font-bold text-gray-900">PCAview Admin</a>
            </div>

            <!-- Navigation Links -->
            <div class="ml-10 flex space-x-8">
              <a
                href="/admin/departments"
                class="inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium text-gray-500 hover:border-gray-300 hover:text-gray-700"
                :class="{
                  'border-blue-500 text-gray-900': $page.url.startsWith('/admin/departments'),
                }"
              >
                Departments
              </a>
            </div>
          </div>

          <!-- Right Side -->
          <div class="flex items-center gap-4">
            <a
              href="/"
              class="text-sm font-medium text-gray-500 hover:text-gray-700"
            >
              Back to Site
            </a>
            
            <div v-if="user" class="flex items-center gap-2">
              <span class="text-sm text-gray-700">{{ user.name }}</span>
              <button
                @click="logout"
                class="rounded-md bg-gray-200 px-3 py-1 text-sm font-medium text-gray-700 hover:bg-gray-300"
              >
                Logout
              </button>
            </div>
          </div>
        </div>
      </div>
    </nav>

    <!-- Page Content -->
    <main>
      <slot />
    </main>
  </div>
</template>
