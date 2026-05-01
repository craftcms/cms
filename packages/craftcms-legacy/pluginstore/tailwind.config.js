module.exports = {
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  content: ['./src/**/*.{vue,js}', '../../../templates/plugin-store/**/*.twig'],
  theme: {
    extend: {
      screens: {
        xl: '1200px',
      },
    },
  },
  plugins: [],
};
