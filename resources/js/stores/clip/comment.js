import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/services/ApiService";

export const useCommentStore = defineStore("comment", () => {
    const authStore = useAuthStore();
    const headers = computed(() => {
        return {
            Authorization: `Bearer ${authStore.token}`,
            "Content-Type": 'multipart/form-data'
        };
    });

    const getList = async (clipId, params = {}) => {
        const response = await ApiService.get(
            `/api/clip/comment/${clipId}/list`,
            params,
            headers.value
        );
        return response;
    };

    const getById = async (id) => {
        const response = await ApiService.get(
            `/api/clip/comment/${id}/show`,
            {},
            headers.value
        );
        return response;
    };

    const store = async (data) => {
        const response = await ApiService.post(
            `/api/clip/comment/store`,
            data,
            headers.value
        );
        return response;
    };

    const update = async (id, data) => {
        const response = await ApiService.post(
            `/api/clip/comment/${id}/update`,
            data,
            headers.value
        );
        return response;
    };

    const destroy = async (id) => {
        const response = await ApiService.delete(
            `/api/clip/comment/${id}/destroy`,
            headers.value
        );
        return response;
    };

    return {
        getList,
        getById,
        store,
        update,
        destroy
    };
});
