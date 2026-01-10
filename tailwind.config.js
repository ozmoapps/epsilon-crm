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
                soft: '0 12px 30px -24px rgba(15, 23, 42, 0.6), 0 6px 16px -12px rgba(15, 23, 42, 0.4)',
                card: '0 22px 45px -32px rgba(15, 23, 42, 0.7), 0 10px 20px -15px rgba(15, 23, 42, 0.35)',
            },
        },
    },

    plugins: [forms],
};
