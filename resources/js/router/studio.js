const studioRoutes = [
    {
        path: "/n",
        component: () => import("@/layouts/AppLayout.vue"),
        children: [
            {
                path: "list",
                name: "studio.list",
                component: () => import("@/pages/studio/RoomList.vue"),
                meta: {
                    auth: true,
                },
            },
            {
                path: "configuration",
                name: "configuration",
                component: () => import("@/pages/studio/Config.vue"),
                meta: {
                    auth: true,
                },
            },
        ],
    },
    {
        path: "/n/:room",
        name: "studio.room",
        component: () => import("@/pages/studio/StudioRoom.vue"),
    }
];

export default studioRoutes;
