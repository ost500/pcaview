<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { safeRoute, isCurrentRoute } from '@/composables/useSafeRoute';
import { computed } from 'vue';
import { Home, Star, User } from 'lucide-vue-next';

const page = usePage();
const auth = computed(() => page.props.auth);

// 프로필 클릭 핸들러 - 항상 프로필 페이지로 이동 (페이지 내에서 로그인 상태 체크)
const handleProfileClick = () => {
    window.location.href = safeRoute('profile');
};

// 네비게이션 아이템 활성 상태 확인 함수
const isActive = (routes: string | string[]): boolean => {
    const routeArray = Array.isArray(routes) ? routes : [routes];
    return routeArray.some((route) => isCurrentRoute(route));
};
</script>

<template>
    <!-- Fixed bottom navigation bar -->
    <nav
        class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white shadow-lg"
        aria-label="Bottom Navigation"
    >
        <div class="mx-auto flex h-14 max-w-screen-xl items-center justify-around px-2 sm:h-16 sm:px-4">
            <!-- Home -->
            <a
                :href="safeRoute('home')"
                class="group flex min-w-[3.5rem] flex-1 flex-col items-center gap-0.5 py-2 transition-colors active:scale-95 sm:gap-1 sm:px-3"
                :class="
                    isActive(['home', 'contents*'])
                        ? 'text-blue-600'
                        : 'text-gray-600 active:text-blue-600 sm:hover:text-blue-600'
                "
                aria-label="홈"
            >
                <Home :size="22" :stroke-width="isActive(['home', 'contents*']) ? 2.5 : 2" class="sm:h-6 sm:w-6" />
                <span class="text-[0.625rem] font-medium sm:text-xs">홈</span>
            </a>

            <!-- Department -->
            <a
                :href="safeRoute('department')"
                class="group flex min-w-[3.5rem] flex-1 flex-col items-center gap-0.5 py-2 transition-colors active:scale-95 sm:gap-1 sm:px-3"
                :class="
                    isActive('department*')
                        ? 'text-blue-600'
                        : 'text-gray-600 active:text-blue-600 sm:hover:text-blue-600'
                "
                aria-label="VIEW"
            >
                <Star :size="22" :stroke-width="isActive('department*') ? 2.5 : 2" class="sm:h-6 sm:w-6" />
                <span class="text-[0.625rem] font-medium sm:text-xs">VIEW</span>
            </a>

            <!-- Profile -->
            <a
                href="#"
                @click.prevent="handleProfileClick"
                class="group flex min-w-[3.5rem] flex-1 flex-col items-center gap-0.5 py-2 transition-colors active:scale-95 sm:gap-1 sm:px-3"
                :class="
                    isActive(['profile*', 'settings*'])
                        ? 'text-blue-600'
                        : 'text-gray-600 active:text-blue-600 sm:hover:text-blue-600'
                "
                :aria-label="auth.user ? '프로필' : '로그인'"
            >
                <User :size="22" :stroke-width="isActive(['profile*', 'settings*']) ? 2.5 : 2" class="sm:h-6 sm:w-6" />
                <span class="text-[0.625rem] font-medium sm:text-xs">{{ auth.user ? '프로필' : '로그인' }}</span>
            </a>
        </div>
    </nav>

    <!-- Spacer to prevent content from going under fixed menu bar -->
    <div class="h-14 sm:h-16"></div>
</template>

<style scoped>
/* Component styles are handled by Tailwind classes */
</style>
