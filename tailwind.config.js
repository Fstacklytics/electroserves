import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/**
 * ElectroServes design tokens.
 *
 * Colours, type scale and spacing come from the design system section of
 * README.md and docs/phase-0/06-architecture-decisions.md (ADR-004).
 *
 * Contrast note: `primary-700` on white is 8.6:1 and white on `primary-700` is
 * 8.6:1, so both directions clear the 4.5:1 WCAG AA requirement. The amber
 * secondary is only ever used as a background behind `neutral-900` text, or as
 * `secondary-700` text on white (5.1:1) — never as light amber text on white.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './app/**/*.php',
        './lang/**/*.php',
    ],

    darkMode: 'class',

    theme: {
        screens: {
            sm: '640px',
            md: '768px',
            lg: '1024px',
            xl: '1280px',
            '2xl': '1536px',
        },

        extend: {
            colors: {
                primary: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    200: '#bfdbfe',
                    300: '#93c5fd',
                    400: '#60a5fa',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                    900: '#1e3a8a',
                    950: '#172554',
                },
                secondary: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    200: '#fde68a',
                    300: '#fcd34d',
                    400: '#fbbf24',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                    900: '#78350f',
                    950: '#451a03',
                },
                neutral: {
                    50: '#f8fafc',
                    100: '#f1f5f9',
                    200: '#e2e8f0',
                    300: '#cbd5e1',
                    400: '#94a3b8',
                    500: '#64748b',
                    600: '#475569',
                    700: '#334155',
                    800: '#1e293b',
                    900: '#0f172a',
                    950: '#020617',
                },
                success: {
                    50: '#f0fdf4',
                    100: '#dcfce7',
                    500: '#22c55e',
                    600: '#16a34a',
                    700: '#15803d',
                    800: '#166534',
                },
                warning: {
                    50: '#fffbeb',
                    100: '#fef3c7',
                    500: '#f59e0b',
                    600: '#d97706',
                    700: '#b45309',
                    800: '#92400e',
                },
                danger: {
                    50: '#fef2f2',
                    100: '#fee2e2',
                    500: '#ef4444',
                    600: '#dc2626',
                    700: '#b91c1c',
                    800: '#991b1b',
                },
                info: {
                    50: '#eff6ff',
                    100: '#dbeafe',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                    800: '#1e40af',
                },
            },

            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                mono: ['"JetBrains Mono"', ...defaultTheme.fontFamily.mono],
            },

            // Major third (1.25) type scale with paired line heights.
            fontSize: {
                xs: ['0.75rem', { lineHeight: '1.125rem' }],
                sm: ['0.875rem', { lineHeight: '1.375rem' }],
                base: ['1rem', { lineHeight: '1.625rem' }],
                lg: ['1.125rem', { lineHeight: '1.75rem' }],
                xl: ['1.25rem', { lineHeight: '1.875rem' }],
                '2xl': ['1.5625rem', { lineHeight: '2.125rem' }],
                '3xl': ['1.953rem', { lineHeight: '2.5rem' }],
                '4xl': ['2.441rem', { lineHeight: '2.875rem' }],
                '5xl': ['3.052rem', { lineHeight: '3.375rem' }],
                '6xl': ['3.815rem', { lineHeight: '4rem' }],
            },

            spacing: {
                // 44px: the minimum accessible touch target used across the UI.
                'touch': '2.75rem',
                18: '4.5rem',
                88: '22rem',
                128: '32rem',
            },

            borderRadius: {
                none: '0',
                sm: '0.25rem',
                DEFAULT: '0.5rem',
                md: '0.5rem',
                lg: '0.75rem',
                xl: '1rem',
                '2xl': '1.5rem',
                full: '9999px',
            },

            boxShadow: {
                sm: '0 1px 2px 0 rgb(15 23 42 / 0.05)',
                DEFAULT: '0 1px 3px 0 rgb(15 23 42 / 0.1), 0 1px 2px -1px rgb(15 23 42 / 0.1)',
                md: '0 4px 6px -1px rgb(15 23 42 / 0.1), 0 2px 4px -2px rgb(15 23 42 / 0.1)',
                lg: '0 10px 15px -3px rgb(15 23 42 / 0.1), 0 4px 6px -4px rgb(15 23 42 / 0.1)',
                xl: '0 20px 25px -5px rgb(15 23 42 / 0.1), 0 8px 10px -6px rgb(15 23 42 / 0.1)',
                'focus': '0 0 0 3px rgb(59 130 246 / 0.45)',
            },

            maxWidth: {
                prose: '68ch',
            },

            transitionDuration: {
                DEFAULT: '150ms',
            },

            keyframes: {
                'fade-in': {
                    from: { opacity: '0' },
                    to: { opacity: '1' },
                },
                'slide-up': {
                    from: { opacity: '0', transform: 'translateY(0.5rem)' },
                    to: { opacity: '1', transform: 'translateY(0)' },
                },
            },

            animation: {
                'fade-in': 'fade-in 200ms ease-out',
                'slide-up': 'slide-up 200ms ease-out',
            },

            typography: (theme) => ({
                DEFAULT: {
                    css: {
                        maxWidth: '68ch',
                        color: theme('colors.neutral.700'),
                        a: {
                            color: theme('colors.primary.700'),
                            textUnderlineOffset: '2px',
                            '&:hover': { color: theme('colors.primary.800') },
                        },
                        'h2, h3, h4': {
                            color: theme('colors.neutral.900'),
                            scrollMarginTop: '6rem',
                        },
                        code: {
                            color: theme('colors.neutral.900'),
                            backgroundColor: theme('colors.neutral.100'),
                            padding: '0.125rem 0.375rem',
                            borderRadius: theme('borderRadius.sm'),
                            fontWeight: '500',
                        },
                        'code::before': { content: '""' },
                        'code::after': { content: '""' },
                    },
                },
            }),
        },
    },

    plugins: [forms, typography],
};
