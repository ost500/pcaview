import { route as ziggyRoute } from 'ziggy-js';

/**
 * SSR 환경에서 안전하게 Ziggy route를 사용하기 위한 composable
 */

// 기본 폴백 경로 맵
const fallbackRoutes: Record<string, string> = {
    'home': '/',
    'church': '/church',
    'church.show': '/church/:id',
    'department': '/department',
    'department.show': '/department/:id',
    'profile': '/profile',
    'contents.show': '/contents/:id',
    'dashboard': '/dashboard',
    'login': '/login',
    'register': '/register',
    'privacy-policy': '/privacy-policy',
};

/**
 * SSR 환경에서 안전하게 route를 생성합니다
 * @param name - 라우트 이름
 * @param params - 라우트 파라미터 (선택)
 * @returns 라우트 URL
 */
export function safeRoute(name: string, params?: any): string {
    try {
        return ziggyRoute(name, params);
    } catch (error) {
        console.error(`Route error for ${name}:`, error);

        // 폴백 경로 가져오기
        let fallback = fallbackRoutes[name] || '/';

        // 파라미터가 있으면 경로에 적용
        if (params && typeof params === 'object') {
            Object.entries(params).forEach(([key, value]) => {
                fallback = fallback.replace(`:${key}`, String(value));
            });
        }

        return fallback;
    }
}

/**
 * SSR 환경에서 안전하게 현재 라우트를 체크합니다
 * @param pattern - 체크할 라우트 패턴 (예: 'home', 'church*')
 * @returns 현재 라우트가 패턴과 일치하는지 여부
 */
export function isCurrentRoute(pattern: string): boolean {
    try {
        return ziggyRoute().current(pattern);
    } catch (error) {
        return false;
    }
}

/**
 * 여러 라우트를 한 번에 안전하게 생성합니다
 * @param routes - { key: [name, params?] } 형태의 객체
 * @returns { key: url } 형태의 객체
 */
export function safeRoutes(routes: Record<string, [string, any?]>): Record<string, string> {
    const result: Record<string, string> = {};

    Object.entries(routes).forEach(([key, [name, params]]) => {
        result[key] = safeRoute(name, params);
    });

    return result;
}

/**
 * SSR 환경에서 안전하게 route 객체를 사용하기 위한 composable
 */
export function useSafeRoute() {
    return {
        route: safeRoute,
        current: isCurrentRoute,
        routes: safeRoutes,
    };
}
