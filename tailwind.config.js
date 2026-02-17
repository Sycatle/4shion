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
                ink: '#17181A',
                paper: '#F7F7F4',
                concrete: '#E4E4E0',
                steel: '#8C9096',
                line: '#D6D6D0',
                muted: '#5E6369',
                signal: {
                    DEFAULT: '#2A3FE8',
                    dark: '#1F31C0',
                },
            },
            fontFamily: {
                sans: ['"Inter Variable"', ...defaultTheme.fontFamily.sans],
                display: ['"Archivo Variable"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
