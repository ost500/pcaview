<script setup lang="ts">
import { Church } from '@/types/church';
import { computed } from 'vue';

const props = defineProps<{
    church: Church;
}>();

const emit = defineEmits<{
    click: [slug: string];
}>();

// display_name이 있으면 사용, 없으면 name 사용
const churchDisplayName = computed(() => props.church.display_name || props.church.name);
</script>

<template>
    <div @click="emit('click', church.slug)" class="cursor-pointer transition-transform active:scale-95 sm:hover:scale-105">
        <div class="overflow-hidden rounded-lg bg-white shadow-md">
            <!-- Icon Image -->
            <div class="aspect-square w-full overflow-hidden bg-gray-100">
                <img
                    :src="church.icon_url || '/pcaview_icon.png'"
                    :alt="churchDisplayName + ' 아이콘'"
                    class="h-full w-full object-cover"
                    loading="lazy"
                />
            </div>
            <!-- Church Name -->
            <div class="p-2 sm:p-3">
                <h3 class="text-center text-xs font-medium text-gray-900 sm:text-sm">
                    {{ churchDisplayName }}
                </h3>
            </div>
        </div>
    </div>
</template>
