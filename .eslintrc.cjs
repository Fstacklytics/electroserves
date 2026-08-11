/*
 * ESLint configuration.
 *
 * ESLint 8 is pinned in package.json, so this uses the eslintrc format rather
 * than flat config. If ESLint is upgraded to 9+, this file must be converted to
 * an `eslint.config.js` flat config (or ESLINT_USE_FLAT_CONFIG=false set), or
 * linting will silently find no configuration and fail.
 *
 * The rules below are intentionally close to `eslint:recommended`. No plugin or
 * shareable config is added: the front end is a small set of hand-written
 * Alpine.js component factories, and extra dependencies would cost more than
 * they catch here.
 */
module.exports = {
    root: true,

    env: {
        browser: true,
        es2022: true,
    },

    parserOptions: {
        ecmaVersion: 2022,
        sourceType: 'module',
    },

    extends: ['eslint:recommended'],

    rules: {
        /*
         * Debug leftovers must not reach production. console.warn and
         * console.error stay allowed so genuine failures remain reportable
         * from the browser.
         */
        'no-console': ['error', { allow: ['warn', 'error'] }],
        'no-debugger': 'error',
        'no-alert': 'error',

        /*
         * Progressive enhancement: the site must work without JavaScript, so
         * accidental globals and implicit coercion bugs are treated as errors
         * rather than style nits.
         */
        eqeqeq: ['error', 'always', { null: 'ignore' }],
        'no-implicit-globals': 'error',
        'no-var': 'error',
        'prefer-const': 'error',

        /* An unused argument prefixed with _ is a documented intentional gap. */
        'no-unused-vars': ['error', { argsIgnorePattern: '^_', varsIgnorePattern: '^_' }],
    },

    overrides: [
        {
            /* Build tooling and this config run in Node, not the browser. */
            files: ['*.cjs', '*.config.js', 'vite.config.js'],
            env: { browser: false, node: true },
        },
    ],

    ignorePatterns: ['public/build/', 'node_modules/', 'vendor/', 'storage/'],
};
