import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "@/stores/auth";
import studioRoutes from "./studio";
import clipRoutes from "./clip";

const routes = [
    ...studioRoutes,
    ...clipRoutes,
    // catch all 404
    {
        path: "/login",
        component: () => import("@/pages/auth/Login.vue"),
        name: "login",
        meta: {
            guest: true,
        },
    },
    {
        path: "/",
        component: () => import("@/layouts/AppLayout.vue"),
        children: [
            {
                path: "profile",
                name: "profile",
                component: () => import("@/pages/auth/Profile.vue"),
                meta: {
                    auth: true,
                },
            },
        ],
    },
    {
        path: "/:pathMatch(.*)*",
        component: () => import("@/pages/errors/NotFound.vue"),
        name: "404",
        meta: {
            auth: false,
            title: "404",
        },
    },
];
const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to, form, next) => {
    handleRouteMiddlwares(to, form, next);
});

const handleRouteMiddlwares = async (to, from, next) => {
    const authStore = useAuthStore();

    if (to.meta.auth && !authStore.isAuthenticated) {
        window.location.href = "/login";
        return;
    }

    if (to.meta.auth && !authStore.user) {
        let response = await authStore.getUserDetail();
        if (!response.success) {
            authStore.flushUser();
            window.location.href = "/login";
            return;
        }
    }
    if (to.meta.guest && authStore.isAuthenticated) {
        if (authStore.user) {
            if(to.name.startsWith("clip.")){
                return next({ name: "clip.home" });
            } else {
                return next({ name: "studio.list" });
            }
        }
        let response = await authStore.getUserDetail();
        if (response.success) {
            if(to.name.startsWith("clip.")){
                return next({ name: "clip.home" });
            } else {
                return next({ name: "studio.list" });
            }
        } else {
            authStore.flushUser();
        }
    }

    next();
};

export default router;
