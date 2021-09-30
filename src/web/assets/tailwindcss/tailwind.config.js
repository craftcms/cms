const path = require('path')
const colors = require('../../../../packages/craftui-tailwind/colors/colors')
const semanticColors = require('../pluginstore/semanticColors')(colors)

module.exports = {
    prefix: 'tw-',
    purge: [
        path.resolve(__dirname, '../../../templates/**/*.{html,twig}'),
        path.resolve(__dirname, '../pluginstore/src/**/*.{vue,js}'),
    ],
    plugins: [
        require('../../../../packages/craftui-tailwind')({
            semanticColors,
            darkModeSupport: false,
        }),
    ],
}
