import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: { DEFAULT: '#2563EB', 600: '#2563EB', 700: '#1D4ED8' },
                secondary: '#4F46E5',
                ink: '#0F172A',
                muted: '#64748B',
                line: '#E2E8F0',
                surface: '#F8FAFC',
            },
            borderRadius: { xl: '14px', '2xl': '18px' },
            boxShadow: { card: '0 1px 2px rgba(15,23,42,.04), 0 4px 16px rgba(15,23,42,.04)' },
        },
    },
    plugins: [forms, typography],
};
