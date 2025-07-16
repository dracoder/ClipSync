import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/services/ApiService";

export const useStudioRoomChatStore = defineStore("studioChatRoom", () => {
    const authStore = useAuthStore();
    const headers = computed(() => {
        return {
            Authorization: `Bearer ${authStore.token}`,
        };
    });

    const getList = async (params = {}, slug = '') => {
        const response = await ApiService.get(
            `/api/studio/rooms/${slug}/chats/list`,
            params,
            headers.value
        );
        return response;
    };


    const store = async (data, slug = '') => {
        const response = await ApiService.post(
            `/api/studio/rooms/${slug}/chats/store`,
            data,
            headers.value
        );
        return response;
    };
    
    const destroy = async (slug) => {
        const response = await ApiService.delete(
            `/api/studio/rooms/${slug}/chats/destroy`,
            headers.value
        );
        return response;
    };
    
    const exportChat = async (slug) => {
        const response = await ApiService.get(`/api/studio/rooms/${slug}/chats/export`,
            headers.value, 
        );
        return response;
    };

    return {
        getList,
        store,
        destroy,
        exportChat,
    };
});
