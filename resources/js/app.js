import './bootstrap';
import '../css/app.css';
//import '../css/admin.css'

import { createApp, h } from 'vue';
import { createInertiaApp, Head, Link } from '@inertiajs/inertia-vue3';
import { Inertia } from '@inertiajs/inertia';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m';
import { InertiaProgress } from '@inertiajs/progress';
import Antd from 'ant-design-vue';
import { i18nVue, loadLanguageAsync, getActiveLanguage } from 'laravel-vue-i18n'
import 'ant-design-vue/dist/reset.css';


// import { createI18n } from 'vue-i18n';
// import messages from '@/lang/messages.js'
// const i18n = createI18n({
//     locale: 'nl',
//     fallbackLocale: 'en',
//     messages,
// });

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    async setup({ el, app, props, plugin }) {
        // 前端語系跟隨伺服器（HandleInertiaRequests 分享的 lang，預設 zh-TW）
        const initialLang = props?.initialPage?.props?.lang || 'zh-TW';

        const vueApp = createApp({ render: () => h(app, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .use(Antd)
            .use(i18nVue, {
                lang: initialLang,
                resolve: async lang => {
                    const langs = import.meta.glob('../../lang/*.json');
                    return await langs[`../../lang/${lang}.json`]();

                },
            });

        // 先等語言檔載入完成才掛載，避免重新整理時先顯示未翻譯的 key，
        // 也避免 layout 之後的 loadLanguageAsync 與這裡競態
        try {
            await loadLanguageAsync(initialLang);
        } catch (error) {
            console.error('載入語言檔失敗:', error);
        }

        vueApp
            .component('inertia-head', Head)
            .component('inertia-link', Link)
            .mount(el);
    },
});

// Inertia 換頁（包括切換語言後的 redirect back）即時套用新的語系
Inertia.on('navigate', (event) => {
    const lang = event?.detail?.page?.props?.lang;

    if (lang && lang !== getActiveLanguage()) {
        loadLanguageAsync(lang);
    }
});

InertiaProgress.init({ color: '#4B5563' });
