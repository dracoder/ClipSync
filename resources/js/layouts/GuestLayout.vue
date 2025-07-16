<template>
    <div class="main-wrapper w-full h-screen color-bg">
        <div class="w-full h-screen overflow-auto" id="content-container">
            <div class="absolute top-0 right-0 p-4">
            <LanguageSwitcher />
            </div>
            <div class="flex flex-col gap-4 w-full h-full justify-center items-center">
                <div class="flex items-center">
                                <img :src="'/img/clipsync.svg'" alt="ClipSync Logo" class="h-20 w-20" />
            <a href="/" class="no-link-color -ml-2 -mt-2">
                <p class="clip-logo">CLIPSYNC</p>
            </a>
                </div>
                <div class="flex flex-col w-full">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
<script setup>

</script>