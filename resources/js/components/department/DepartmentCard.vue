<script setup lang="ts">
import { Department } from '@/types/department';

defineProps<{
    department: Department;
}>();

const emit = defineEmits<{
    click: [id: number];
}>();
</script>

<template>
    <div
        @click="emit('click', department.id)"
        class="cursor-pointer transition-transform active:scale-95 sm:hover:scale-105"
    >
        <div class="overflow-hidden rounded-lg bg-white shadow-md">
            <!-- Icon Image -->
            <div class="aspect-square w-full overflow-hidden bg-gray-100">
                <img
                    :src="department.icon_image || '/pcaview_icon.png'"
                    :alt="department.name + ' 아이콘'"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
            </div>
            <!-- Department Name -->
            <div class="p-2 sm:p-3">
                <h3 class="text-center text-xs font-medium text-gray-900 sm:text-sm">
                    {{ department.name }}
                </h3>
            </div>
        </div>
    </div>
</template>
