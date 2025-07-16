import { defineStore } from "pinia";
import { ref, computed } from "vue";
import { useAuthStore } from "@/stores/auth";
import ApiService from "@/services/ApiService";

export const useClipStore = defineStore("clip", () => {
    const authStore = useAuthStore();
    const headers = computed(() => {
        return {
            Authorization: `Bearer ${authStore.token}`,
            "Content-Type": 'multipart/form-data'
        };
    });

    const getList = async (params = {}) => {
        const response = await ApiService.get(
            `/api/clip/list`,
            params,
            headers.value
        );
        return response;
    };

    const getById = async (slug) => {
        const response = await ApiService.get(
            `/api/clip/${slug}/show`,
            {},
            headers.value
        );
        return response;
    };

    const store = async (data) => {
        const response = await ApiService.post(
            `/api/clip/store`,
            data,
            headers.value
        );
        return response;
    };

    const update = async (id, data) => {
        const response = await ApiService.post(
            `/api/clip/${id}/update`,
            data,
            headers.value
        );
        return response;
    };

    const destroy = async (id) => {
        const response = await ApiService.delete(
            `/api/clip/${id}/destroy`,
            headers.value
        );
        return response;
    };

    const trackView = async (data) => {
        const response = await ApiService.post(
            `/api/clip/check-viewer`,
            data,
            headers.value
        );
        return response;
    };

    const uploadStreams = async (data) => {
        const response = await ApiService.post(
            `/api/clip/upload-streams`,
            data,
            headers.value
        );
        return response;
    };

    const getProcessingStatus = async (clipId) => {
        const response = await ApiService.get(
            `/api/clip/${clipId}/processing-status`,
            {},
            headers.value
        );
        return response;
    };

    const getStreamMetadata = async (clipId) => {
        const response = await ApiService.get(
            `/api/clip/${clipId}/stream-metadata`,
            {},
            headers.value
        );
        return response;
    };

    // const addComment = async (data) => {
    //     const response = await ApiService.post(
    //         `/api/clip/addComment`,
    //         data,
    //         headers.value
    //     );
    //     return response;
    // };

    // const removeComment = async (id) => {
    //     const response = await ApiService.delete(
    //         `/api/clip/removeComment`,
    //         headers.value
    //     );
    //     return response;
    // };

    return {
        getList,
        getById,
        store,
        update,
        destroy,
        trackView,
        uploadStreams,
        getProcessingStatus,
        getStreamMetadata
        //addComment,
        //removeComment
    };
});
