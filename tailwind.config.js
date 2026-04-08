import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js}',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },

            fontSize: {
                '2xs': ['0.625rem', { lineHeight: '0.875rem' }],
            },

            spacing: {
                120: '30rem',
            },

            // ── Semantic color tokens (driven by CSS variables) ──────────────
            // Usage: bg-bg, bg-surface, bg-surface-2, bg-surface-2/50, etc.
            colors: {
                bg: 'rgb(var(--color-bg) / <alpha-value>)',
                surface: {
                    DEFAULT: 'rgb(var(--color-surface) / <alpha-value>)',
                    2: 'rgb(var(--color-surface-2) / <alpha-value>)',
                    3: 'rgb(var(--color-surface-3) / <alpha-value>)',
                },
                // Usage: bg-badge-danger-bg, text-badge-danger-text, etc.
                badge: {
                    danger:  { bg: 'rgb(var(--color-badge-danger-bg)  / <alpha-value>)', text: 'rgb(var(--color-badge-danger-text)  / <alpha-value>)' },
                    warning: { bg: 'rgb(var(--color-badge-warning-bg) / <alpha-value>)', text: 'rgb(var(--color-badge-warning-text) / <alpha-value>)' },
                    primary: { bg: 'rgb(var(--color-badge-primary-bg) / <alpha-value>)', text: 'rgb(var(--color-badge-primary-text) / <alpha-value>)' },
                    info:    { bg: 'rgb(var(--color-badge-info-bg)    / <alpha-value>)', text: 'rgb(var(--color-badge-info-text)    / <alpha-value>)' },
                },
            },

            // Usage: text-primary, text-secondary, text-muted, text-subtle
            textColor: {
                primary:   'rgb(var(--color-text-primary)   / <alpha-value>)',
                secondary: 'rgb(var(--color-text-secondary) / <alpha-value>)',
                muted:     'rgb(var(--color-text-muted)     / <alpha-value>)',
                subtle:    'rgb(var(--color-text-subtle)    / <alpha-value>)',
            },

            // Usage: border-base, border-strong, border-subtle
            borderColor: {
                base:   'rgb(var(--color-border)        / <alpha-value>)',
                strong: 'rgb(var(--color-border-strong) / <alpha-value>)',
                subtle: 'rgb(var(--color-border-subtle) / <alpha-value>)',
            },
        },
    },

    plugins: [forms, typography],
};
