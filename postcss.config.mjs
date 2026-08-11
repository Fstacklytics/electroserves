// Tailwind + Autoprefixer run through PostCSS. Astro picks this up
// automatically for every CSS file it bundles.
export default {
    plugins: {
        tailwindcss: {},
        autoprefixer: {},
    },
};
