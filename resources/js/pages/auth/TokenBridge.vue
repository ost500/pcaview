<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { Head } from '@inertiajs/vue3'

const props = defineProps<{
  token: string
  user: {
    id: number
    name: string
    email: string
  }
}>()

const status = ref<'sending' | 'sent' | 'error'>('sending')
const message = ref('')
const isDev = import.meta.env.DEV

const sendTokenToApp = () => {
  try {
    const tokenData = {
      token: props.token,
      user: props.user,
      timestamp: new Date().toISOString(),
    }

    // iOS WebView (WKWebView)
    if (window.webkit?.messageHandlers?.tokenReceiver) {
      window.webkit.messageHandlers.tokenReceiver.postMessage(tokenData)
      status.value = 'sent'
      message.value = 'iOS 앱으로 토큰 전송 완료'
      return
    }

    // Android WebView
    if (window.AndroidBridge?.receiveToken) {
      window.AndroidBridge.receiveToken(JSON.stringify(tokenData))
      status.value = 'sent'
      message.value = 'Android 앱으로 토큰 전송 완료'
      return
    }

    // React Native WebView
    if (window.ReactNativeWebView) {
      window.ReactNativeWebView.postMessage(JSON.stringify(tokenData))
      status.value = 'sent'
      message.value = '앱으로 토큰 전송 완료'
      return
    }

    // 일반 postMessage (fallback)
    window.parent.postMessage(tokenData, '*')
    status.value = 'sent'
    message.value = '토큰 전송 완료'
  } catch (error) {
    console.error('Token transfer error:', error)
    status.value = 'error'
    message.value = '토큰 전송 중 오류가 발생했습니다.'
  }
}

onMounted(() => {
  // 페이지 로드 후 자동으로 토큰 전송
  setTimeout(() => {
    sendTokenToApp()
  }, 500)

  // 3초 후 자동으로 프로필 페이지로 이동 (모바일 파라미터 포함)
  setTimeout(() => {
    if (status.value === 'sent') {
      window.location.href = '/profile?mobilescreen=true&hideHeader=true'
    }
  }, 3000)
})
</script>

<template>
  <Head title="로그인 완료" />

  <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
      <div class="bg-white py-8 px-4 shadow sm:rounded-lg sm:px-10">
        <div class="text-center">
          <!-- Loading State -->
          <div v-if="status === 'sending'" class="space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full">
              <svg
                class="animate-spin h-8 w-8 text-blue-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
              >
                <circle
                  class="opacity-25"
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  stroke-width="4"
                ></circle>
                <path
                  class="opacity-75"
                  fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                ></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">로그인 처리 중...</h3>
            <p class="text-sm text-gray-500">앱으로 로그인 정보를 전송하고 있습니다.</p>
          </div>

          <!-- Success State -->
          <div v-if="status === 'sent'" class="space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full">
              <svg
                class="h-8 w-8 text-green-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M5 13l4 4L19 7"
                ></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">로그인 완료!</h3>
            <p class="text-sm text-gray-500">{{ message }}</p>
            <p class="text-xs text-gray-400 mt-2">잠시 후 자동으로 이동합니다...</p>
          </div>

          <!-- Error State -->
          <div v-if="status === 'error'" class="space-y-4">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full">
              <svg
                class="h-8 w-8 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900">오류 발생</h3>
            <p class="text-sm text-gray-500">{{ message }}</p>
            <button
              @click="sendTokenToApp"
              class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              다시 시도
            </button>
          </div>

          <!-- User Info -->
          <div v-if="status === 'sent'" class="mt-6 pt-6 border-t border-gray-200">
            <div class="text-sm text-gray-600">
              <p class="font-medium">{{ user.name }}</p>
              <p class="text-gray-500">{{ user.email }}</p>
            </div>
          </div>

          <!-- Debug Info (개발 환경에서만) -->
          <div v-if="isDev" class="mt-6 pt-6 border-t border-gray-200">
            <details class="text-left">
              <summary class="text-xs text-gray-500 cursor-pointer">디버그 정보</summary>
              <pre class="mt-2 text-xs text-left bg-gray-100 p-2 rounded overflow-auto">{{
                {
                  token: token.substring(0, 20) + '...',
                  user: user,
                  hasWebKit: !!window.webkit,
                  hasAndroidBridge: !!window.AndroidBridge,
                  hasReactNative: !!window.ReactNativeWebView,
                }
              }}</pre>
            </details>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>