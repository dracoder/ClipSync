const clipRoutes = [
    {
        path: "/c",
        component: () => import("@/layouts/clip/AppLayout.vue"),
        children: [
            {
                path: "home",
                            name: "clip.home",
            component: () => import("@/pages/clip/Home.vue"),
                meta: {
                    auth: true,
                },
            },
            // {
            //     path: "list",
                    //     name: "clip.list",
        //     component: () => import("@/pages/clip/ClipList.vue"),
            //     meta: {
            //         auth: true,
            //     },
            // },
            {
                path: "record",
                            name: "clip.record",
            component: () => import("@/pages/clip/Record.vue"),
                meta: {
                    auth: true,
                },
            },
            {
                            path: "clip/:slug",
            name: "clip.view",
            component: () => import("@/pages/clip/ViewClip.vue"),
                meta: {
                    auth: false,
                },
            },
        ],
    },
];

export default clipRoutes;
