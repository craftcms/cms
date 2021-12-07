const path = require('path')
const colors = require('@craftcms/tailwind/colors/colors')
const semanticColors = require('../pluginstore/semanticColors')(colors)

module.exports = {
    prefix: 'tw-',
    purge: [
        path.resolve(__dirname, '../../../templates/**/*.{html,twig}'),
        path.resolve(__dirname, '../pluginstore/src/**/*.{vue,js}'),
    ],
    plugins: [
        require('@craftcms/tailwind')({
            semanticColors,
            darkModeSupport: false,
        }),
    ],
}
