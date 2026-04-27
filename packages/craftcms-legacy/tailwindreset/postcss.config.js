const path = require('path');
var tailwindcss = require('tailwindcss');

module.exports = {
  plugins: [
    tailwindcss(path.resolve(__dirname, './tailwind.config.js')),
    require('autoprefixer'),
  ],
};
