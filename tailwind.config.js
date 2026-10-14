const defaultTheme = require('tailwindcss/defaultTheme');

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './vendor/laravel/jetstream/**/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [
        // 使用 class 策略：避免 forms plugin 輸出 [type='text'] / [type='password'] / select
        // 這類全域選擇器去命中 ant-design-vue 的 .ant-input / .ant-select 等元件
        // （AntD 4 的 cssinjs 會把樣式 prepend 到 <head> 最前面，同權重下會輸給 Tailwind）
        require('@tailwindcss/forms')({ strategy: 'class' }),
        require('@tailwindcss/typography'),
    ],
};
