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
            // Misma paleta que el catálogo público, para que el panel se sienta
            // parte de la marca y no un Laravel genérico.
            colors: {
                paper: '#000000',
                ink: '#f2f1ee',
                line: '#282828',
                muted: '#8f8f8f',
                panel: '#0e0e0e',
            },
            fontFamily: {
                serif: ['Cormorant', 'Didot', 'Georgia', ...defaultTheme.fontFamily.serif],
                sans: ['Jost', ...defaultTheme.fontFamily.sans],
            },
            letterSpacing: {
                eyebrow: '0.34em',
                label: '0.22em',
            },
        },
    },

    plugins: [forms],
};
