/**
 * SSR 환경에서 안전하게 Wayfinder route를 사용하기 위한 composable
 */

import * as routes from '@/routes';

/**
 * Wayfinder 라우트를 안전하게 생성합니다
 * @param name - 라우트 이름
 * @param params - 라우트 파라미터 (선택)
 * @returns 라우트 URL
 */
export function safeRoute(name: string, params?: any): string {
    try {
        // Wayfinder routes에서 라우트 함수 가져오기
        const routeFn = (routes as any)[name];

        if (!routeFn) {
            console.warn(`Route '${name}' not found in Wayfinder routes`);
            return '/';
        }

        // URL 생성
        if (typeof routeFn.url === 'function') {
            return routeFn.url(params ? { query: params } : undefined);
        }

        // 함수 직접 호출로 RouteDefinition 얻기
        const definition = routeFn(params ? { query: params } : undefined);
        return definition.url;
    } catch (error) {
        console.error(`Error generating route for '${name}':`, error);
        return '/';
    }
}

/**
 * SSR 환경에서 안전하게 현재 라우트를 체크합니다
 * @param pattern - 체크할 라우트 패턴 (예: 'home', 'church*')
 * @returns 현재 라우트가 패턴과 일치하는지 여부
 */
export function isCurrentRoute(pattern: string): boolean {
    // SSR 환경에서는 항상 false 반환
    if (typeof window === 'undefined') {
        return false;
    }

    try {
        // 현재 URL 경로 가져오기
        const currentPath = window.location.pathname;

        // 패턴을 정규식으로 변환 (* 를 .* 로)
        const regexPattern = pattern.replace(/\*/g, '.*');
        const regex = new RegExp(`^${regexPattern}$`);

        // 정확한 일치 체크
        if (pattern === currentPath) {
            return true;
        }

        // 라우트 이름으로 URL 가져오기
        const routeName = pattern.replace(/\*$/, '');
        const routeFn = (routes as any)[routeName];

        if (routeFn && typeof routeFn.url === 'function') {
            const routeUrl = routeFn.url();

            // 와일드카드 패턴 체크
            if (pattern.endsWith('*')) {
                return currentPath.startsWith(routeUrl);
            }

            // 정확한 일치
            return currentPath === routeUrl;
        }

        return false;
    } catch (error) {
        console.error(`Error checking current route for pattern '${pattern}':`, error);
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
