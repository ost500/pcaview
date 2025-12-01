<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { safeRoute, isCurrentRoute } from '@/composables/useSafeRoute';
import { computed } from 'vue';
import { Home, Church, Star, User } from 'lucide-vue-next';

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
        class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
        aria-label="Bottom Navigation"
    >
        <div class="mx-auto flex h-16 max-w-screen-xl items-center justify-around px-4">
            <!-- Home -->
            <a
                :href="safeRoute('home')"
                class="group flex flex-col items-center gap-1 px-3 py-2 transition-colors"
                :class="
                    isActive(['home', 'contents*'])
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400'
                "
                aria-label="홈"
            >
                <Home :size="24" :stroke-width="isActive(['home', 'contents*']) ? 2.5 : 2" />
                <span class="text-xs font-medium">홈</span>
            </a>

            <!-- Church -->
            <a
                :href="safeRoute('church')"
                class="group flex flex-col items-center gap-1 px-3 py-2 transition-colors"
                :class="
                    isActive('church*')
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400'
                "
                aria-label="교회"
            >
                <Church :size="24" :stroke-width="isActive('church*') ? 2.5 : 2" />
                <span class="text-xs font-medium">교회</span>
            </a>

            <!-- Department -->
            <a
                :href="safeRoute('department')"
                class="group flex flex-col items-center gap-1 px-3 py-2 transition-colors"
                :class="
                    isActive('department*')
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400'
                "
                aria-label="부서"
            >
                <Star :size="24" :stroke-width="isActive('department*') ? 2.5 : 2" />
                <span class="text-xs font-medium">부서</span>
            </a>

            <!-- Profile -->
            <a
                href="#"
                @click.prevent="handleProfileClick"
                class="group flex flex-col items-center gap-1 px-3 py-2 transition-colors"
                :class="
                    isActive(['profile*', 'settings*'])
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-gray-600 hover:text-blue-600 dark:text-gray-400 dark:hover:text-blue-400'
                "
                :aria-label="auth.user ? '프로필' : '로그인'"
            >
                <User :size="24" :stroke-width="isActive(['profile*', 'settings*']) ? 2.5 : 2" />
                <span class="text-xs font-medium">{{ auth.user ? '프로필' : '로그인' }}</span>
            </a>
        </div>
    </nav>

    <!-- Spacer to prevent content from going under fixed menu bar -->
    <div class="h-16"></div>
</template>

<style scoped>
/* Component styles are handled by Tailwind classes */
</style>
