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
            // 拆開 vendor chunk 可以明顯降低 Rollup 打包時的記憶體峰值，亦方便瀏覽器快取
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
                        if (id.includes('quill')) {
                            return 'vendor-quill';
                        }
                        if (id.includes('vue-advanced-cropper')) {
                            return 'vendor-cropper';
                        }
                        if (id.includes('qrcode')) {
                            return 'vendor-qrcode';
                        }
                        if (id.includes('lodash')) {
                            return 'vendor-lodash';
                        }

                        return 'vendor';
                    },
                },
            },
        },
    }
});
