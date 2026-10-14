import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // spektrumX hanya memakai tema terang (Ruang Cetak). Tanpa ini Tailwind v3
    // memakai 'media': class dark:* (mis. dark:text-gray-300 di x-input-label /
    // x-text-input) ikut aktif di PC yang Windows-nya mode gelap → teks pudar.
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
