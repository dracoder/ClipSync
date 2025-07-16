import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        vue(),
        laravel({
            input: [
                "resources/css/app.css",
                "resources/sass/app.scss",
                "resources/js/app.js",
            ],
            refresh: true,
        }),
    ],
    css: {
        preprocessorOptions: {
            scss: {
                api : 'modern-compiler',
            }
        },
    },
    resolve: {
        alias: {
            // "vue": "vue/dist/vue.esm-bundler.js",
            "@": "/resources/js",
        },
    },
});
