import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
                display: ['Fraunces', ...defaultTheme.fontFamily.serif],
            },
            colors: {
                pine: {
                    DEFAULT: '#14432f',
                    light: '#1d5a3f',
                    dark: '#0d2e20',
                    deep: '#082018',
                },
                parchment: {
                    DEFAULT: '#f2eee3',
                    dark: '#e8e1d0',
                },
                cream: '#faf7ef',
                brass: {
                    DEFAULT: '#b08d57',
                    light: '#caa86e',
                    dark: '#8a6c3f',
                },
                ink: '#1b1d1a',
            },
        },
    },

    plugins: [forms],
};
