<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { X } from 'lucide-vue-next';
import { ref, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    churchId: number;
}>();

const emit = defineEmits<{
    close: [];
}>();

const form = useForm({
    name: '',
    church_id: props.churchId,
});

// Update church_id when prop changes
watch(
    () => props.churchId,
    (newChurchId) => {
        form.church_id = newChurchId;
    },
);

const submit = () => {
    form.post('/department', {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('close');
        },
        onError: () => {
            // Errors are automatically handled by Inertia
        },
    });
};

const closeModal = () => {
    if (!form.processing) {
        form.reset();
        emit('close');
    }
};
</script>

<template>
    <!-- Modal Overlay -->
    <Transition
        enter-active-class="transition-opacity duration-200"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition-opacity duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="show" @click="closeModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <!-- Modal Content -->
            <Transition
                enter-active-class="transition-all duration-200"
                enter-from-class="scale-95 opacity-0"
                enter-to-class="scale-100 opacity-100"
                leave-active-class="transition-all duration-200"
                leave-from-class="scale-100 opacity-100"
                leave-to-class="scale-95 opacity-0"
            >
                <div v-if="show" @click.stop class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                    <!-- Header -->
                    <div class="mb-4 flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-gray-900">채널 추가</h2>
                        <button
                            @click="closeModal"
                            :disabled="form.processing"
                            class="rounded-full p-1 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                        >
                            <X :size="24" />
                        </button>
                    </div>

                    <!-- Form -->
                    <form @submit.prevent="submit">
                        <div class="mb-4">
                            <label for="channel-name" class="mb-2 block text-sm font-medium text-gray-700"> 채널 이름 </label>
                            <input
                                id="channel-name"
                                v-model="form.name"
                                type="text"
                                placeholder="예: 청년부, 어린이부"
                                class="w-full rounded-lg border border-gray-300 px-4 py-2 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                                :disabled="form.processing"
                                required
                            />
                            <div v-if="form.errors.name" class="mt-1 text-sm text-red-600">
                                {{ form.errors.name }}
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="closeModal"
                                :disabled="form.processing"
                                class="flex-1 rounded-lg border border-gray-300 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                            >
                                취소
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="flex-1 rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                            >
                                {{ form.processing ? '생성 중...' : '생성' }}
                            </button>
                        </div>
                    </form>
                </div>
            </Transition>
        </div>
    </Transition>
</template>
