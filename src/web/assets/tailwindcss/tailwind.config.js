const path = require('path')
// const tailwindConf = require('../../../../packages/craftui-tailwind/config')
const colors = require('../../../../packages/craftui-tailwind/colors/colors')
const semanticColors = require('../pluginstore/semanticColors')(colors)

module.exports = {
    prefix: 'tw-',
    corePlugins: {
        preflight: true,
    },
    // ...tailwindConf,
    purge: [
        path.resolve(__dirname, '../../../templates/**/*.{html,twig}'),
        path.resolve(__dirname, '../pluginstore/src/**/*.{vue,js}'),
    ],
    plugins: [
        // ...tailwindConf.plugins,

        require('../../../../packages/craftui-tailwind')({
            semanticColors,
            darkModeSupport: false,
        }),
    ],
}