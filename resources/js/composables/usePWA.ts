import { onBeforeUnmount, onMounted, ref } from 'vue';

export function usePWA() {
    const deferredPrompt = ref<any>(null);
    const showInstallPrompt = ref(false);
    const showIOSInstructions = ref(false);
    const isInstalled = ref(false);
    const isMobile = ref(false);
    const isIOS = ref(false);

    // 모바일 기기 감지
    const detectMobile = () => {
        const userAgent = navigator.userAgent || navigator.vendor || (window as any).opera;
        isMobile.value = /android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent.toLowerCase());
        // iOS 기기 감지
        isIOS.value = /iphone|ipad|ipod/i.test(userAgent.toLowerCase());
        return isMobile.value;
    };

    // PWA가 이미 설치되었는지 확인
    const checkIfInstalled = () => {
        // Standalone 모드로 실행 중인지 확인 (홈화면에서 실행)
        if (window.matchMedia('(display-mode: standalone)').matches) {
            isInstalled.value = true;
            return true;
        }

        // iOS Safari의 standalone 모드 확인
        if ((navigator as any).standalone) {
            isInstalled.value = true;
            return true;
        }

        return false;
    };

    // PWA 설치 이벤트 핸들러
    const handleBeforeInstallPrompt = (e: Event) => {
        // 기본 브라우저 프롬프트 방지
        e.preventDefault();

        // 나중에 사용하기 위해 이벤트 저장
        deferredPrompt.value = e;

        // 설치되지 않았고 모바일인 경우만 프롬프트 표시
        if (!isInstalled.value && isMobile.value) {
            showInstallPrompt.value = true;
        }
    };

    // 사용자가 설치 프롬프트를 클릭했을 때
    const promptInstall = async () => {
        console.log('=== 설치하기 클릭 ===');
        console.log('deferredPrompt:', deferredPrompt.value);
        console.log('isIOS:', isIOS.value);
        console.log('isMobile:', isMobile.value);

        // Android Chrome/Edge - 네이티브 프롬프트
        if (deferredPrompt.value) {
            console.log('Android 네이티브 프롬프트 실행');
            deferredPrompt.value.prompt();
            const { outcome } = await deferredPrompt.value.userChoice;
            console.log(`사용자 선택: ${outcome}`);
            deferredPrompt.value = null;
            showInstallPrompt.value = false;
            return;
        }

        // iOS Safari - 수동 안내 표시
        if (isIOS.value) {
            console.log('iOS 안내 모달 표시');
            showInstallPrompt.value = false;
            showIOSInstructions.value = true;
            console.log('showIOSInstructions:', showIOSInstructions.value);
            return;
        }

        // 데스크톱 또는 PWA 미지원 브라우저 - 즐겨찾기 안내
        console.log('데스크톱 안내 모달 표시');
        showInstallPrompt.value = false;
        showIOSInstructions.value = true;
        console.log('showIOSInstructions:', showIOSInstructions.value);
    };

    // 설치 완료 이벤트 핸들러
    const handleAppInstalled = () => {
        console.log('PWA 설치 완료');
        isInstalled.value = true;
        showInstallPrompt.value = false;
        deferredPrompt.value = null;
    };

    // 프롬프트 닫기 (나중에)
    const dismissPrompt = () => {
        showInstallPrompt.value = false;

        // 사용자가 닫으면 7일 동안 다시 표시하지 않음
        localStorage.setItem('pwa-prompt-dismissed', Date.now().toString());
    };

    // 프롬프트 영구 닫기 (다시 보지 않기)
    const dismissPermanently = () => {
        showInstallPrompt.value = false;
        showIOSInstructions.value = false;

        // 영구적으로 표시하지 않음
        localStorage.setItem('pwa-prompt-never-show', 'true');
    };

    // iOS 안내 모달 닫기
    const closeIOSInstructions = () => {
        showIOSInstructions.value = false;
    };

    // 프롬프트를 다시 표시해도 되는지 확인
    const shouldShowPrompt = () => {
        // 영구 닫기 상태 확인
        const neverShow = localStorage.getItem('pwa-prompt-never-show');
        if (neverShow === 'true') {
            return false;
        }

        const dismissedTime = localStorage.getItem('pwa-prompt-dismissed');
        if (!dismissedTime) return true;

        // 7일 후에 다시 표시
        const sevenDaysInMs = 7 * 24 * 60 * 60 * 1000;
        const timeSinceDismissed = Date.now() - parseInt(dismissedTime);

        return timeSinceDismissed > sevenDaysInMs;
    };

    onMounted(() => {
        detectMobile();
        checkIfInstalled();

        console.log('=== PWA 초기화 ===');
        console.log('isInstalled:', isInstalled.value);
        console.log('shouldShowPrompt:', shouldShowPrompt());
        console.log('localStorage dismissed:', localStorage.getItem('pwa-prompt-dismissed'));
        console.log('localStorage never-show:', localStorage.getItem('pwa-prompt-never-show'));

        // 이미 설치되었거나 최근에 닫은 경우 프롬프트 표시 안 함
        if (isInstalled.value || !shouldShowPrompt()) {
            console.log('프롬프트 표시 안 함 (설치됨 또는 닫힘)');
            return;
        }

        // 모바일과 데스크톱 모두 프롬프트 표시
        showInstallPrompt.value = true;
        console.log('프롬프트 표시:', showInstallPrompt.value);

        // PWA 설치 이벤트 리스너 등록 (Chrome/Edge Android용)
        window.addEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.addEventListener('appinstalled', handleAppInstalled);
    });

    onBeforeUnmount(() => {
        window.removeEventListener('beforeinstallprompt', handleBeforeInstallPrompt);
        window.removeEventListener('appinstalled', handleAppInstalled);
    });

    // 개발용: localStorage 초기화 (테스트용)
    const resetPromptState = () => {
        localStorage.removeItem('pwa-prompt-dismissed');
        localStorage.removeItem('pwa-prompt-never-show');
        showInstallPrompt.value = false;
        console.log('PWA 프롬프트 상태 초기화됨. 페이지를 새로고침하세요.');
    };

    return {
        showInstallPrompt,
        showIOSInstructions,
        isInstalled,
        isMobile,
        isIOS,
        promptInstall,
        dismissPrompt,
        dismissPermanently,
        closeIOSInstructions,
        resetPromptState, // 개발용
    };
}
