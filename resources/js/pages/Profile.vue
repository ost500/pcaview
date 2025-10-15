<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { router, usePage } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import { computed, ref } from 'vue';
import type { Department } from '@/types/department';

interface Props {
    allDepartments?: Department[];
    subscribedDepartmentIds?: number[];
}

const props = defineProps<Props>();
const page = usePage();
const user = computed(() => page.props.auth.user);

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
            <!-- 로그인 안 된 경우 -->
            <div v-if="!user" class="card">
                <div class="card-body text-center py-5">
                    <i class="fa-solid fa-user-circle" style="font-size: 80px; color: #ccc; margin-bottom: 20px;"></i>
                    <h5 class="mb-3">로그인이 필요합니다</h5>
                    <p class="text-muted mb-4">프로필을 보려면 로그인해주세요</p>
                    <a :href="route('login')" class="btn btn-primary btn-block">로그인</a>
                    <a :href="route('register')" class="btn btn-outline-primary btn-block mt-2">회원가입</a>
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
                                        <i class="fa-solid fa-sign-out-alt me-3 text-danger"></i>
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
                        <p class="mb-0 text-muted small">© 2025 Bulletin App</p>
                    </div>
                </div>
            </div>
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
