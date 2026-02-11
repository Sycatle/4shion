import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
    ],

    theme: {
        extend: {
            colors: {
                ink: '#0E0E0E',
                paper: '#F6F4EF',
                sand: {
                    DEFAULT: '#C8B79A',
                    dark: '#A8977A',
                },
                line: '#E2DED5',
                muted: '#6B6862',
            },
            fontFamily: {
                sans: ['"Inter Variable"', ...defaultTheme.fontFamily.sans],
            },
            letterSpacing: {
                brand: '0.18em',
            },
        },
    },

    plugins: [forms],
};
