import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/services/ApiService";

export const useStudioRoomStore = defineStore("studioRoom", () => {
    const authStore = useAuthStore();
    const headers = computed(() => {
        return {
            Authorization: `Bearer ${authStore.token}`,
        };
    });
    const fileHeaders = computed(() => {
        return {
            Authorization: `Bearer ${authStore.token}`,
            'Content-Type': 'multipart/form-data',
        };
    });

    const getList = async (params = {}) => {
        const response = await ApiService.get(
            `/api/studio/rooms/list`,
            params,
            headers.value
        );
        return response;
    };

    const getById = async (id) => {
        const response = await ApiService.get(
            `/api/studio/rooms/${id}/show`,
            {},
            headers.value
        );
        return response;
    };

    const store = async (data) => {
        const response = await ApiService.post(
            `/api/studio/rooms/store`,
            data,
            headers.value
        );
        return response;
    };

    const update = async (id, data) => {
        const response = await ApiService.post(
            `/api/studio/rooms/${id}/update`,
            data,
            headers.value
        );
        return response;
    };

    const destroy = async (id) => {
        const response = await ApiService.delete(
            `/api/studio/rooms/${id}/destroy`,
            headers.value
        );
        return response;
    };

    const saveConfiguration = async (data) => {
        const response = await ApiService.post(
            `/api/studio/rooms/config/store`,
            data,
            fileHeaders.value
        );
        return response;
    };

    const getRoomConfiguration = async (roomId) => {
        const response = await ApiService.get(
            `/api/studio/rooms/config/${roomId}/list`,
            headers.value
        );
        return response;
    };

    return {
        getList,
        getById,
        store,
        update,
        destroy,
        saveConfiguration,
        getRoomConfiguration
    };
});
