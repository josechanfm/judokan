import { fileURLToPath, URL } from 'node:url';
import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';


export default defineConfig(({ mode }) => {

    process.env = {...process.env, ...loadEnv(mode, process.cwd())};
    return {
        optimizeDeps: { include: ["quill"]},
        server: {
            host: '0.0.0.0',
            hmr: {
                host: process.env.VITE_HOST ?? '127.0.0.1',
            },
        },
        plugins: [
            laravel({
                input: 'resources/js/app.js',
                refresh: true,
            }),
            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        build: {
            // 這個專案的第三方依賴很大（ant-design-vue 4 + ckeditor + quill + 裁剪器 + QR），
            // 把最巨型的兩個拆成獨立 chunk，可以降低 Rollup 打包時的記憶體峰值，亦方便瀏覽器快取。
            //
            // 注意：只拆「純 ESM 而且自成一體」的套件。像 lodash 這種 CJS 套件被其他
            // 依賴（@inertiajs/inertia-vue3 的 cloneDeep）匯入時，拆到另一個 chunk 會令
            // interop 的 default 變成 undefined（登入頁就會出 "qn is not a function"），
            // 所以 lodash／quill／cropper／qrcode 一律留在 vendor。
            chunkSizeWarningLimit: 1600,
            rollupOptions: {
                output: {
                    manualChunks(id) {
                        if (!id.includes('node_modules')) {
                            return undefined;
                        }
                        if (id.includes('ant-design-vue') || id.includes('@ant-design')) {
                            return 'vendor-antd';
                        }
                        if (id.includes('@ckeditor')) {
                            return 'vendor-ckeditor';
                        }

                        return 'vendor';
                    },
                },
            },
        },
    }
});
