/* jshint esversion: 6 */
/* globals module, require, __dirname */
const path = require('path');
const tailwindcssJit = require('@tailwindcss/jit');

module.exports = {
    plugins: [
        tailwindcssJit(path.resolve(__dirname, 'tailwind.config.js')),
        require('autoprefixer')
    ]
}
