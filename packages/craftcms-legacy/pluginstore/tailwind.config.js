const path = require('path');

module.exports = {
  prefix: 'tw-',
  corePlugins: {
    preflight: false,
  },
  content: [
    path.join(__dirname, 'src/**/*.{vue,js}'),
    path.join(__dirname, '../../../resources/templates/plugin-store/**/*.twig'),
  ],
  theme: {
    extend: {
      screens: {
        xl: '1200px',
      },
    },
  },
  plugins: [],
};
