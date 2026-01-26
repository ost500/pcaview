<script setup lang="ts">
import Header from '@/components/template/Header.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="개인정보 처리방침">
        <meta name="description" content="PCAview 주보고 개인정보 처리방침 - 이용자의 개인정보를 안전하게 보호합니다." />
    </Head>

    <Header title="개인정보 처리방침"></Header>

    <div class="page-content space-top p-b60">
        <div class="container">
            <div class="mx-auto max-w-4xl space-y-8 py-6">
                <div class="space-y-4">
                    <h1 class="text-3xl font-bold">개인정보 처리방침</h1>
                    <p class="text-muted-foreground">
                        주보고(이하 "서비스")는 이용자의 개인정보를 중요시하며, 「개인정보 보호법」 등 관련 법령을 준수하고 있습니다.
                    </p>
                </div>

                <div class="space-y-6">
                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">1. 개인정보의 수집 및 이용 목적</h2>
                        <p class="text-muted-foreground">서비스는 다음의 목적을 위해 개인정보를 수집하고 이용합니다:</p>
                        <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                            <li>회원 가입 및 관리: 회원제 서비스 제공, 본인 확인, 회원자격 유지·관리</li>
                            <li>주보 알림 서비스: 교회 주보 및 소식 알림 제공, 구독 관리</li>
                            <li>서비스 개선: 서비스 이용 통계 분석 및 맞춤형 서비스 제공</li>
                        </ul>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">2. 수집하는 개인정보의 항목</h2>
                        <div class="space-y-3">
                            <div>
                                <h3 class="mb-2 text-lg font-medium">필수 수집 항목</h3>
                                <ul class="ml-6 list-disc space-y-1 text-muted-foreground">
                                    <li>이름</li>
                                    <li>이메일 주소</li>
                                    <li>비밀번호 (암호화하여 저장)</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-medium">자동 수집 항목</h3>
                                <ul class="ml-6 list-disc space-y-1 text-muted-foreground">
                                    <li>서비스 이용 기록</li>
                                    <li>접속 로그</li>
                                    <li>쿠키 정보</li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">3. 개인정보의 보유 및 이용기간</h2>
                        <p class="text-muted-foreground">
                            서비스는 이용자의 개인정보를 회원 가입일로부터 서비스를 제공하는 기간 동안 보유 및 이용합니다. 회원 탈퇴 시 지체없이
                            파기합니다.
                        </p>
                        <p class="text-muted-foreground">단, 관련 법령에 의해 보존할 필요가 있는 경우 해당 법령에서 정한 기간 동안 보관합니다:</p>
                        <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                            <li>계약 또는 청약철회 등에 관한 기록: 5년 (전자상거래법)</li>
                            <li>소비자의 불만 또는 분쟁처리에 관한 기록: 3년 (전자상거래법)</li>
                            <li>서비스 이용 기록: 3개월 (통신비밀보호법)</li>
                        </ul>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">4. 개인정보의 제3자 제공</h2>
                        <p class="text-muted-foreground">
                            서비스는 원칙적으로 이용자의 개인정보를 제3자에게 제공하지 않습니다. 다만, 다음의 경우는 예외로 합니다:
                        </p>
                        <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                            <li>이용자가 사전에 동의한 경우</li>
                            <li>법령의 규정에 의거하거나, 수사 목적으로 법령에 정해진 절차와 방법에 따라 수사기관의 요구가 있는 경우</li>
                        </ul>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">5. 사용자 업로드 콘텐츠의 이용 및 보호</h2>
                        <p class="text-muted-foreground">사용자가 커뮤니티 게시글에 업로드한 사진 및 기타 콘텐츠는 다음과 같이 관리됩니다:</p>
                        <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                            <li>업로드된 사진은 앱 내 콘텐츠 표시를 위해서만 사용됩니다</li>
                            <li>광고 제공업체를 포함한 제3자에게 공유되지 않습니다</li>
                            <li>사용자는 언제든지 자신이 업로드한 콘텐츠를 삭제할 수 있습니다</li>
                            <li>콘텐츠 삭제 시 관련된 이미지 파일도 서버에서 영구 삭제됩니다</li>
                        </ul>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">6. 개인정보 처리의 위탁</h2>
                        <p class="text-muted-foreground">서비스는 원활한 서비스 제공을 위해 다음과 같이 개인정보 처리업무를 위탁하고 있습니다:</p>
                        <div class="overflow-x-auto">
                            <table class="w-full border-collapse border border-sidebar-border">
                                <thead>
                                    <tr class="bg-muted/50">
                                        <th class="border border-sidebar-border p-3 text-left">수탁업체</th>
                                        <th class="border border-sidebar-border p-3 text-left">위탁업무 내용</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td class="border border-sidebar-border p-3 text-muted-foreground">Google Cloud Platform</td>
                                        <td class="border border-sidebar-border p-3 text-muted-foreground">데이터 저장 및 관리</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">7. 이용자의 권리와 행사 방법</h2>
                        <p class="text-muted-foreground">이용자는 언제든지 다음과 같은 권리를 행사할 수 있습니다:</p>
                        <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                            <li>개인정보 열람 요구</li>
                            <li>개인정보 정정 요구</li>
                            <li>개인정보 삭제 요구</li>
                            <li>개인정보 처리 정지 요구</li>
                        </ul>
                        <p class="text-muted-foreground">위 권리 행사는 서비스의 '설정 &gt; 프로필' 메뉴를 통해 직접 하실 수 있습니다.</p>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">8. 개인정보의 파기절차 및 방법</h2>
                        <p class="text-muted-foreground">
                            서비스는 개인정보 보유기간의 경과, 처리목적 달성 등 개인정보가 불필요하게 되었을 때에는 지체없이 해당 개인정보를
                            파기합니다.
                        </p>
                        <div class="space-y-2">
                            <h3 class="text-lg font-medium">파기절차</h3>
                            <p class="text-muted-foreground">
                                이용자가 입력한 정보는 목적 달성 후 별도의 DB에 옮겨져(종이의 경우 별도의 서류) 내부 방침 및 기타 관련 법령에 따라
                                일정기간 저장된 후 혹은 즉시 파기됩니다.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-lg font-medium">파기방법</h3>
                            <ul class="ml-6 list-disc space-y-1 text-muted-foreground">
                                <li>전자적 파일 형태: 기록을 재생할 수 없는 기술적 방법을 사용하여 삭제</li>
                                <li>종이에 출력된 개인정보: 분쇄기로 분쇄하거나 소각</li>
                            </ul>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">9. 개인정보 보호책임자</h2>
                        <p class="text-muted-foreground">
                            서비스는 개인정보 처리에 관한 업무를 총괄해서 책임지고, 개인정보 처리와 관련한 이용자의 불만처리 및 피해구제 등을 위하여
                            아래와 같이 개인정보 보호책임자를 지정하고 있습니다.
                        </p>
                        <div class="rounded-lg border border-sidebar-border bg-muted/30 p-4">
                            <p class="font-medium">개인정보 보호책임자</p>
                            <p class="text-muted-foreground">이메일: privacy@pcaview.com</p>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">10. 개인정보 처리방침의 변경</h2>
                        <p class="text-muted-foreground">
                            이 개인정보 처리방침은 시행일로부터 적용되며, 법령 및 방침에 따른 변경내용의 추가, 삭제 및 정정이 있는 경우에는 변경사항의
                            시행 7일 전부터 공지사항을 통하여 고지할 것입니다.
                        </p>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">11. 쿠키의 운영</h2>
                        <p class="text-muted-foreground">서비스는 이용자에게 개별적인 맞춤서비스를 제공하기 위해 쿠키(Cookie)를 사용합니다.</p>
                        <div class="space-y-2">
                            <h3 class="text-lg font-medium">쿠키란?</h3>
                            <p class="text-muted-foreground">
                                쿠키는 웹사이트가 이용자의 컴퓨터 브라우저로 전송하는 소량의 정보입니다. 이용자가 웹사이트에 접속할 때 컴퓨터는
                                이용자의 브라우저에 있는 쿠키의 내용을 읽고, 추가정보를 찾아 서비스를 제공합니다.
                            </p>
                        </div>
                        <div class="space-y-2">
                            <h3 class="text-lg font-medium">쿠키의 사용 목적</h3>
                            <ul class="ml-6 list-disc space-y-1 text-muted-foreground">
                                <li>로그인 상태 유지</li>
                                <li>이용자 맞춤 서비스 제공</li>
                                <li>서비스 이용 통계 분석</li>
                            </ul>
                        </div>
                    </section>

                    <section class="space-y-3">
                        <h2 class="text-2xl font-semibold">12. 아동 보호 및 안전 정책</h2>
                        <p class="text-muted-foreground">
                            주보고는 아동의 온라인 안전과 보호를 최우선으로 생각하며, 아동 성적 학대 및 착취(CSAE) 근절을 위해 다음과 같은 정책을
                            시행하고 있습니다.
                        </p>
                        <div class="space-y-3">
                            <div>
                                <h3 class="mb-2 text-lg font-medium">아동 보호 원칙</h3>
                                <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                                    <li>서비스는 만 14세 미만 아동의 개인정보를 수집하지 않습니다</li>
                                    <li>아동 성적 학대 및 착취(CSAE) 콘텐츠를 엄격히 금지합니다</li>
                                    <li>부적절한 콘텐츠 발견 시 즉시 삭제 및 관계 기관에 신고합니다</li>
                                    <li>사용자가 업로드한 모든 콘텐츠를 모니터링하고 관리합니다</li>
                                </ul>
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-medium">신고 및 대응</h3>
                                <p class="text-muted-foreground">
                                    아동 성적 학대 및 착취(CSAE) 관련 콘텐츠를 발견하신 경우, 즉시 아래 연락처로 신고해 주시기 바랍니다:
                                </p>
                                <div class="mt-2 rounded-lg border border-sidebar-border bg-muted/30 p-4">
                                    <p class="font-medium">아동 보호 신고 센터</p>
                                    <p class="text-muted-foreground">이메일: safety@pcaview.com</p>
                                    <p class="text-muted-foreground">
                                        또는
                                        <a
                                            href="https://www.safe182.go.kr/"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="text-primary hover:underline"
                                        >
                                            아동·청소년 대상 성범죄 신고센터
                                        </a>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <h3 class="mb-2 text-lg font-medium">콘텐츠 관리</h3>
                                <ul class="ml-6 list-disc space-y-2 text-muted-foreground">
                                    <li>모든 사용자 업로드 콘텐츠는 자동화된 시스템과 인력을 통해 검토됩니다</li>
                                    <li>CSAE 관련 의심 콘텐츠 발견 시 즉시 차단 및 조사를 진행합니다</li>
                                    <li>위반 사용자는 즉시 계정 정지 및 법적 조치가 취해집니다</li>
                                    <li>관련 법령에 따라 수사기관에 적극 협조합니다</li>
                                </ul>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="border-t border-sidebar-border pt-6">
                    <p class="text-sm text-muted-foreground">시행일자: 2025년 1월 1일</p>
                </div>
            </div>
        </div>
    </div>
</template>
