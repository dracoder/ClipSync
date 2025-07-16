import { defineStore } from "pinia";
import { ref, computed } from "vue";
import ApiService from "@/services/ApiService";

export const useAuthStore = defineStore("auth", () => {
    const token = ref(null);
    const user = ref(null);

    // check if token is in local storage
    if (localStorage.getItem("token")) {
        token.value = localStorage.getItem("token");
    }

    const isAuthenticated = computed(() => !!token.value);
    const headers = computed(() => ({
        Authorization: `Bearer ${token.value}`,
    }));


    const setToken = (tokenValue) => {
        if (tokenValue == null) {
            localStorage.removeItem("token");
        } else {
            localStorage.setItem("token", tokenValue);
        }
        token.value = tokenValue;
    };

    const setUser = (userValue) => {
        user.value = userValue;
    };

    const getUserDetail = async () => {
        const headers = {
            Authorization: `Bearer ${token.value}`,
        };
        const response = await ApiService.get("/api/me", {}, headers);
        if (response.success) {
            setUser(response.data.user);
        }
        return response;
    };

    const login = async (data) => {
        const response = await ApiService.post("/api/login",data);
        if (response.success) {
            setToken(response.data.token);
            setUser(response.data.user);
        }
        return response;
    };

    const forgotPassword = async (email) => {
        const response = await ApiService.post("/api/forgot-password", {
            email,
        });
        return response;
    };

    const resetPassword = async (data) => {
        const response = await ApiService.post("/api/reset-password", data);
        return response;
    };

    const logout = async () => {
        if (!token.value) {
            flushUser();
            return { success: true };
        }
        try {
            const response = await ApiService.post(
                "/api/logout",
                {},
                headers.value
            );
            if (response.success) {
                flushUser();
            }
            return response;
        } catch (error) {
            console.log(error);
        }
    };

    const updateProfile = async (data) => {
        const response = await ApiService.post("/api/me/update", data, headers.value);
        if (response.success) {
            setUser(response.data.user);
        }
        return response;
    }

    const flushUser = () => {
        setToken(null);
        setUser(null);
    };

    const can = (permission) => {
        if (!user.value || !user.value.permissions) {
            return false;
        }
        return user.value.permissions.includes(permission);
    }


    return {
        token,
        user,
        isAuthenticated,
        login,
        forgotPassword,
        resetPassword,
        setToken,
        setUser,
        flushUser,
        getUserDetail,
        logout,
        can,
        updateProfile,
    };
});
