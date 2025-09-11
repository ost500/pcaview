<script setup lang="ts"></script>

<template>
    <!-- Preloader -->
    <div id="preloader">
        <div class="loader">
            <div class="load-circle">
                <div></div>
                <div></div>
            </div>
        </div>
    </div>
    <!-- Preloader end-->

    <!-- Header -->
    <header class="header header-fixed">
        <div class="container">
            <div class="header-content">
                <div class="left-content">
                    <h6 class="title">홈</h6>
                </div>
                <div class="mid-content"></div>
                <div class="right-content"></div>
            </div>
        </div>
    </header>
    <!-- Header -->

    <!-- Page Content Start -->
    <div class="page-content space-top p-b60">
        <div class="container">
            <div class="swiper chat-swiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide m-r15">
                        <a href="chat.html" class="recent active">
                            <div class="media media-60 rounded-circle">
                                <img src="/storage/msch.webp" alt="" />
                            </div>
                            <span>명성교회</span>
                        </a>
                    </div>
                </div>
            </div>
            <div class="title-bar">
                <h6 class="title">소식</h6>
            </div>
            <div class="row" id="contentArea">
                <div class="col-12">
                    <div class="card">
                        <img src="/storage/BULLETIN/after/36.webp" class="card-img-top" alt="..." />
                        <div class="card-body">
                            <h5 class="card-title">명성교회 2025년 9월 10일 주보</h5>
                            <p class="card-text">Some quick example text to build on the card title and make up the bulk of the card's content.</p>
                            <a href="javascript:void(0);" class="btn btn-primary">자세히</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Page Content End -->

    <!-- Menubar -->
    <div class="menubar-area style-2 footer-fixed">
        <div class="toolbar-inner menubar-nav">
            <a href="components/components.html" class="nav-link">
                <i class="icon feather icon-heart-on"></i>
                <span>Features</span>
            </a>
            <a href="media.html" class="nav-link">
                <i class="fa-solid fa-image"></i>
                <span>Media</span>
            </a>
            <a href="index.html" class="nav-link active">
                <i class="fa-solid fa-house"></i>
                <span>Home</span>
            </a>
            <a href="pages.html" class="nav-link">
                <i class="icon feather icon-grid"></i>
                <span>Pages</span>
            </a>
            <a href="home-setting.html" class="nav-link">
                <i class="fa-solid fa-gear"></i>
                <span>Settings</span>
            </a>
        </div>
    </div>
    <!-- Menubar -->
</template>
