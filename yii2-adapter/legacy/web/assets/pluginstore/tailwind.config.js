module.exports = {
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  content: ['./legacy/**/*.{vue,js}', '../resources/templates/**/*.twig'],
  theme: {
    extend: {
      screens: {
        xl: '1200px',
      },
    },
  },
  plugins: [],
};
