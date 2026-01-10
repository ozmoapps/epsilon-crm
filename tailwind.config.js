import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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
            colors: {
                brand: {
                    50: '#ecfeff',
                    100: '#cffafe',
                    200: '#a5f3fc',
                    300: '#67e8f9',
                    400: '#22d3ee',
                    500: '#06b6d4',
                    600: '#0891b2',
                    700: '#0e7490',
                    800: '#155e75',
                    900: '#164e63',
                    950: '#0d3745',
                },
            },
            boxShadow: {
                soft: '0 10px 22px -18px rgba(15, 23, 42, 0.45), 0 4px 12px -10px rgba(15, 23, 42, 0.25)',
                card: '0 16px 32px -22px rgba(15, 23, 42, 0.45), 0 6px 14px -10px rgba(15, 23, 42, 0.2)',
            },
        },
    },

    plugins: [forms],
};
