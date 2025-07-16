<template>
     <div class="main-wrapper w-full h-screen color-bg">
          <div v-if="loading" class="flex h-screen">
               <Loading />
          </div>
          <div v-else class="flex h-screen px-2">
               <Header @toggle-sidebar="toggleSidebar" :sidebar="sidebar"/>
               <div class="flex flex-grow w-full">
                    <div v-if="authStore.isAuthenticated" class="sidebar p-5 md:p-0 shadow-brutal md:shadow-none md:hover:shadow-none rounded-sm bg-white md:bg-transparent" :class="{ 'active': sidebar }">
                         <Sidebar @close="toggleSidebar" />
                    </div>
                    <div class="w-full overflow-auto mt-20" id="content-container">
                         <div class="px-2 md:px-4">
                              <router-view></router-view>
                         </div>
                    </div>
               </div>
          </div>
     </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import Header from '@/layouts/clip/Header.vue';
import Sidebar from '@/layouts/partials/Sidebar.vue';
import { useAuthStore } from '@/stores/auth';

const loading = ref(false);
const sidebar = ref(false);
const authStore = useAuthStore();

const toggleSidebar = () => {
     sidebar.value = !sidebar.value;
}

const getUserDetails = async() => {
     if(!authStore.isAuthenticated) return;
     loading.value = true;
     await authStore.getUserDetail();
     loading.value = false;
}

onMounted(async() => {
     await getUserDetails();
});

</script>
<style lang="scss">
.sidebar {
     position: fixed;
     top: 5rem;
     width: 95vw;
     max-height: 90vh;
     transform: translateX(-110%);
     z-index: 1000;
     transition: transform 0.3s ease-in-out;

     @media (min-width: 768px) {
          position: static;
          margin-top: 8rem;
          width: 18rem;
          transform: translateX(0%);
     }

     @media (min-width: 1024px) {
          width: 25rem;
     }

     &.active {
          transform: translateX(0%);
     }

}
</style>