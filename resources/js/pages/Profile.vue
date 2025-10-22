<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { router, usePage, useForm } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';
import type { Department } from '@/types/department';
import BusinessInfo from '@/components/BusinessInfo.vue';

interface Props {
    allDepartments?: Department[];
    subscribedDepartmentIds?: number[];
    canResetPassword?: boolean;
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user);

// 로그인 폼
const loginForm = useForm({
    email: '',
    password: '',
    remember: false,
});

const handleLogin = () => {
    loginForm.post(route('login'), {
        preserveScroll: true,
        onSuccess: () => {
            loginForm.reset('password');
        },
    });
};

// 구독 상태 관리
const subscribed = ref<Set<number>>(new Set(props.subscribedDepartmentIds || []));

const handleLogout = () => {
    if (confirm('로그아웃 하시겠습니까?')) {
        router.post(route('logout'), {}, {
            onSuccess: () => {
                window.location.href = route('home');
            }
        });
    }
};

const goToSettings = () => {
    window.location.href = route('profile.edit');
};

// 부서 구독 토글
const toggleSubscription = (departmentId: number) => {
    router.post(route('profile.subscribe'), {
        department_id: departmentId
    }, {
        preserveScroll: true,
        onSuccess: () => {
            // 구독 상태 토글
            if (subscribed.value.has(departmentId)) {
                subscribed.value.delete(departmentId);
            } else {
                subscribed.value.add(departmentId);
            }
        }
    });
};
</script>

<template>
    <Header title="프로필"></Header>

    <div class="page-content space-top p-b60">
        <div class="container">
            <!-- 로그인 안 된 경우 - 인라인 로그인 폼 -->
            <div v-if="!user" class="card">
                <div class="card-body py-4">
                    <div class="text-center mb-4">
                        <i class="fa-solid fa-user-circle" style="font-size: 60px; color: #007bff;"></i>
                        <h5 class="mt-3 mb-1">로그인</h5>
                        <p class="text-muted small">프로필을 보려면 로그인해주세요</p>
                    </div>

                    <form @submit.prevent="handleLogin">
                        <!-- 이메일 -->
                        <div class="mb-3">
                            <label for="email" class="form-label">이메일</label>
                            <input
                                id="email"
                                type="email"
                                class="form-control"
                                v-model="loginForm.email"
                                placeholder="email@example.com"
                                required
                                autofocus
                                autocomplete="email"
                            />
                            <div v-if="loginForm.errors.email" class="invalid-feedback d-block">
                                {{ loginForm.errors.email }}
                            </div>
                        </div>

                        <!-- 비밀번호 -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="password" class="form-label mb-0">비밀번호</label>
                                <a v-if="canResetPassword" :href="route('password.request')" class="text-muted small">
                                    비밀번호 찾기
                                </a>
                            </div>
                            <input
                                id="password"
                                type="password"
                                class="form-control"
                                v-model="loginForm.password"
                                placeholder="비밀번호를 입력하세요"
                                required
                                autocomplete="current-password"
                            />
                            <div v-if="loginForm.errors.password" class="invalid-feedback d-block">
                                {{ loginForm.errors.password }}
                            </div>
                        </div>

                        <!-- 자동 로그인 -->
                        <div class="mb-3 form-check">
                            <input
                                type="checkbox"
                                class="form-check-input"
                                id="remember"
                                v-model="loginForm.remember"
                            />
                            <label class="form-check-label" for="remember">
                                로그인 상태 유지
                            </label>
                        </div>

                        <!-- 로그인 버튼 -->
                        <button type="submit" class="btn btn-primary btn-block w-100" :disabled="loginForm.processing">
                            <span v-if="loginForm.processing" class="spinner-border spinner-border-sm me-2"></span>
                            로그인
                        </button>

                        <!-- 회원가입 버튼 -->
                        <a :href="route('register')" class="btn btn-outline-secondary btn-block w-100 mt-2">
                            회원가입
                        </a>
                    </form>
                </div>
            </div>

            <!-- 로그인된 경우 -->
            <div v-else>
                <!-- 프로필 카드 -->
                <div class="card mb-3">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fa-solid fa-user-circle" style="font-size: 80px; color: #007bff;"></i>
                        </div>
                        <h5 class="mb-1">{{ user.name }}</h5>
                        <p class="text-muted mb-0">{{ user.email }}</p>
                        <span v-if="user.email_verified_at" class="badge badge-success mt-2">
                            <i class="fa-solid fa-check-circle"></i> 이메일 인증됨
                        </span>
                        <span v-else class="badge badge-warning mt-2">
                            <i class="fa-solid fa-exclamation-circle"></i> 이메일 미인증
                        </span>
                    </div>
                    <iframe
                        src="https://ads-partners.coupang.com/widgets.html?id=927016&template=carousel&trackingCode=AF7527668&subId=&width=680&height=140&tsource="
                        width="100%"
                        height="140"
                        frameborder="0"
                        scrolling="no"
                        referrerpolicy="unsafe-url"
                        browsingtopics
                    ></iframe>
                </div>

                <!-- 구독 부서 관리 -->
                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">
                            <i class="fa-solid fa-star me-2"></i>
                            구독 부서 관리
                        </h6>
                        <p class="text-muted small mb-3">관심있는 부서를 체크하세요. 해당 부서의 소식을 받아볼 수 있습니다.</p>

                        <div v-if="allDepartments && allDepartments.length > 0" class="list-group list-group-flush">
                            <div
                                v-for="department in allDepartments"
                                :key="department.id"
                                class="list-group-item d-flex align-items-center"
                                style="cursor: pointer; border-left: none; border-right: none;"
                                @click="toggleSubscription(department.id)"
                            >
                                <div class="media media-40 rounded-circle me-3">
                                    <img :src="department.icon_image" :alt="department.name" style="width: 40px; height: 40px; object-fit: cover;" />
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0">{{ department.name }}</h6>
                                </div>
                                <div class="form-check">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        :checked="subscribed.has(department.id)"
                                        @click.stop="toggleSubscription(department.id)"
                                        style="width: 24px; height: 24px; cursor: pointer;"
                                    />
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center text-muted py-3">
                            <small>등록된 부서가 없습니다.</small>
                        </div>
                    </div>
                </div>

                <!-- 메뉴 리스트 -->
                <div class="card">
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item" @click="goToSettings" style="cursor: pointer;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fa-solid fa-user-edit me-3"></i>
                                        <span>프로필 수정</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-muted"></i>
                                </div>
                            </li>
                            <li class="list-group-item" @click="handleLogout" style="cursor: pointer;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fa-solid fa-sign -out-alt me-3 text-danger"></i>
                                        <span class="text-danger">로그아웃</span>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-muted"></i>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- 앱 정보 -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3">앱 정보</h6>
                        <p class="mb-1 text-muted small">버전: 1.0.0</p>
                        <p class="mb-0 text-muted small">© 2025 Jubogo App</p>
                    </div>
                </div>
            </div>
            <BusinessInfo></BusinessInfo>

        </div>

    </div>
</template>

<style scoped>
.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item:active {
    background-color: #e9ecef;
}
</style>
