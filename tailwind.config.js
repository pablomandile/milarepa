import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    // PrimeFlex (importado en app.js) tiene clases utilitarias con !important
    // (bg-white, text-*, etc.) que ganaban sobre Tailwind. Con important:true las
    // utilidades de Tailwind también llevan !important + Tailwind se importa al
    // final → Tailwind gana por orden de cascada.
    important: true,

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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            boxShadow: {
                'soft-indigo': '0 4px 6px rgba(99, 102, 241, 0.2), 0 1px 3px rgba(99, 102, 241, 0.08)',
            },
        },
    },

    plugins: [forms, typography],
};
