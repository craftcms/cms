/* jshint esversion: 6 */
/* globals module, require, __dirname */
const path = require('path');

module.exports = {
    prefix: 'tw-',
    purge: [
        path.resolve(__dirname, '../../../templates/**/*.{html,twig}')
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        extend: {},
    },
    variants: {
        extend: {},
    },
    plugins: [],
}
