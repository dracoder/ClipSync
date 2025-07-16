<template>
     <div class="main-wrapper w-full color-bg">
          <div v-if="loading"
            class="flex h-screen">
               <Loading />
          </div>
          <div v-else
            class="flex flex-col h-screen px-2">
               <Header />
               <div class="w-full h-full overflow-auto px-2 md:px-4" id="content-container">
                    <slot />
               </div>
          </div>
     </div>
</template>
<script setup>
import { ref } from 'vue';
import Header from '@/layouts/studio/Header.vue';

const loading = ref(false);
</script>
