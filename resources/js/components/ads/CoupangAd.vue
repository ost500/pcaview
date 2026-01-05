<script setup lang="ts">
interface Props {
    position?: number;
}

defineProps<Props>();
</script>

<template>
    <div class="coupang-ad-container col-12">
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
</template>

<style scoped>
.coupang-ad-container {
    margin: 1rem 0;
    padding: 0 0.5rem;
    overflow: hidden;
    max-width: 100%;
}

.coupang-ad-container iframe {
    max-width: 100%;
    display: block;
}
</style>
