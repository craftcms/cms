/* jshint esversion: 6 */
/* globals module, require, __dirname */
const path = require('path');

module.exports = {
    plugins: [
        require('tailwindcss')(path.resolve(__dirname, 'tailwind.config.js')),
        require('autoprefixer')
    ]
}
