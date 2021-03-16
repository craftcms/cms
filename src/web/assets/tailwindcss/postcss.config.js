/* jshint esversion: 6 */
/* globals module, require */
const path = require('path');
const tailwindcss = require('tailwindcss');

module.exports = {
    plugins: [
        tailwindcss(path.resolve(__dirname, 'tailwind.config.js')),
        require('autoprefixer')
    ],
}
