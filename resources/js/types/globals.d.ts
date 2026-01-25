import { AppPageProps } from '@/types/index';

// Kakao SDK
declare global {
    interface Window {
        Kakao: {
            init: (appKey: string) => void;
            isInitialized: () => boolean;
            Auth: {
                authorize: (options: { redirectUri: string; scope?: string }) => void;
            };
        };

        // WebView Bridges
        webkit?: {
            messageHandlers?: {
                tokenReceiver?: {
                    postMessage: (data: any) => void;
                };
                logout?: {
                    postMessage: (data: any) => void;
                };
            };
        };

        AndroidBridge?: {
            receiveToken: (jsonString: string) => void;
        };

        ReactNativeWebView?: {
            postMessage: (data: string) => void;
        };

        // App Bridge for logout
        AppBridge?: {
            logout: () => void;
        };

        Android?: {
            logout: () => void;
        };

        // Kakao Login Bridge
        KakaoLogin?: {
            kakaoLogin: () => void;
        };
    }
}

// Extend ImportMeta interface for Vite...
declare module 'vite/client' {
    interface ImportMetaEnv {
        readonly VITE_APP_NAME: string;
        readonly VITE_KAKAO_CLIENT_ID: string;
        [key: string]: string | boolean | undefined;
    }

    interface ImportMeta {
        readonly env: ImportMetaEnv;
        readonly glob: <T>(pattern: string) => Record<string, () => Promise<T>>;
    }
}

declare module '@inertiajs/core' {
    interface PageProps extends InertiaPageProps, AppPageProps {}
}

declare module 'vue' {
    interface ComponentCustomProperties {
        $inertia: typeof Router;
        $page: Page;
        $headManager: ReturnType<typeof createHeadManager>;
    }
}
