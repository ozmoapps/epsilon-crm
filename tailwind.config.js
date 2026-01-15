import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import colors from 'tailwindcss/colors';

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
                gray: colors.slate,
                brand: colors.slate,
                // Semantic colors for consistent action states
                semantic: {
                    success: {
                        50: '#f0fdf4',
                        100: '#dcfce7',
                        500: '#22c55e',
                        600: '#16a34a',
                        700: '#15803d',
                    },
                    warning: {
                        50: '#fffbeb',
                        100: '#fef3c7',
                        500: '#f59e0b',
                        600: '#d97706',
                        700: '#b45309',
                    },
                    danger: {
                        50: '#fef2f2',
                        100: '#fee2e2',
                        500: '#ef4444',
                        600: '#dc2626',
                        700: '#b91c1c',
                    },
                    info: {
                        50: '#eff6ff',
                        100: '#dbeafe',
                        500: '#3b82f6',
                        600: '#2563eb',
                        700: '#1d4ed8',
                    },
                },
            },
            spacing: {
                '4.5': '1.125rem', // 18px
                '18': '4.5rem',    // 72px
                '22': '5.5rem',    // 88px
            },
            fontSize: {
                // Typography scale for consistent headings
                'heading-1': ['2.25rem', { lineHeight: '2.5rem', fontWeight: '700', letterSpacing: '-0.025em' }],
                'heading-2': ['1.875rem', { lineHeight: '2.25rem', fontWeight: '600', letterSpacing: '-0.0125em' }],
                'heading-3': ['1.5rem', { lineHeight: '2rem', fontWeight: '600' }],
                'heading-4': ['1.25rem', { lineHeight: '1.75rem', fontWeight: '600' }],
                'body': ['1rem', { lineHeight: '1.5rem' }],
                'body-sm': ['0.875rem', { lineHeight: '1.25rem' }],
            },
            boxShadow: {
                soft: '0 10px 22px -18px rgba(15, 23, 42, 0.45), 0 4px 12px -10px rgba(15, 23, 42, 0.25)',
                card: '0 16px 32px -22px rgba(15, 23, 42, 0.45), 0 6px 14px -10px rgba(15, 23, 42, 0.2)',
                'card-hover': '0 20px 38px -18px rgba(15, 23, 42, 0.5), 0 8px 16px -8px rgba(15, 23, 42, 0.25)',
            },
            transitionDuration: {
                DEFAULT: '200ms',
            },
            transitionTimingFunction: {
                DEFAULT: 'cubic-bezier(0.4, 0, 0.2, 1)',
            },
        },
    },

    plugins: [forms],
};
